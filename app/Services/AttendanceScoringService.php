<?php

namespace App\Services;

use App\Models\AttendanceScore;
use App\Models\DailySchedule;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\PayrollCutoff;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\GamificationService;

class AttendanceScoringService
{
    private array $scheduleCache = [];

    public const RULE_NO_LATE = 'no_late_reward';

    public const RULE_NO_ABSENT = 'no_absent_reward';

    public const RULE_SAME_DAY_COMPLETE = 'same_day_complete_dtr';

    public const RULE_LATE_PENALTY = 'late_penalty';

    public const RULE_ABSENT_PENALTY = 'absent_penalty';

    public const RULE_PERFECT_CUTOFF_BONUS = 'perfect_cutoff_bonus';

    public function scoreEmployeeForCutoff(PayrollCutoff $cutoff, Employee $employee): AttendanceScore
    {
        return DB::transaction(function () use ($cutoff, $employee) {
            $result = $this->estimateEmployeeForCutoff($cutoff, $employee);

            $score = AttendanceScore::firstOrNew([
                'payroll_cutoff_id' => $cutoff->id,
                'employee_id' => $employee->id,
            ]);

            $score->fill($result['totals'] + [
                'finalized_at' => $cutoff->finalized_at ?? now(),
            ]);
            $score->save();

            $score->items()->delete();
            $score->items()->createMany($result['items']);

            return $score->load('items');
        });
    }

    public function estimateEmployeeForCutoff(PayrollCutoff $cutoff, Employee $employee): array
    {
        $dtrs = $employee->dtrs()
            ->whereDate('date', '>=', $cutoff->start_date->toDateString())
            ->whereDate('date', '<=', $cutoff->end_date->toDateString())
            ->orderBy('date')
            ->get()
            ->keyBy(fn (Dtr $dtr) => $dtr->date->toDateString());

        return $this->scorePeriod($cutoff, $employee, $dtrs);
    }

    public function scoreCutoff(PayrollCutoff $cutoff): Collection
    {
        $employees = $cutoff->payrollEntries()
            ->with('employee')
            ->get()
            ->pluck('employee')
            ->filter()
            ->unique('id')
            ->values();

        return new Collection($employees->map(fn (Employee $employee) => $this->scoreEmployeeForCutoff($cutoff, $employee)));
    }

    public function completeDtrStreak(Employee $employee, ?Carbon $throughDate = null, ?Carbon $sinceDate = null): int
    {
        $date = ($throughDate ?? today())->copy()->startOfDay();
        $floor = $sinceDate?->copy()->startOfDay() ?? $date->copy()->subDays(45);
        $trackingStart = $this->trackingStartDate($employee);
        $floor = $floor->max($trackingStart);
        $dtrs = $employee->dtrs()
            ->whereDate('date', '>=', $floor->toDateString())
            ->whereDate('date', '<=', $date->toDateString())
            ->get()
            ->keyBy(fn (Dtr $dtr) => $dtr->date->toDateString());

        $streak = 0;

        while ($date->gte($floor)) {
            $schedule = $this->scheduledWorkdayFor($employee, $date->toDateString());

            if (! $schedule['has_schedule']) {
                $date->subDay();

                continue;
            }

            $dtr = $dtrs->get($date->toDateString());
            if (! $dtr || ! $this->isCompleteDtr($dtr)) {
                break;
            }

            $streak++;
            $date->subDay();
        }

        return $streak;
    }

