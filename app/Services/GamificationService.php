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
    const PTS_ON_TIME   = 10;
    const PTS_SAME_DAY  = 5;
    const PTS_BADGE_NO_LATE_5           = 50;
    const PTS_BADGE_SAME_DAY_FINISHER   = 30;
    const PTS_BADGE_NO_ABSENCES         = 80;
    const PTS_PENALTY_LATE              = -2;
    const PTS_PENALTY_ABSENT            = -3;

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
        ['name' => 'Froyo Titan',         'min' => 15000,  'desc' => 'Cold, calm, powerful'],
        ['name' => 'Final Swirl Boss',    'min' => 22000,  'desc' => 'The ultimate frozen form'],
    ];

    public function rankFor(int $points): array
    {
        $ranks   = self::RANKS;
        $current = $ranks[0];
        $index   = 0;

        foreach ($ranks as $i => $rank) {
            if ($points >= $rank['min']) {
                $current = $rank;
                $index   = $i;
            }
        }

        $next            = $ranks[$index + 1] ?? null;
        $progressPct     = 0;
        $pointsToNext    = null;

        if ($next) {
            $span        = $next['min'] - $current['min'];
            $earned      = $points - $current['min'];
            $progressPct = $span > 0 ? min(100, (int) round($earned / $span * 100)) : 100;
            $pointsToNext = $next['min'] - $points;
        }

        return [
            'number'        => $index + 1,
            'name'          => $current['name'],
            'desc'          => $current['desc'],
            'min_points'    => $current['min'],
            'next_name'     => $next['name'] ?? null,
            'next_min'      => $next['min'] ?? null,
            'points_to_next'=> $pointsToNext,
            'progress_pct'  => $progressPct,
            'is_max'        => $next === null,
        ];
    }

    public function __construct(private AttendanceScoringService $scoringService) {}

    // Current consecutive on-time days streak (cross-cutoff)
    public function noLateStreak(Employee $employee, ?string $throughDate = null): int
    {
        $date  = Carbon::parse($throughDate ?? today())->startOfDay();
        $floor = $date->copy()->subDays(60);
        $trackingStart = $this->trackingStartDate($employee);

        $dtrs = $employee->dtrs()
            ->whereBetween('date', [$floor->toDateString(), $date->toDateString()])
            ->get()
            ->keyBy(fn (Dtr $dtr) => $dtr->date->toDateString());

        $streak  = 0;
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
        $streak   = $this->noLateStreak($employee, $dtr->date->toDateString());

        return [
            'points_earned' => $isOnTime ? self::PTS_ON_TIME : 0,
            'is_on_time'    => $isOnTime,
            'streak'        => $streak,
            'streak_target' => 5,
        ];
    }

    // Minimal data for the dashboard teaser strip
    public function teaserData(Employee $employee): array
    {
        $streak = $this->noLateStreak($employee);

        return [
            'badge_name' => 'No-Late 5',
            'streak'     => $streak,
            'target'     => 5,
            'points'     => self::PTS_BADGE_NO_LATE_5,
        ];
    }

    // Full data for the Achievements screen
    public function achievementsData(Employee $employee, ?PayrollCutoff $cutoff): array
    {
        $noLateStreak    = $this->noLateStreak($employee);
        $thisCutoffPts   = 0;
        $pointsLog       = [];
        $elapsedWorkdays = 0;
        $sameDayProgress = 0;
        $noAbsentProgress = 0;
        $workdayStatuses  = [];
        $trackingStart = $this->trackingStartDate($employee);

        if ($cutoff) {
            $today = today()->toDateString();
            $dtrs  = $employee->dtrs()
                ->whereBetween('date', [
                    $cutoff->start_date->toDateString(),
                    min($cutoff->end_date->toDateString(), $today),
                ])
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
                $isToday  = $dateStr === $today;
                $dtr      = $dtrs->get($dateStr);
                $hasDtr   = $dtr && $dtr->time_in;
                $isOnTime = $hasDtr && (int) $dtr->late_mins === 0;
                $isSameDay = $hasDtr && $this->scoringService->wasCompletedPromptly($dtr);

                if (! $isFuture) {
                    $elapsedWorkdays++;
                }

                // Workday status for calendar grid (cutoff badge cards)
                $workdayStatuses[$dateStr] = [
                    'date'       => $dateStr,
                    'label'      => $date->format('D')[0],
                    'has_dtr'    => (bool) $hasDtr,
                    'is_on_time' => $isOnTime,
                    'is_today'   => $isToday,
                    'is_future'  => $isFuture,
                ];

                if ($isFuture) {
                    continue;
                }

                if ($hasDtr) {
                    $noAbsentProgress++;
                    if ($isOnTime) {
                        $thisCutoffPts += self::PTS_ON_TIME;
                        $pointsLog[] = [
                            'type'        => 'on_time',
                            'description' => 'On-time time-in',
                            'points'      => self::PTS_ON_TIME,
                            'date'        => Carbon::parse($dateStr)->format('M j'),
                        ];
                    } else {
                        $thisCutoffPts += self::PTS_PENALTY_LATE;
                        $pointsLog[] = [
                            'type'        => 'penalty',
                            'description' => 'Late time-in',
                            'points'      => self::PTS_PENALTY_LATE,
                            'date'        => Carbon::parse($dateStr)->format('M j'),
                        ];
                    }
                    if ($isSameDay) {
                        $thisCutoffPts += self::PTS_SAME_DAY;
                        $sameDayProgress++;
                        $pointsLog[] = [
                            'type'        => 'same_day',
                            'description' => 'Same-day DTR filed',
                            'points'      => self::PTS_SAME_DAY,
                            'date'        => Carbon::parse($dateStr)->format('M j'),
                        ];
                    }
                } else {
                    $thisCutoffPts += self::PTS_PENALTY_ABSENT;
                    $pointsLog[] = [
                        'type'        => 'penalty',
                        'description' => 'Absent',
                        'points'      => self::PTS_PENALTY_ABSENT,
                        'date'        => Carbon::parse($dateStr)->format('M j'),
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
                        'type'        => 'badge',
                        'description' => ($award->badge->name ?? 'Badge') . ' earned',
                        'points'      => $pts,
                        'date'        => Carbon::parse($award->awarded_at)->format('M j'),
                    ];
                }
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

        return [
            'total_points'        => $allTimePoints,
            'this_cutoff_points'  => $thisCutoffPts,
            'total_badges_earned' => $totalBadgesEarned,
            'points_log'          => array_reverse($pointsLog),
            'badges'              => $badges,
            'no_late_streak'      => $noLateStreak,
        ];
    }

    private function deductionsForPeriod(Employee $employee, string $startDate, string $endDate): int
    {
        $today = today()->toDateString();
        $end   = min($endDate, $today);
        $trackingStart = $this->trackingStartDate($employee)->toDateString();
        $start = max($startDate, $trackingStart);

        if ($start > $end) {
            return 0;
        }

        $dtrs = $employee->dtrs()
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy(fn (Dtr $dtr) => $dtr->date->toDateString());

        $total = 0;

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $dateStr  = $date->toDateString();
            $schedule = $this->scoringService->scheduledWorkdayFor($employee, $dateStr);

            if (! $schedule['has_schedule']) {
                continue;
            }

            $dtr    = $dtrs->get($dateStr);
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
        // Sum gamification-rate points from all previously finalized cutoff scores
        $query = AttendanceScore::where('employee_id', $employee->id)
            ->whereHas('payrollCutoff', fn ($q) => $q->where('status', 'finalized'));

        if ($currentCutoff?->id) {
            $query->where('payroll_cutoff_id', '!=', $currentCutoff->id);
        }

        $pastPts = $query->get()->sum(
            fn (AttendanceScore $s) => $s->on_time_days * self::PTS_ON_TIME + $s->same_day_complete_days * self::PTS_SAME_DAY
        );

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

        $cutoffQuery = PayrollCutoff::where('branch_id', $employee->branch_id)
            ->where('status', 'finalized');

        if ($currentCutoff?->id) {
            $cutoffQuery->where('id', '!=', $currentCutoff->id);
        }

        $historicalDeductions = $cutoffQuery->get()->sum(
            fn (PayrollCutoff $c) => $this->deductionsForPeriod(
                $employee,
                $c->start_date->toDateString(),
                $c->end_date->toDateString(),
            )
        );

        return $pastPts + $badgePts + $historicalDeductions;
    }

    private function trackingStartDate(Employee $employee): Carbon
    {
        $employee->loadMissing('user');

        $candidates = array_filter([
            $employee->created_at?->copy()->startOfDay(),
            $employee->user?->created_at?->copy()->startOfDay(),
            $employee->hired_date?->copy()->startOfDay(),
        ]);

        if ($candidates === []) {
            return today()->copy()->startOfDay();
        }

        return collect($candidates)
            ->sortBy(fn (Carbon $date) => $date->timestamp)
            ->last()
            ->copy();
    }

    private function badgePoints(?string $key): int
    {
        return match ($key) {
            AttendanceBadgeService::BADGE_ON_TIME_5          => self::PTS_BADGE_NO_LATE_5,
            AttendanceBadgeService::BADGE_SAME_DAY_FINISHER  => self::PTS_BADGE_SAME_DAY_FINISHER,
            AttendanceBadgeService::BADGE_NO_ABSENT_CUTOFF   => self::PTS_BADGE_NO_ABSENCES,
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

        // --- No-Late 5 (streak badge) ---
        $streak5 = min($noLateStreak, 5);
        $dayStatuses = [];
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $streak5) {
                $dayStatuses[] = 'done';
            } elseif ($i === $streak5 + 1) {
                $dayStatuses[] = 'current';
            } else {
                $dayStatuses[] = 'future';
            }
        }

        $noLate5 = [
            'id'           => 'on_time_5',
            'name'         => 'No-Late 5',
            'tagline'      => 'Arrive on time 5 days in a row',
            'desc'         => 'Clock in before or at your scheduled start time for 5 consecutive working days. Any late arrival resets the streak.',
            'type'         => 'streak',
            'color'        => '#E8722A',
            'bg_color'     => '#FFF7ED',
            'border_color' => '#FED7AA',
            'icon'         => '⏱',
            'progress'     => $streak5,
            'total'        => 5,
            'earned'       => $noLateStreak >= 5,
            'times_earned' => (int) ($timesEarned[AttendanceBadgeService::BADGE_ON_TIME_5] ?? 0),
            'points'       => self::PTS_BADGE_NO_LATE_5,
            'tip'          => 'Clock in before or at your scheduled start time, every day.',
            'day_statuses' => $dayStatuses,
        ];

        // --- Same-Day Finisher (cutoff badge) ---
        $sameDayFinisher = [
            'id'               => 'same_day_finisher',
            'name'             => 'Same-Day Finisher',
            'tagline'          => 'File every DTR on the day it happens',
            'desc'             => 'Complete all 4 time events (time in, break start, break end, time out) on the same calendar day as your work date, for every scheduled workday this cutoff.',
            'type'             => 'cutoff',
            'color'            => '#3B82F6',
            'bg_color'         => '#EFF5FF',
            'border_color'     => '#BFDBFE',
            'icon'             => '✦',
            'progress'         => $sameDayProgress,
            'total'            => $elapsedWorkdays,
            'earned'           => $cutoff && $elapsedWorkdays > 0 && $sameDayProgress >= $elapsedWorkdays,
            'on_track'         => $elapsedWorkdays > 0 && $sameDayProgress === $elapsedWorkdays,
            'times_earned'     => (int) ($timesEarned[AttendanceBadgeService::BADGE_SAME_DAY_FINISHER] ?? 0),
            'points'           => self::PTS_BADGE_SAME_DAY_FINISHER,
            'tip'              => 'File all 4 DTR events — time in, break start, break end, and time out — before midnight each day.',
            'workday_statuses' => array_values($workdayStatuses),
        ];

        // --- No Absences (cutoff badge) ---
        $noAbsences = [
            'id'               => 'no_absent_cutoff',
            'name'             => 'No Absences',
            'tagline'          => 'Show up every scheduled day this cutoff',
            'desc'             => 'Have at least a time-in DTR entry for every scheduled workday in the cutoff. Late days still count.',
            'type'             => 'cutoff',
            'color'            => '#5BBF27',
            'bg_color'         => '#EBF7E0',
            'border_color'     => '#C8ECA4',
            'icon'             => '✓',
            'progress'         => $noAbsentProgress,
            'total'            => $elapsedWorkdays,
            'earned'           => $cutoff && $elapsedWorkdays > 0 && $noAbsentProgress >= $elapsedWorkdays,
            'on_track'         => $elapsedWorkdays > 0 && $noAbsentProgress === $elapsedWorkdays,
            'times_earned'     => (int) ($timesEarned[AttendanceBadgeService::BADGE_NO_ABSENT_CUTOFF] ?? 0),
            'points'           => self::PTS_BADGE_NO_ABSENCES,
            'tip'              => 'Clock in for every scheduled workday this cutoff — even if you\'re late or can\'t complete your full DTR.',
            'workday_statuses' => array_values($workdayStatuses),
        ];

        return [$noLate5, $sameDayFinisher, $noAbsences];
    }
}
