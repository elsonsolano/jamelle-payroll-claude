<?php

namespace App\Services;

use App\Models\AttendanceBadge;
use App\Models\AttendanceScore;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeAttendanceBadge;
use App\Models\PayrollCutoff;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceBadgeService
{
    public const BADGE_ON_TIME_7 = 'on_time_7';
    public const BADGE_SAME_DAY_FINISHER = 'same_day_finisher';
    public const BADGE_NO_ABSENT_CUTOFF = 'no_absent_cutoff';

    public const DEFAULT_BADGES = [
        self::BADGE_ON_TIME_7 => [
            'name'        => 'No-Late 7',
            'description' => 'Had a 7 scheduled-workday streak with no late minutes.',
            'icon'        => 'calendar-check',
        ],
        self::BADGE_SAME_DAY_FINISHER => [
            'name'        => 'Same-Day Finisher',
            'description' => 'Completed every scheduled workday DTR on the same day during the cutoff.',
            'icon'        => 'sparkles',
        ],
        self::BADGE_NO_ABSENT_CUTOFF => [
            'name'        => 'No Absences',
            'description' => 'Had a DTR for every scheduled workday in the cutoff.',
            'icon'        => 'shield-check',
        ],
    ];

    private const RETIRED_BADGES = [
        'complete_cutoff',
        'reliable_closer',
        'ot_pro',
        'on_time_5',
    ];

    public function __construct(protected AttendanceScoringService $attendanceScoringService)
    {
    }

    public function ensureDefaultBadges(): Collection
    {
        $badges = new Collection();

        foreach (self::DEFAULT_BADGES as $key => $definition) {
            $badges->push(AttendanceBadge::updateOrCreate(
                ['key' => $key],
                $definition + ['active' => true],
            ));
        }

        $retiredBadgeIds = AttendanceBadge::whereIn('key', self::RETIRED_BADGES)->pluck('id');
        AttendanceBadge::whereIn('id', $retiredBadgeIds)->update(['active' => false]);

        if ($retiredBadgeIds->isNotEmpty()) {
            EmployeeAttendanceBadge::whereIn('attendance_badge_id', $retiredBadgeIds)->delete();
        }

        return $badges->keyBy('key');
    }

    public function awardBadgesForCutoff(PayrollCutoff $cutoff): Collection
    {
        $employees = $cutoff->payrollEntries()
            ->with('employee')
            ->get()
            ->pluck('employee')
            ->filter()
            ->unique('id')
            ->values();

        return new Collection($employees->flatMap(
            fn (Employee $employee) => $this->awardBadgesForEmployee($cutoff, $employee)
        )->values());
    }

    public function awardBadgesForEmployee(PayrollCutoff $cutoff, Employee $employee): Collection
    {
        $badges = $this->ensureDefaultBadges();

        $score = AttendanceScore::where('payroll_cutoff_id', $cutoff->id)
            ->where('employee_id', $employee->id)
            ->with('items')
            ->first();

        if (! $score || $cutoff->status !== 'finalized') {
            return new Collection();
        }

        $dtrs = $employee->dtrs()
            ->whereDate('date', '>=', $cutoff->start_date->toDateString())
            ->whereDate('date', '<=', $cutoff->end_date->toDateString())
            ->orderBy('date')
            ->get()
            ->keyBy(fn (Dtr $dtr) => $dtr->date->toDateString());

        $qualified = $this->qualifiedBadges($cutoff, $employee, $score, $dtrs);

        return DB::transaction(function () use ($employee, $cutoff, $score, $badges, $qualified) {
            $awards = new Collection();
            $qualifiedKeys = array_keys($qualified);
            $qualifiedBadgeIds = collect($qualifiedKeys)
                ->map(fn (string $key) => $badges->get($key)?->id)
                ->filter()
                ->values();

            EmployeeAttendanceBadge::where('employee_id', $employee->id)
                ->where('payroll_cutoff_id', $cutoff->id)
                ->whereIn('attendance_badge_id', $badges->pluck('id'))
                ->whereNotIn('attendance_badge_id', $qualifiedBadgeIds)
                ->delete();

            foreach ($qualified as $badgeKey => $metadata) {
                $badge = $badges->get($badgeKey);
                if (! $badge?->active) {
                    continue;
                }

                $awards->push(EmployeeAttendanceBadge::updateOrCreate(
                    [
                        'employee_id'          => $employee->id,
                        'attendance_badge_id'  => $badge->id,
                        'payroll_cutoff_id'    => $cutoff->id,
                    ],
                    [
                        'attendance_score_id' => $score->id,
                        'awarded_at'          => $cutoff->finalized_at ?? now(),
                        'metadata'            => $metadata,
                    ],
                )->load('badge'));
            }

            return $awards;
        });
    }

    private function qualifiedBadges(PayrollCutoff $cutoff, Employee $employee, AttendanceScore $score, \Illuminate\Support\Collection $dtrs): array
    {
        $scheduledDates = $this->scheduledDates($cutoff, $employee);
        $qualified = [];

        $onTimeStreak = $this->onTimeStreak($cutoff, $employee, $dtrs);
        if ($onTimeStreak['max_streak'] >= 7) {
            $qualified[self::BADGE_ON_TIME_7] = $onTimeStreak;
        }

        if ($scheduledDates->isNotEmpty() && $score->same_day_complete_days >= $scheduledDates->count()) {
            $qualified[self::BADGE_SAME_DAY_FINISHER] = [
                'scheduled_workdays'       => $scheduledDates->count(),
                'same_day_complete_days'   => $score->same_day_complete_days,
                'date_range'               => $this->dateRangeMetadata($scheduledDates),
            ];
        }

        if ($scheduledDates->isNotEmpty() && $score->no_absent_days >= $scheduledDates->count()) {
            $qualified[self::BADGE_NO_ABSENT_CUTOFF] = [
                'scheduled_workdays' => $scheduledDates->count(),
                'no_absent_days'     => $score->no_absent_days,
                'date_range'         => $this->dateRangeMetadata($scheduledDates),
            ];
        }

        return $qualified;
    }

    private function scheduledDates(PayrollCutoff $cutoff, Employee $employee): \Illuminate\Support\Collection
    {
        return collect(CarbonPeriod::create($cutoff->start_date, $cutoff->end_date))
            ->map(fn ($date) => $date->toDateString())
            ->filter(fn (string $date) => $this->attendanceScoringService->scheduledWorkdayFor($employee, $date)['has_schedule'])
            ->values();
    }

    private function onTimeStreak(PayrollCutoff $cutoff, Employee $employee, \Illuminate\Support\Collection $dtrs): array
    {
        $current = 0;
        $currentStart = null;
        $best = [
            'max_streak' => 0,
            'start_date' => null,
            'end_date'   => null,
        ];

        foreach (CarbonPeriod::create($cutoff->start_date, $cutoff->end_date) as $date) {
            $dateString = $date->toDateString();
            $schedule = $this->attendanceScoringService->scheduledWorkdayFor($employee, $dateString);

            if (! $schedule['has_schedule']) {
                continue;
            }

            $dtr = $dtrs->get($dateString);
            if ($dtr && $dtr->time_in && (int) $dtr->late_mins === 0) {
                $currentStart ??= $dateString;
                $current++;

                if ($current > $best['max_streak']) {
                    $best = [
                        'max_streak' => $current,
                        'start_date' => $currentStart,
                        'end_date'   => $dateString,
                    ];
                }

                continue;
            }

            $current = 0;
            $currentStart = null;
        }

        return $best + ['required_streak' => 7];
    }

    private function dateRangeMetadata(\Illuminate\Support\Collection $dates): array
    {
        return [
            'start_date' => $dates->first(),
            'end_date'   => $dates->last(),
        ];
    }
}