    private function scorePeriod(PayrollCutoff $cutoff, Employee $employee, Collection|\Illuminate\Support\Collection $dtrs): array
    {
        $totals = [
            'total_points' => 0,
            'complete_dtr_days' => 0,
            'on_time_days' => 0,
            'proper_time_out_days' => 0,
            'same_day_complete_days' => 0,
            'no_absent_days' => 0,
            'late_days' => 0,
            'approved_ot_days' => 0,
            'late_minutes' => 0,
        ];
        $items = [];
        $trackingStart = $this->trackingStartDate($employee);
        $scheduledWorkdays = 0;
        $absentDays = 0;

        foreach (CarbonPeriod::create($cutoff->start_date, $cutoff->end_date) as $date) {
            $workDate = $date->toDateString();

            if ($date->lt($trackingStart)) {
                continue;
            }

            $schedule = $this->scheduledWorkdayFor($employee, $workDate);

            if (! $schedule['has_schedule']) {
                continue;
            }

            $scheduledWorkdays++;

            $dtr = $dtrs->get($workDate);
            if (! $dtr || ! $dtr->time_in) {
                $absentDays++;
                $totals['total_points'] += GamificationService::PTS_PENALTY_ABSENT;
                $items[] = $this->item(null, $workDate, self::RULE_ABSENT_PENALTY, 'Absent scheduled workday', GamificationService::PTS_PENALTY_ABSENT, [
                    'schedule_source' => $schedule['source'],
                    'work_start_time' => $schedule['work_start_time'],
                ]);

                continue;
            }

            $lateMinutes = (int) $dtr->late_mins;
            $totals['late_minutes'] += $lateMinutes;

            if ($this->isCompleteDtr($dtr)) {
                $totals['complete_dtr_days']++;
                if ($this->wasCompletedPromptly($dtr)) {
                    $totals['total_points'] += GamificationService::PTS_SAME_DAY;
                    $totals['same_day_complete_days']++;
                    $items[] = $this->item($dtr->id, $workDate, self::RULE_SAME_DAY_COMPLETE, 'Same-day DTR filed', GamificationService::PTS_SAME_DAY, [
                        'required_events' => ['time_in', 'am_out', 'pm_in', 'time_out'],
                        'work_date' => $workDate,
                    ]);
                }
            }

            $totals['no_absent_days']++;

            if ($dtr->time_in && $lateMinutes === 0) {
                $totals['total_points'] += GamificationService::PTS_ON_TIME;
                $totals['on_time_days']++;
                $items[] = $this->item($dtr->id, $workDate, self::RULE_NO_LATE, 'On-time time-in', GamificationService::PTS_ON_TIME, [
                    'time_in' => $dtr->time_in,
                    'late_mins' => $lateMinutes,
                    'schedule_source' => $schedule['source'],
                    'work_start_time' => $schedule['work_start_time'],
                ]);
            }

            if ($dtr->time_in && $lateMinutes > 0) {
                $totals['total_points'] += GamificationService::PTS_PENALTY_LATE;
                $totals['late_days']++;
                $items[] = $this->item($dtr->id, $workDate, self::RULE_LATE_PENALTY, 'Late time-in', GamificationService::PTS_PENALTY_LATE, [
                    'time_in' => $dtr->time_in,
                    'late_mins' => $lateMinutes,
                    'schedule_source' => $schedule['source'],
                    'work_start_time' => $schedule['work_start_time'],
                ]);
            }

            if ($dtr->time_out) {
                $totals['proper_time_out_days']++;
            }
        }

        if ($scheduledWorkdays > 0 && $totals['late_days'] === 0 && $absentDays === 0) {
            $totals['total_points'] += GamificationService::PTS_PERFECT_CUTOFF;
            $items[] = $this->item(null, $cutoff->end_date->toDateString(), self::RULE_PERFECT_CUTOFF_BONUS, 'Perfect Cutoff bonus', GamificationService::PTS_PERFECT_CUTOFF, [
                'scheduled_workdays' => $scheduledWorkdays,
                'late_days' => $totals['late_days'],
                'absent_days' => $absentDays,
            ]);
        }

        return [
            'totals' => $totals,
            'items' => $items,
        ];
    }

    public function isCompleteDtr(Dtr $dtr): bool
    {
        return (bool) ($dtr->time_in && $dtr->am_out && $dtr->pm_in && $dtr->time_out);
    }

    public function wasCompletedPromptly(Dtr $dtr): bool
    {
        if (! $this->isCompleteDtr($dtr)) {
            return false;
        }

        $dtr->loadMissing('logEvents');

        foreach (['time_in', 'am_out', 'pm_in', 'time_out'] as $eventKey) {
            $allowNextDay = $eventKey === 'time_out' && $this->isOvernightDtr($dtr);

            $hasPromptEvent = $dtr->logEvents
                ->where('event_key', $eventKey)
                ->whereIn('source', ['staff_dashboard', 'staff_form'])
                ->contains(function ($event) use ($dtr, $allowNextDay) {
                    if (! $event->submitted_at) {
                        return false;
                    }

                    $submittedDate = $event->submitted_at->toDateString();
                    $workDate = $dtr->date->toDateString();

                    return $submittedDate === $workDate
                        || ($allowNextDay && $submittedDate === $dtr->date->copy()->addDay()->toDateString());
                });

            if (! $hasPromptEvent) {
                return false;
            }
        }

        return true;
    }

    private function isOvernightDtr(Dtr $dtr): bool
    {
        if (! $dtr->time_in || ! $dtr->time_out) {
            return false;
        }

        return Carbon::createFromTimeString($dtr->time_out)
            ->lte(Carbon::createFromTimeString($dtr->time_in));
    }

