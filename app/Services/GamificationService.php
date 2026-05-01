<?php

namespace App\Services;

use App\Models\AttendanceScore;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeAttendanceBadge;
use App\Models\PayrollCutoff;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class GamificationService
{
    const GAMIFICATION_LAUNCH_DATE = '2026-05-01';

    const PTS_ON_TIME = 10;

    const MIN_LEADERBOARD_POINTS = 10;

    const PTS_SAME_DAY = 5;

    const PTS_BADGE_NO_LATE_7 = 65;

    const PTS_BADGE_SAME_DAY_FINISHER = 30;

    const PTS_BADGE_NO_ABSENCES = 80;

    const PTS_PERFECT_CUTOFF = 75;

    const PTS_PENALTY_LATE = -5;

    const PTS_PENALTY_ABSENT = -8;

    const RANKS = [
        ['name' => 'Empty Cup',          'min' => 0,      'desc' => 'Wala pang laman, pero may potential'],
        ['name' => 'Bagong Swirl',        'min' => 50,     'desc' => 'Medyo shaky pa ang galawan'],
        ['name' => 'Toppings Trainee',    'min' => 150,    'desc' => 'Nalilito pa sa choices'],
        ['name' => 'Spoon Rookie',        'min' => 300,    'desc' => 'Ready na sumabak'],
        ['name' => 'Sauce Drizzler',      'min' => 500,    'desc' => 'May konting style na'],
        ['name' => 'Crunch Keeper',       'min' => 800,    'desc' => 'Bantay toppings mode'],
        ['name' => 'Fruit Scoop Soldier', 'min' => 1200,   'desc' => 'Fresh ang effort'],
        ['name' => 'Queue Survivor',      'min' => 1800,   'desc' => 'Sanay na sa pila'],
        ['name' => 'Swirl Master',        'min' => 2600,   'desc' => 'Malinis na ang ikot'],
        ['name' => 'Toppings Boss',       'min' => 3600,   'desc' => 'Alam na ang perfect combo'],
        ['name' => 'Sauce Overlord',      'min' => 5000,   'desc' => 'Hindi bitin mag-drizzle'],
        ['name' => 'Rush Hour Hero',      'min' => 7000,   'desc' => 'Buhay pa kahit peak time'],
        ['name' => 'Cup Legend',          'min' => 10000,  'desc' => 'Kilala na sa counter'],
        ['name' => 'Froyo Titan',         'min' => 13000,  'desc' => 'Cold, calm, powerful'],
        ['name' => 'Final Swirl Boss',    'min' => 16000,  'desc' => 'The ultimate frozen form'],
    ];

    public function rankFor(int $points): array
    {
        $ranks = self::RANKS;
        $current = $ranks[0];
        $index = 0;

        foreach ($ranks as $i => $rank) {
            if ($points >= $rank['min']) {
                $current = $rank;
                $index = $i;
            }
        }

        $next = $ranks[$index + 1] ?? null;
        $progressPct = 0;
        $pointsToNext = null;

        if ($next) {
            $span = $next['min'] - $current['min'];
            $earned = max(0, $points - $current['min']);
            $progressPct = $span > 0 ? min(100, (int) round($earned / $span * 100)) : 100;
            $pointsToNext = $next['min'] - $points;
        }

        return [
            'number' => $index + 1,
            'name' => $current['name'],
            'desc' => $current['desc'],
            'min_points' => $current['min'],
            'next_name' => $next['name'] ?? null,
            'next_min' => $next['min'] ?? null,
            'points_to_next' => $pointsToNext,
            'progress_pct' => $progressPct,
            'is_max' => $next === null,
        ];
    }

    public function __construct(private AttendanceScoringService $scoringService) {}

    // Current consecutive on-time days streak (cross-cutoff)
    public function noLateStreak(Employee $employee, ?string $throughDate = null): int
    {
        $date = Carbon::parse($throughDate ?? today())->startOfDay();
        $floor = $date->copy()->subDays(60);
        $trackingStart = $this->trackingStartDate($employee);

        $dtrs = $employee->dtrs()
            ->whereBetween('date', [$floor->toDateString(), $date->toDateString()])
            ->get()
            ->keyBy(fn (Dtr $dtr) => $dtr->date->toDateString());

        $streak = 0;
        $current = $date->copy();

        while ($current->gte($floor) && $current->gte($trackingStart)) {
            $schedule = $this->scoringService->scheduledWorkdayFor($employee, $current->toDateString());

            if (! $schedule['has_schedule']) {
                $current->subDay();

                continue;
            }

            $dtr = $dtrs->get($current->toDateString());

            if (! $dtr || ! $dtr->time_in || (int) $dtr->late_mins > 0) {
                break;
            }

            $streak++;
            $current->subDay();
        }

        return $streak;
    }

    // Data returned to the dashboard after a time_in save
    public function celebrationData(Employee $employee, Dtr $dtr): array
    {
        $isOnTime = $dtr->time_in && (int) $dtr->late_mins === 0;
        $streak = $this->noLateStreak($employee, $dtr->date->toDateString());

        return [
            'points_earned' => $isOnTime ? self::PTS_ON_TIME : 0,
            'is_on_time' => $isOnTime,
            'streak' => $streak,
            'streak_target' => 7,
        ];
    }

    // Minimal data for the dashboard teaser strip
    public function teaserData(Employee $employee): array
    {
        $streak = $this->noLateStreak($employee);

        return [
            'badge_name' => 'No-Late 7',
            'streak' => $streak,
            'target' => 7,
            'points' => self::PTS_BADGE_NO_LATE_7,
        ];
    }

    // Full data for the Achievements screen
    public function achievementsData(Employee $employee, ?PayrollCutoff $cutoff): array
    {
        $noLateStreak = $this->noLateStreak($employee);
        $thisCutoffPts = 0;
        $pointsLog = [];
        $elapsedWorkdays = 0;
        $sameDayProgress = 0;
        $noAbsentProgress = 0;
        $workdayStatuses = [];
        $trackingStart = $this->trackingStartDate($employee);

        if ($cutoff) {
            $today = today()->toDateString();
            $dtrs = $employee->dtrs()
                ->whereDate('date', '>=', $cutoff->start_date->toDateString())
                ->whereDate('date', '<=', min($cutoff->end_date->toDateString(), $today))
                ->orderBy('date')
                ->get()
                ->keyBy(fn (Dtr $dtr) => $dtr->date->toDateString());

            foreach (CarbonPeriod::create($cutoff->start_date, $cutoff->end_date) as $date) {
                $dateStr = $date->toDateString();

                if ($date->lt($trackingStart)) {
                    continue;
                }

                $schedule = $this->scoringService->scheduledWorkdayFor($employee, $dateStr);

                if (! $schedule['has_schedule']) {
                    continue;
                }

                $isFuture = $dateStr > $today;
                $isToday = $dateStr === $today;
                $dtr = $dtrs->get($dateStr);
                $hasDtr = $dtr && $dtr->time_in;
                $isOnTime = $hasDtr && (int) $dtr->late_mins === 0;
                $isSameDay = $hasDtr && $this->scoringService->wasCompletedPromptly($dtr);

                if (! $isFuture) {
                    $elapsedWorkdays++;
                }

                // Workday status for calendar grid (cutoff badge cards)
                $workdayStatuses[$dateStr] = [
                    'date' => $dateStr,
                    'label' => $date->format('D')[0],
                    'has_dtr' => (bool) $hasDtr,
                    'is_on_time' => $isOnTime,
                    'is_today' => $isToday,
                    'is_future' => $isFuture,
                ];

                if ($isFuture) {
                    continue;
                }

                if ($hasDtr) {
                    $noAbsentProgress++;
                    if ($isOnTime) {
                        $thisCutoffPts += self::PTS_ON_TIME;
                        $pointsLog[] = [
                            'type' => 'on_time',
                            'description' => 'On-time time-in',
                            'points' => self::PTS_ON_TIME,
                            'date' => Carbon::parse($dateStr)->format('M j'),
                        ];
                    } else {
                        $thisCutoffPts += self::PTS_PENALTY_LATE;
                        $pointsLog[] = [
                            'type' => 'penalty',
                            'description' => 'Late time-in',
                            'points' => self::PTS_PENALTY_LATE,
                            'date' => Carbon::parse($dateStr)->format('M j'),
                        ];
                    }
                    if ($isSameDay) {
                        $thisCutoffPts += self::PTS_SAME_DAY;
                        $sameDayProgress++;
                        $pointsLog[] = [
                            'type' => 'same_day',
                            'description' => 'Same-day DTR filed',
                            'points' => self::PTS_SAME_DAY,
                            'date' => Carbon::parse($dateStr)->format('M j'),
                        ];
                    }
                } elseif (! $isToday) {
                    $thisCutoffPts += self::PTS_PENALTY_ABSENT;
                    $pointsLog[] = [
                        'type' => 'penalty',
                        'description' => 'Absent',
                        'points' => self::PTS_PENALTY_ABSENT,
                        'date' => Carbon::parse($dateStr)->format('M j'),
                    ];
                }
            }

            // Badge points from this cutoff (if finalized and awarded)
            $cutoffBadges = EmployeeAttendanceBadge::where('employee_id', $employee->id)
                ->where('payroll_cutoff_id', $cutoff->id)
                ->whereHas('badge', fn ($q) => $q->where('active', true))
                ->with('badge')
                ->get();

            foreach ($cutoffBadges as $award) {
                $pts = $this->badgePoints($award->badge?->key);
                if ($pts > 0) {
                    $thisCutoffPts += $pts;
                    $pointsLog[] = [
                        'type' => 'badge',
                        'description' => ($award->badge->name ?? 'Badge').' earned',
                        'points' => $pts,
                        'date' => Carbon::parse($award->awarded_at)->format('M j'),
                    ];
                }
            }

            if ($cutoff->status === 'finalized' && $elapsedWorkdays > 0 && $noAbsentProgress >= $elapsedWorkdays && ! collect($workdayStatuses)->contains(fn (array $status) => ! $status['is_on_time'])) {
                $thisCutoffPts += self::PTS_PERFECT_CUTOFF;
                $pointsLog[] = [
                    'type' => 'bonus',
                    'description' => 'Perfect Cutoff bonus',
                    'points' => self::PTS_PERFECT_CUTOFF,
                    'date' => Carbon::parse($cutoff->finalized_at ?? $cutoff->end_date)->format('M j'),
                ];
            }
        }

        $allTimePoints = $this->allTimePoints($employee, $cutoff) + $thisCutoffPts;

        $badges = $this->buildBadges(
            $employee, $cutoff, $noLateStreak,
            $elapsedWorkdays, $sameDayProgress, $noAbsentProgress,
            $workdayStatuses,
        );

        $totalBadgesEarned = EmployeeAttendanceBadge::where('employee_id', $employee->id)
            ->whereHas('badge', fn ($q) => $q->where('active', true))
            ->count();
        $positivePoints = collect($pointsLog)
            ->sum(fn (array $row) => max(0, (int) $row['points']));
        $penaltyPoints = abs(collect($pointsLog)
            ->sum(fn (array $row) => min(0, (int) $row['points'])));

        return [
            'total_points' => $allTimePoints,
            'this_cutoff_points' => $thisCutoffPts,
            'current_period_positive_points' => $positivePoints,
            'current_period_penalty_points' => $penaltyPoints,
            'total_badges_earned' => $totalBadgesEarned,
            'points_log' => array_reverse($pointsLog),
            'badges' => $badges,
            'no_late_streak' => $noLateStreak,
        ];
    }

    public function leaderboard(Employee $viewer, int $limit = 10, ?string $search = null): array
    {
        $employees = Employee::query()
            ->where('active', true)
            ->whereHas('user', fn ($query) => $query->where('role', 'staff'))
            ->with(['branch', 'user'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        if ($employees->isEmpty()) {
            return [
                'top10' => [],
                'allEntries' => [],
                'allEmployees' => [],
                'viewerRank' => null,
                'viewerInTop10' => false,
                'searchQuery' => trim((string) $search),
                'searchResults' => [],
            ];
        }

        $employeeIds = $employees->pluck('id')->all();

        // 60-day window covers both the streak lookback and any current cutoff period
        $fromDate = today()->subDays(60)->toDateString();
        $toDate = today()->toDateString();

        // Fix 1+2: bulk-fill the schedule cache — eliminates O(N×D) per-day DB queries
        $this->scoringService->preloadSchedules($employees, $fromDate, $toDate);

        // One query per shared resource instead of one per employee
        $branchIds = $employees->pluck('branch_id')->unique()->filter()->values()->all();

        $cutoffsByBranch = PayrollCutoff::where('status', '!=', 'voided')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->whereIn('branch_id', $branchIds)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->groupBy('branch_id');

        $allDtrs = Dtr::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$fromDate, $toDate])
            ->with('logEvents')
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($group) => $group->keyBy(fn ($dtr) => $dtr->date->toDateString()));

        $allScores = AttendanceScore::whereIn('employee_id', $employeeIds)
            ->whereHas('payrollCutoff', fn ($q) => $q->where('status', 'finalized'))
            ->with('payrollCutoff')
            ->get()
            ->groupBy('employee_id');

        $allBadges = EmployeeAttendanceBadge::whereIn('employee_id', $employeeIds)
            ->whereHas('badge', fn ($q) => $q->where('active', true))
            ->with('badge')
            ->get()
            ->groupBy('employee_id');

        $allMapped = $employees
            ->map(function (Employee $employee) use ($viewer, $cutoffsByBranch, $allDtrs, $allScores, $allBadges) {
                $cutoff = $cutoffsByBranch->get($employee->branch_id)?->first()
                    ?? $this->virtualCurrentCutoff($employee);

                $cutoffId = $cutoff->id ?? null;
                $employeeBadges = $allBadges->get($employee->id, collect());

                $currentCutoffBadges = $cutoffId !== null
                    ? $employeeBadges->where('payroll_cutoff_id', $cutoffId)
                    : collect();

                $pastBadges = $cutoffId !== null
                    ? $employeeBadges->where('payroll_cutoff_id', '!=', $cutoffId)
                    : $employeeBadges;

                // Exclude current cutoff from stored scores (its points are computed live below)
                $pastScores = $cutoffId !== null
                    ? $allScores->get($employee->id, collect())->where('payroll_cutoff_id', '!=', $cutoffId)
                    : $allScores->get($employee->id, collect());

                $points = $this->computeTotalPoints(
                    $employee,
                    $cutoff,
                    $allDtrs->get($employee->id, collect()),
                    $pastScores,
                    $currentCutoffBadges,
                    $pastBadges,
                );

                return [
                    'rank' => null,
                    'employee_id' => $employee->id,
                    'name' => $employee->full_name,
                    'branch' => $employee->branch?->name ?? 'No branch',
                    'points' => $points,
                    'rank_name' => $this->rankFor($points)['name'],
                    'is_viewer' => $employee->id === $viewer->id,
                    'profile_photo_url' => $employee->user?->profile_photo_url,
                ];
            })
            ->sortBy([
                ['points', 'desc'],
                ['name', 'asc'],
            ])
            ->values();

        // Assign sequential rank only to leaderboard-eligible employees
        $leaderboardRank = 0;
        $allWithRanks = $allMapped->map(function (array $row) use (&$leaderboardRank) {
            if ($row['points'] >= self::MIN_LEADERBOARD_POINTS) {
                $row['rank'] = ++$leaderboardRank;
            }

            return $row;
        });

        $ranked = $allWithRanks->filter(fn (array $row) => $row['rank'] !== null)->values();

        $top = $ranked->take($limit)->values();
        $viewerRow = $ranked->firstWhere('employee_id', $viewer->id);
        $searchQuery = trim((string) $search);
        $searchResults = collect();

        if ($searchQuery !== '') {
            $needle = mb_strtolower($searchQuery);

            $searchResults = $ranked
                ->filter(fn (array $row) => str_contains(mb_strtolower($row['name']), $needle))
                ->take(5)
                ->values();
        }

        return [
            'top10' => $top->all(),
            'allEntries' => $ranked->all(),
            'allEmployees' => $allWithRanks->all(),
            'viewerRank' => $viewerRow,
            'viewerInTop10' => $viewerRow ? $top->contains('employee_id', $viewer->id) : false,
            'searchQuery' => $searchQuery,
            'searchResults' => $searchResults->all(),
        ];
    }

    /**
     * Lightweight point total for leaderboard use — same math as achievementsData()
     * but skips badge-display building, points log, and workday status arrays.
     *
     * @param  \Illuminate\Support\Collection  $cutoffDtrs      DTRs keyed by date string (current cutoff range)
     * @param  \Illuminate\Support\Collection  $pastScores      AttendanceScore rows for past finalized cutoffs
     * @param  \Illuminate\Support\Collection  $currentCutoffBadges  EmployeeAttendanceBadge rows for the current cutoff
     * @param  \Illuminate\Support\Collection  $pastBadges      EmployeeAttendanceBadge rows for all other cutoffs
     */
    private function computeTotalPoints(
        Employee $employee,
        ?PayrollCutoff $cutoff,
        \Illuminate\Support\Collection $cutoffDtrs,
        \Illuminate\Support\Collection $pastScores,
        \Illuminate\Support\Collection $currentCutoffBadges,
        \Illuminate\Support\Collection $pastBadges,
    ): int {
        $trackingStart = $this->trackingStartDate($employee);
        $today = today()->toDateString();
        $thisCutoffPts = 0;
        $elapsedWorkdays = 0;
        $noAbsentProgress = 0;
        $allOnTime = true;

        if ($cutoff) {
            foreach (CarbonPeriod::create($cutoff->start_date, $cutoff->end_date) as $date) {
                $dateStr = $date->toDateString();

                if ($date->lt($trackingStart)) {
                    continue;
                }

                $schedule = $this->scoringService->scheduledWorkdayFor($employee, $dateStr);

                if (! $schedule['has_schedule']) {
                    continue;
                }

                $isFuture = $dateStr > $today;
                $isToday = $dateStr === $today;

                if (! $isFuture) {
                    $elapsedWorkdays++;
                }

                if ($isFuture) {
                    continue;
                }

                $dtr = $cutoffDtrs->get($dateStr);
                $hasDtr = $dtr && $dtr->time_in;

                if ($hasDtr) {
                    $noAbsentProgress++;
                    $isOnTime = (int) $dtr->late_mins === 0;

                    if ($isOnTime) {
                        $thisCutoffPts += self::PTS_ON_TIME;
                    } else {
                        $thisCutoffPts += self::PTS_PENALTY_LATE;
                        $allOnTime = false;
                    }

                    if ($this->scoringService->wasCompletedPromptly($dtr)) {
                        $thisCutoffPts += self::PTS_SAME_DAY;
                    }
                } elseif (! $isToday) {
                    $thisCutoffPts += self::PTS_PENALTY_ABSENT;
                    $allOnTime = false;
                }
            }

            // Badge points awarded for this cutoff
            foreach ($currentCutoffBadges as $award) {
                $thisCutoffPts += $this->badgePoints($award->badge?->key);
            }

            // Perfect cutoff bonus (only when cutoff is already finalized)
            if ($cutoff->status === 'finalized'
                && $elapsedWorkdays > 0
                && $noAbsentProgress >= $elapsedWorkdays
                && $allOnTime
            ) {
                $thisCutoffPts += self::PTS_PERFECT_CUTOFF;
            }
        }

        // Points from past finalized cutoffs (pre-loaded, no extra queries)
        $pastPts = $pastScores->sum(function (AttendanceScore $score) use ($employee) {
            $pastCutoff = $score->payrollCutoff;

            $base = $score->on_time_days * self::PTS_ON_TIME
                + $score->same_day_complete_days * self::PTS_SAME_DAY;

            if (! $pastCutoff) {
                return $base;
            }

            $penalties = $this->deductionsForPeriod(
                $employee,
                $pastCutoff->start_date->toDateString(),
                $pastCutoff->end_date->toDateString(),
            );

            $perfectBonus = $this->qualifiesForPerfectCutoff($score, $employee, $pastCutoff)
                ? self::PTS_PERFECT_CUTOFF
                : 0;

            return $base + $penalties + $perfectBonus;
        });

        $pastBadgePts = $pastBadges->sum(fn ($award) => $this->badgePoints($award->badge?->key));

        return $pastPts + $pastBadgePts + $thisCutoffPts;
    }

    public function currentCutoffFor(Employee $employee): ?PayrollCutoff
    {
        $cutoff = PayrollCutoff::where('branch_id', $employee->branch_id)
            ->where('status', '!=', 'voided')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        return $cutoff ?? $this->virtualCurrentCutoff($employee);
    }

    private function virtualCurrentCutoff(Employee $employee): PayrollCutoff
    {
        [$startDate, $endDate] = $this->currentAttendancePeriod(today());

        return new PayrollCutoff([
            'branch_id' => $employee->branch_id,
            'name' => 'Current attendance period',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'draft',
        ]);
    }

    private function currentAttendancePeriod(Carbon $date): array
    {
        $day = (int) $date->format('j');

        if ($day >= 14 && $day <= 29) {
            return [
                $date->copy()->day(14)->startOfDay(),
                $date->copy()->day(29)->startOfDay(),
            ];
        }

        if ($day >= 30) {
            return [
                $date->copy()->day(30)->startOfDay(),
                $date->copy()->addMonthNoOverflow()->day(13)->startOfDay(),
            ];
        }

        $previousMonth = $date->copy()->subMonthNoOverflow();

        return [
            $previousMonth->copy()->day(min(30, $previousMonth->daysInMonth))->startOfDay(),
            $date->copy()->day(13)->startOfDay(),
        ];
    }

    private function deductionsForPeriod(Employee $employee, string $startDate, string $endDate): int
    {
        $today = today()->toDateString();
        $end = min($endDate, $today);
        $trackingStart = $this->trackingStartDate($employee)->toDateString();
        $start = max($startDate, $trackingStart);

        if ($start > $end) {
            return 0;
        }

        $dtrs = $employee->dtrs()
            ->whereDate('date', '>=', $start)
            ->whereDate('date', '<=', $end)
            ->get()
            ->keyBy(fn (Dtr $dtr) => $dtr->date->toDateString());

        $total = 0;

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $dateStr = $date->toDateString();
            $schedule = $this->scoringService->scheduledWorkdayFor($employee, $dateStr);

            if (! $schedule['has_schedule']) {
                continue;
            }

            $dtr = $dtrs->get($dateStr);
            $hasDtr = $dtr && $dtr->time_in;

            if (! $hasDtr) {
                $total += self::PTS_PENALTY_ABSENT;
            } elseif ((int) ($dtr->late_mins ?? 0) > 0) {
                $total += self::PTS_PENALTY_LATE;
            }
        }

        return $total;
    }

    private function allTimePoints(Employee $employee, ?PayrollCutoff $currentCutoff): int
    {
        $query = AttendanceScore::where('employee_id', $employee->id)
            ->whereHas('payrollCutoff', fn ($q) => $q->where('status', 'finalized'));

        if ($currentCutoff?->id) {
            $query->where('payroll_cutoff_id', '!=', $currentCutoff->id);
        }

        $pastPts = $query->with('payrollCutoff')->get()->sum(function (AttendanceScore $score) use ($employee) {
            $cutoff = $score->payrollCutoff;
            $base = $score->on_time_days * self::PTS_ON_TIME
                + $score->same_day_complete_days * self::PTS_SAME_DAY;

            if (! $cutoff) {
                return $base;
            }

            $penalties = $this->deductionsForPeriod(
                $employee,
                $cutoff->start_date->toDateString(),
                $cutoff->end_date->toDateString(),
            );

            $perfectBonus = $this->qualifiesForPerfectCutoff($score, $employee, $cutoff)
                ? self::PTS_PERFECT_CUTOFF
                : 0;

            return $base + $penalties + $perfectBonus;
        });

        // Badge points from all past cutoffs
        $badgePtsQuery = EmployeeAttendanceBadge::where('employee_id', $employee->id)
            ->whereHas('badge', fn ($q) => $q->where('active', true))
            ->with('badge');

        if ($currentCutoff?->id) {
            $badgePtsQuery->where('payroll_cutoff_id', '!=', $currentCutoff->id);
        }

        $badgePts = $badgePtsQuery->get()->sum(
            fn ($award) => $this->badgePoints($award->badge?->key)
        );

        return $pastPts + $badgePts;
    }

    private function qualifiesForPerfectCutoff(AttendanceScore $score, Employee $employee, PayrollCutoff $cutoff): bool
    {
        $scheduledWorkdays = collect(CarbonPeriod::create($cutoff->start_date, $cutoff->end_date))
            ->filter(fn (Carbon $date) => $this->scoringService->scheduledWorkdayFor($employee, $date->toDateString())['has_schedule'])
            ->count();

        return $scheduledWorkdays > 0
            && (int) $score->late_days === 0
            && (int) $score->no_absent_days >= $scheduledWorkdays;
    }

    private function trackingStartDate(Employee $employee): Carbon
    {
        $employee->loadMissing('user');

        $launch = Carbon::parse(self::GAMIFICATION_LAUNCH_DATE)->startOfDay();

        $candidates = array_filter([
            $employee->created_at?->copy()->startOfDay(),
            $employee->user?->created_at?->copy()->startOfDay(),
            $employee->hired_date?->copy()->startOfDay(),
        ]);

        if ($candidates === []) {
            return $launch;
        }

        $employeeStart = collect($candidates)
            ->sortBy(fn (Carbon $date) => $date->timestamp)
            ->last()
            ->copy();

        return $employeeStart->gt($launch) ? $employeeStart : $launch;
    }

    private function badgePoints(?string $key): int
    {
        return match ($key) {
            AttendanceBadgeService::BADGE_ON_TIME_7 => self::PTS_BADGE_NO_LATE_7,
            AttendanceBadgeService::BADGE_SAME_DAY_FINISHER => self::PTS_BADGE_SAME_DAY_FINISHER,
            AttendanceBadgeService::BADGE_NO_ABSENT_CUTOFF => self::PTS_BADGE_NO_ABSENCES,
            default => 0,
        };
    }

    private function buildBadges(
        Employee $employee,
        ?PayrollCutoff $cutoff,
        int $noLateStreak,
        int $elapsedWorkdays,
        int $sameDayProgress,
        int $noAbsentProgress,
        array $workdayStatuses,
    ): array {
        $timesEarned = EmployeeAttendanceBadge::where('employee_id', $employee->id)
            ->whereHas('badge', fn ($q) => $q->where('active', true))
            ->join('attendance_badges', 'attendance_badges.id', '=', 'employee_attendance_badges.attendance_badge_id')
            ->groupBy('attendance_badges.key')
            ->selectRaw('attendance_badges.key, COUNT(*) as cnt')
            ->pluck('cnt', 'key');

        $total = max($elapsedWorkdays, 1);

        // --- No-Late 7 (streak badge) ---
        $streakTarget = 7;
        $streakProgress = min($noLateStreak, $streakTarget);
        $dayStatuses = [];
        for ($i = 1; $i <= $streakTarget; $i++) {
            if ($i <= $streakProgress) {
                $dayStatuses[] = 'done';
            } elseif ($i === $streakProgress + 1) {
                $dayStatuses[] = 'current';
            } else {
                $dayStatuses[] = 'future';
            }
        }

        $noLate7 = [
            'id' => 'on_time_7',
            'name' => 'No-Late 7',
            'tagline' => 'Arrive on time 7 days in a row',
            'desc' => 'Clock in before or at your scheduled start time for 7 consecutive working days. Any late arrival resets the streak.',
            'type' => 'streak',
            'color' => '#E8722A',
            'bg_color' => '#1c1408',
            'border_color' => '#FED7AA',
            'icon' => '⏱',
            'progress' => $streakProgress,
            'total' => $streakTarget,
            'earned' => $noLateStreak >= $streakTarget,
            'times_earned' => (int) ($timesEarned[AttendanceBadgeService::BADGE_ON_TIME_7] ?? 0),
            'points' => self::PTS_BADGE_NO_LATE_7,
            'tip' => 'Clock in before or at your scheduled start time, every day.',
            'day_statuses' => $dayStatuses,
        ];

        // --- Same-Day Finisher (cutoff badge) ---
        $sameDayFinisher = [
            'id' => 'same_day_finisher',
            'name' => 'Same-Day Finisher',
            'tagline' => 'File every DTR on the day it happens',
            'desc' => 'Complete all 4 time events (time in, break start, break end, time out) on the same calendar day as your work date, for every scheduled workday this cutoff.',
            'type' => 'cutoff',
            'color' => '#3B82F6',
            'bg_color' => '#080e1c',
            'border_color' => '#BFDBFE',
            'icon' => '✦',
            'progress' => $sameDayProgress,
            'total' => $elapsedWorkdays,
            'earned' => $cutoff && $elapsedWorkdays > 0 && $sameDayProgress >= $elapsedWorkdays,
            'on_track' => $elapsedWorkdays > 0 && $sameDayProgress === $elapsedWorkdays,
            'times_earned' => (int) ($timesEarned[AttendanceBadgeService::BADGE_SAME_DAY_FINISHER] ?? 0),
            'points' => self::PTS_BADGE_SAME_DAY_FINISHER,
            'tip' => 'File all 4 DTR events — time in, break start, break end, and time out — before midnight each day.',
            'workday_statuses' => array_values($workdayStatuses),
        ];

        // --- No Absences (cutoff badge) ---
        $noAbsences = [
            'id' => 'no_absent_cutoff',
            'name' => 'No Absences',
            'tagline' => 'Show up every scheduled day this cutoff',
            'desc' => 'Have at least a time-in DTR entry for every scheduled workday in the cutoff. Late days still count.',
            'type' => 'cutoff',
            'color' => '#5BBF27',
            'bg_color' => '#071408',
            'border_color' => '#C8ECA4',
            'icon' => '✓',
            'progress' => $noAbsentProgress,
            'total' => $elapsedWorkdays,
            'earned' => $cutoff && $elapsedWorkdays > 0 && $noAbsentProgress >= $elapsedWorkdays,
            'on_track' => $elapsedWorkdays > 0 && $noAbsentProgress === $elapsedWorkdays,
            'times_earned' => (int) ($timesEarned[AttendanceBadgeService::BADGE_NO_ABSENT_CUTOFF] ?? 0),
            'points' => self::PTS_BADGE_NO_ABSENCES,
            'tip' => 'Clock in for every scheduled workday this cutoff — even if you\'re late or can\'t complete your full DTR.',
            'workday_statuses' => array_values($workdayStatuses),
        ];

        return [$noLate7, $sameDayFinisher, $noAbsences];
    }
}
