<?php

namespace App\Services;

use App\Models\AttendanceScore;
use App\Models\DailySchedule;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\PayrollCutoff;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceScoringService
{
    public const RULE_NO_LATE = 'no_late_reward';
    public const RULE_NO_ABSENT = 'no_absent_reward';
    public const RULE_SAME_DAY_COMPLETE = 'same_day_complete_dtr';
    public const RULE_LATE_PENALTY = 'late_penalty';

    public function scoreEmployeeForCutoff(PayrollCutoff $cutoff, Employee $employee): AttendanceScore
    {
        return DB::transaction(function () use ($cutoff, $employee) {
            $result = $this->estimateEmployeeForCutoff($cutoff, $employee);

            $score = AttendanceScore::firstOrNew([
                'payroll_cutoff_id' => $cutoff->id,
                'employee_id'        => $employee->id,
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
            ->whereBetween('date', [
                $cutoff->start_date->toDateString(),
                $cutoff->end_date->toDateString(),
            ])
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
        $dtrs = $employee->dtrs()
            ->whereBetween('date', [$floor->toDateString(), $date->toDateString()])
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
            'total_points'           => 0,
            'complete_dtr_days'      => 0,
            'on_time_days'           => 0,
            'proper_time_out_days'   => 0,
            'same_day_complete_days' => 0,
            'no_absent_days'         => 0,
            'late_days'              => 0,
            'approved_ot_days'       => 0,
            'late_minutes'           => 0,
        ];
        $items = [];

        foreach (CarbonPeriod::create($cutoff->start_date, $cutoff->end_date) as $date) {
            $workDate = $date->toDateString();
            $schedule = $this->scheduledWorkdayFor($employee, $workDate);

            if (! $schedule['has_schedule']) {
                continue;
            }

            $dtr = $dtrs->get($workDate);
            if (! $dtr) {
                continue;
            }

            $lateMinutes = (int) $dtr->late_mins;
            $totals['late_minutes'] += $lateMinutes;

            if ($this->isCompleteDtr($dtr)) {
                $totals['complete_dtr_days']++;
                if ($this->wasCompletedPromptly($dtr)) {
                    $totals['total_points'] += 10;
                    $totals['same_day_complete_days']++;
                    $items[] = $this->item($dtr->id, $workDate, self::RULE_SAME_DAY_COMPLETE, 'Same-day complete DTR', 10, [
                        'required_events' => ['time_in', 'am_out', 'pm_in', 'time_out'],
                        'work_date'       => $workDate,
                    ]);
                }
            }

            if ($dtr->time_in) {
                $totals['total_points'] += 8;
                $totals['no_absent_days']++;
                $items[] = $this->item($dtr->id, $workDate, self::RULE_NO_ABSENT, 'No absent scheduled day', 8, [
                    'time_in'         => $dtr->time_in,
                    'schedule_source' => $schedule['source'],
                ]);
            }

            if ($dtr->time_in && $lateMinutes === 0) {
                $totals['total_points'] += 5;
                $totals['on_time_days']++;
                $items[] = $this->item($dtr->id, $workDate, self::RULE_NO_LATE, 'No late reward', 5, [
                    'time_in'         => $dtr->time_in,
                    'late_mins'       => $lateMinutes,
                    'schedule_source' => $schedule['source'],
                    'work_start_time' => $schedule['work_start_time'],
                ]);
            }

            if ($dtr->time_in && $lateMinutes > 0) {
                $totals['total_points'] -= 5;
                $totals['late_days']++;
                $items[] = $this->item($dtr->id, $workDate, self::RULE_LATE_PENALTY, 'Late penalty', -5, [
                    'time_in'         => $dtr->time_in,
                    'late_mins'       => $lateMinutes,
                    'schedule_source' => $schedule['source'],
                    'work_start_time' => $schedule['work_start_time'],
                ]);
            }

            if ($dtr->time_out) {
                $totals['proper_time_out_days']++;
            }
        }

        return [
            'totals' => $totals,
            'items'  => $items,
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
        $dailySchedule = DailySchedule::where('employee_id', $employee->id)
            ->where('date', $date)
            ->first();

        if ($dailySchedule) {
            return [
                'has_schedule'    => ! $dailySchedule->is_day_off && (bool) $dailySchedule->work_start_time,
                'source'          => 'daily_schedule',
                'work_start_time' => $dailySchedule->work_start_time,
            ];
        }

        $schedule = $employee->employeeSchedules()
            ->where('week_start_date', '<=', $date)
            ->orderByDesc('week_start_date')
            ->first();

        if (! $schedule) {
            return [
                'has_schedule'    => false,
                'source'          => null,
                'work_start_time' => null,
            ];
        }

        $dayName = Carbon::parse($date)->format('l');
        $restDays = $schedule->rest_days ?? [];

        return [
            'has_schedule'    => ! in_array($dayName, $restDays, true) && (bool) $schedule->work_start_time,
            'source'          => 'employee_schedule',
            'work_start_time' => $schedule->work_start_time,
        ];
    }

    private function item(?int $dtrId, ?string $workDate, string $ruleKey, string $description, int $points, ?array $metadata = null): array
    {
        return [
            'dtr_id'      => $dtrId,
            'work_date'   => $workDate,
            'rule_key'    => $ruleKey,
            'description' => $description,
            'points'      => $points,
            'metadata'    => $metadata,
        ];
    }
}