    public function scheduledWorkdayFor(Employee $employee, string $date): array
    {
        $cacheKey = $employee->id . ':' . $date;
        if (array_key_exists($cacheKey, $this->scheduleCache)) {
            return $this->scheduleCache[$cacheKey];
        }

        $dailySchedule = DailySchedule::where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        if ($dailySchedule) {
            return $this->scheduleCache[$cacheKey] = [
                'has_schedule' => ! $dailySchedule->is_day_off && (bool) $dailySchedule->work_start_time,
                'source' => 'daily_schedule',
                'work_start_time' => $dailySchedule->work_start_time,
            ];
        }

        $schedule = $employee->employeeSchedules()
            ->whereDate('week_start_date', '<=', $date)
            ->orderByDesc('week_start_date')
            ->first();

        if (! $schedule) {
            return $this->scheduleCache[$cacheKey] = [
                'has_schedule' => false,
                'source' => null,
                'work_start_time' => null,
            ];
        }

        $dayName = Carbon::parse($date)->format('l');
        $restDays = $schedule->rest_days ?? [];

        return $this->scheduleCache[$cacheKey] = [
            'has_schedule' => ! in_array($dayName, $restDays, true) && (bool) $schedule->work_start_time,
            'source' => 'employee_schedule',
            'work_start_time' => $schedule->work_start_time,
        ];
    }

    /**
     * Bulk-populate the schedule cache for many employees over a date range.
     * Call this before iterating many employees to avoid per-employee DB queries.
     */
    public function preloadSchedules(Collection $employees, string $fromDate, string $toDate): void
    {
        if ($employees->isEmpty()) {
            return;
        }

        $employeeIds = $employees->pluck('id')->all();

        // One query for all daily overrides in the date range
        $dailySchedules = DailySchedule::whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$fromDate, $toDate])
            ->get()
            ->groupBy('employee_id')
            ->map(fn ($group) => $group->keyBy(fn ($ds) => $ds->date->toDateString()));

        // One query for all recurring schedules (all rows needed for fallback resolution)
        $employeeSchedules = EmployeeSchedule::whereIn('employee_id', $employeeIds)
            ->orderByDesc('week_start_date')
            ->get()
            ->groupBy('employee_id');

        $period = CarbonPeriod::create($fromDate, $toDate);

        foreach ($employees as $employee) {
            $empDailySchedules = $dailySchedules->get($employee->id, collect());
            // Already ordered desc — first() gives the most recent schedule for any date
            $empSchedules = $employeeSchedules->get($employee->id, collect());

            foreach ($period as $date) {
                $dateStr = $date->toDateString();
                $cacheKey = $employee->id . ':' . $dateStr;

                if (array_key_exists($cacheKey, $this->scheduleCache)) {
                    continue;
                }

                $dailySchedule = $empDailySchedules->get($dateStr);

                if ($dailySchedule) {
                    $this->scheduleCache[$cacheKey] = [
                        'has_schedule' => ! $dailySchedule->is_day_off && (bool) $dailySchedule->work_start_time,
                        'source' => 'daily_schedule',
                        'work_start_time' => $dailySchedule->work_start_time,
                    ];
                    continue;
                }

                // Most recent EmployeeSchedule with week_start_date <= dateStr
                $schedule = $empSchedules->first(
                    fn ($s) => $s->week_start_date->toDateString() <= $dateStr
                );

                if (! $schedule) {
                    $this->scheduleCache[$cacheKey] = [
                        'has_schedule' => false,
                        'source' => null,
                        'work_start_time' => null,
                    ];
                    continue;
                }

                $dayName = $date->format('l');
                $restDays = $schedule->rest_days ?? [];

                $this->scheduleCache[$cacheKey] = [
                    'has_schedule' => ! in_array($dayName, $restDays, true) && (bool) $schedule->work_start_time,
                    'source' => 'employee_schedule',
                    'work_start_time' => $schedule->work_start_time,
                ];
            }
        }
    }

    private function item(?int $dtrId, ?string $workDate, string $ruleKey, string $description, int $points, ?array $metadata = null): array
    {
        return [
            'dtr_id' => $dtrId,
            'work_date' => $workDate,
            'rule_key' => $ruleKey,
            'description' => $description,
            'points' => $points,
            'metadata' => $metadata,
        ];
    }

    private function trackingStartDate(Employee $employee): Carbon
    {
        $employee->loadMissing('user');

        $launch = Carbon::parse(GamificationService::GAMIFICATION_LAUNCH_DATE)->startOfDay();

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
}
