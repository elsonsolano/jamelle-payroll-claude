<?php

namespace Tests\Feature;

use App\Models\AttendanceScoreItem;
use App\Models\Branch;
use App\Models\DailySchedule;
use App\Models\Dtr;
use App\Models\DtrLogEvent;
use App\Models\Employee;
use App\Models\EmployeeAttendanceBadge;
use App\Models\EmployeeSchedule;
use App\Models\Holiday;
use App\Models\PayrollCutoff;
use App\Models\PayrollEntry;
use App\Services\AttendanceBadgeService;
use App\Services\AttendanceRecalculationService;
use App\Services\AttendanceScoringService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceScoringServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_scores_employee_attendance_for_a_cutoff_and_rebuilds_items_idempotently(): void
    {
        $branch = Branch::create([
            'name' => 'Main',
            'address' => 'Test',
            'work_start_time' => '06:00',
            'work_end_time' => '14:00',
        ]);

        $employee = Employee::create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'employee_code' => 'EMP-001',
            'branch_id' => $branch->id,
            'salary_type' => 'daily',
            'rate' => 500,
            'active' => true,
        ]);
        $employee->forceFill([
            'created_at' => '2026-04-30 10:00:00',
            'updated_at' => '2026-04-30 10:00:00',
        ])->save();

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'week_start_date' => '2026-04-27',
            'rest_days' => ['Sunday'],
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
        ]);

        DailySchedule::create([
            'employee_id' => $employee->id,
            'date' => '2026-05-06',
            'work_start_time' => '10:00',
            'work_end_time' => '19:00',
            'is_day_off' => false,
        ]);

        $cutoff = PayrollCutoff::create([
            'branch_id' => $branch->id,
            'name' => 'May 1st',
            'start_date' => '2026-05-05',
            'end_date' => '2026-05-07',
            'status' => 'finalized',
            'finalized_at' => '2026-05-08 10:00:00',
        ]);

        PayrollEntry::create([
            'payroll_cutoff_id' => $cutoff->id,
            'employee_id' => $employee->id,
        ]);

        $firstDtr = Dtr::create([
            'employee_id' => $employee->id,
            'date' => '2026-05-05',
            'time_in' => '09:00',
            'am_out' => '12:00',
            'pm_in' => '13:00',
            'time_out' => '18:00',
            'late_mins' => 0,
            'overtime_hours' => 1,
            'ot_status' => 'approved',
        ]);

        foreach (['time_in' => '09:00', 'am_out' => '12:00', 'pm_in' => '13:00', 'time_out' => '18:00'] as $eventKey => $loggedTime) {
            DtrLogEvent::create([
                'dtr_id' => $firstDtr->id,
                'employee_id' => $employee->id,
                'work_date' => '2026-05-05',
                'event_key' => $eventKey,
                'logged_time' => $loggedTime,
                'submitted_at' => '2026-05-05 18:05:00',
                'source' => 'staff_dashboard',
            ]);
        }

        Dtr::create([
            'employee_id' => $employee->id,
            'date' => '2026-05-06',
            'time_in' => '09:30',
            'am_out' => '12:00',
            'pm_in' => '13:00',
            'time_out' => '19:00',
            'late_mins' => 0,
            'overtime_hours' => 0,
            'ot_status' => 'none',
        ]);

        Dtr::create([
            'employee_id' => $employee->id,
            'date' => '2026-05-07',
            'time_in' => '09:15',
            'time_out' => '18:00',
            'late_mins' => 15,
        ]);

        $service = app(AttendanceScoringService::class);

        $estimate = $service->estimateEmployeeForCutoff($cutoff, $employee);

        $this->assertSame(20, $estimate['totals']['total_points']);
        $this->assertSame(0, $estimate['totals']['approved_ot_days']);
        $this->assertSame(1, $estimate['totals']['same_day_complete_days']);
        $this->assertSame(3, $estimate['totals']['no_absent_days']);
        $this->assertSame(1, $estimate['totals']['late_days']);
        $this->assertCount(4, $estimate['items']);
        $this->assertDatabaseCount('attendance_scores', 0);
        $this->assertDatabaseCount('attendance_score_items', 0);
        $this->assertSame(2, $service->completeDtrStreak(
            $employee,
            Carbon::parse('2026-05-06'),
            Carbon::parse('2026-05-05'),
        ));

        $score = $service->scoreEmployeeForCutoff($cutoff, $employee);

        $this->assertSame(20, $score->total_points);
        $this->assertSame(2, $score->complete_dtr_days);
        $this->assertSame(2, $score->on_time_days);
        $this->assertSame(3, $score->proper_time_out_days);
        $this->assertSame(1, $score->same_day_complete_days);
        $this->assertSame(3, $score->no_absent_days);
        $this->assertSame(0, $score->approved_ot_days);
        $this->assertSame(1, $score->late_days);
        $this->assertSame(15, $score->late_minutes);
        $this->assertCount(4, $score->items);

        AttendanceScoreItem::create([
            'attendance_score_id' => $score->id,
            'rule_key' => 'stale_item',
            'description' => 'Stale item',
            'points' => 99,
        ]);

        $rescored = $service->scoreEmployeeForCutoff($cutoff, $employee);

        $this->assertSame($score->id, $rescored->id);
        $this->assertSame(20, $rescored->total_points);
        $this->assertCount(4, $rescored->items);
        $this->assertDatabaseMissing('attendance_score_items', [
            'attendance_score_id' => $score->id,
            'rule_key' => 'stale_item',
        ]);

        $badgeService = app(AttendanceBadgeService::class);
        $awards = $badgeService->awardBadgesForEmployee($cutoff, $employee);

        $this->assertCount(1, $awards);
        $this->assertSame('no_absent_cutoff', $awards->first()->badge->key);

        $badgeService->awardBadgesForEmployee($cutoff, $employee);

        $this->assertSame(1, EmployeeAttendanceBadge::where('employee_id', $employee->id)->count());
    }

    public function test_perfect_cutoff_bonus_and_no_late_7_badge_stack_with_no_absences(): void
    {
        $branch = Branch::create([
            'name' => 'Main',
            'address' => 'Test',
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
        ]);

        $employee = Employee::create([
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'employee_code' => 'EMP-002',
            'branch_id' => $branch->id,
            'salary_type' => 'daily',
            'rate' => 500,
            'active' => true,
        ]);
        $employee->forceFill([
            'created_at' => '2026-04-30 10:00:00',
            'updated_at' => '2026-04-30 10:00:00',
        ])->save();

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'week_start_date' => '2026-05-04',
            'rest_days' => [],
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
        ]);

        $cutoff = PayrollCutoff::create([
            'branch_id' => $branch->id,
            'name' => 'Perfect May',
            'start_date' => '2026-05-04',
            'end_date' => '2026-05-10',
            'status' => 'finalized',
            'finalized_at' => '2026-05-12 10:00:00',
        ]);

        PayrollEntry::create([
            'payroll_cutoff_id' => $cutoff->id,
            'employee_id' => $employee->id,
        ]);

        foreach (Carbon::parse('2026-05-04')->daysUntil('2026-05-10') as $date) {
            $dtr = Dtr::create([
                'employee_id' => $employee->id,
                'date' => $date->toDateString(),
                'time_in' => '09:00',
                'am_out' => '12:00',
                'pm_in' => '13:00',
                'time_out' => '18:00',
                'late_mins' => 0,
                'overtime_hours' => 0,
                'ot_status' => 'none',
            ]);

            foreach (['time_in' => '09:00', 'am_out' => '12:00', 'pm_in' => '13:00', 'time_out' => '18:00'] as $eventKey => $loggedTime) {
                DtrLogEvent::create([
                    'dtr_id' => $dtr->id,
                    'employee_id' => $employee->id,
                    'work_date' => $date->toDateString(),
                    'event_key' => $eventKey,
                    'logged_time' => $loggedTime,
                    'submitted_at' => $date->toDateString().' 18:05:00',
                    'source' => 'staff_dashboard',
                ]);
            }
        }

        $score = app(AttendanceScoringService::class)->scoreEmployeeForCutoff($cutoff, $employee);

        $this->assertSame(180, $score->total_points);
        $this->assertSame(7, $score->on_time_days);
        $this->assertSame(7, $score->same_day_complete_days);
        $this->assertSame(7, $score->no_absent_days);
        $this->assertSame(0, $score->late_days);
        $this->assertCount(15, $score->items);
        $this->assertTrue($score->items->contains('rule_key', AttendanceScoringService::RULE_PERFECT_CUTOFF_BONUS));

        $awards = app(AttendanceBadgeService::class)->awardBadgesForEmployee($cutoff, $employee);

        $this->assertEqualsCanonicalizing(
            ['no_absent_cutoff', 'on_time_7', 'same_day_finisher'],
            $awards->pluck('badge.key')->all(),
        );
    }

    public function test_pre_launch_cutoff_does_not_award_attendance_badges(): void
    {
        $branch = Branch::create([
            'name' => 'Main',
            'address' => 'Test',
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
        ]);

        $employee = Employee::create([
            'first_name' => 'Flora',
            'last_name' => 'Prelaunch',
            'employee_code' => 'EMP-PRE',
            'branch_id' => $branch->id,
            'salary_type' => 'daily',
            'rate' => 500,
            'active' => true,
        ]);
        $employee->forceFill([
            'created_at' => '2026-04-01 10:00:00',
            'updated_at' => '2026-04-01 10:00:00',
        ])->save();

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'week_start_date' => '2026-04-01',
            'rest_days' => [],
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
        ]);

        $cutoff = PayrollCutoff::create([
            'branch_id' => $branch->id,
            'name' => 'Prelaunch April',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-13',
            'status' => 'finalized',
            'finalized_at' => '2026-04-15 10:00:00',
        ]);

        PayrollEntry::create([
            'payroll_cutoff_id' => $cutoff->id,
            'employee_id' => $employee->id,
        ]);

        foreach (Carbon::parse('2026-04-01')->daysUntil('2026-04-07') as $date) {
            Dtr::create([
                'employee_id' => $employee->id,
                'date' => $date->toDateString(),
                'time_in' => '09:00',
                'am_out' => '12:00',
                'pm_in' => '13:00',
                'time_out' => '18:00',
                'late_mins' => 0,
                'overtime_hours' => 0,
                'ot_status' => 'none',
            ]);
        }

        $score = app(AttendanceScoringService::class)->scoreEmployeeForCutoff($cutoff, $employee);

        $this->assertSame(0, $score->total_points);
        $this->assertSame(0, $score->on_time_days);

        $badgeService = app(AttendanceBadgeService::class);
        $badgeService->ensureDefaultBadges();

        $badge = \App\Models\AttendanceBadge::where('key', AttendanceBadgeService::BADGE_ON_TIME_7)->firstOrFail();
        EmployeeAttendanceBadge::create([
            'employee_id' => $employee->id,
            'attendance_badge_id' => $badge->id,
            'payroll_cutoff_id' => $cutoff->id,
            'attendance_score_id' => $score->id,
            'awarded_at' => '2026-04-15 10:00:00',
            'metadata' => ['start_date' => '2026-04-01', 'end_date' => '2026-04-07'],
        ]);

        $awards = $badgeService->awardBadgesForEmployee($cutoff, $employee);

        $this->assertCount(0, $awards);
        $this->assertDatabaseMissing('employee_attendance_badges', [
            'employee_id' => $employee->id,
            'payroll_cutoff_id' => $cutoff->id,
        ]);
    }

    public function test_holidays_without_dtr_are_exempt_but_worked_holidays_are_scored_normally(): void
    {
        $branch = Branch::create([
            'name' => 'Main',
            'address' => 'Test',
            'work_start_time' => '08:00',
            'work_end_time' => '17:00',
        ]);

        $employee = Employee::create([
            'first_name' => 'Katherine',
            'last_name' => 'Johnson',
            'employee_code' => 'EMP-003',
            'branch_id' => $branch->id,
            'salary_type' => 'daily',
            'rate' => 500,
            'active' => true,
        ]);
        $employee->forceFill([
            'created_at' => '2026-04-30 10:00:00',
            'updated_at' => '2026-04-30 10:00:00',
        ])->save();

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'week_start_date' => '2026-05-04',
            'rest_days' => ['Sunday'],
            'work_start_time' => '08:00',
            'work_end_time' => '17:00',
        ]);

        Holiday::create([
            'date' => '2026-05-06',
            'name' => 'No Work Holiday',
            'type' => 'regular',
        ]);
        Holiday::create([
            'date' => '2026-05-07',
            'name' => 'Worked Holiday',
            'type' => 'regular',
        ]);

        $cutoff = PayrollCutoff::create([
            'branch_id' => $branch->id,
            'name' => 'Holiday May',
            'start_date' => '2026-05-05',
            'end_date' => '2026-05-07',
            'status' => 'finalized',
            'finalized_at' => '2026-05-08 10:00:00',
        ]);

        PayrollEntry::create([
            'payroll_cutoff_id' => $cutoff->id,
            'employee_id' => $employee->id,
        ]);

        Dtr::create([
            'employee_id' => $employee->id,
            'date' => '2026-05-05',
            'time_in' => '08:00',
            'am_out' => '12:00',
            'pm_in' => '13:00',
            'time_out' => '17:00',
            'late_mins' => 0,
        ]);

        Dtr::create([
            'employee_id' => $employee->id,
            'date' => '2026-05-07',
            'time_in' => '08:15',
            'am_out' => '12:00',
            'pm_in' => '13:00',
            'time_out' => '17:00',
            'late_mins' => 15,
        ]);

        $score = app(AttendanceScoringService::class)->scoreEmployeeForCutoff($cutoff, $employee);

        $this->assertSame(5, $score->total_points);
        $this->assertSame(2, $score->no_absent_days);
        $this->assertSame(1, $score->on_time_days);
        $this->assertSame(1, $score->late_days);
        $this->assertFalse($score->items->contains('rule_key', AttendanceScoringService::RULE_ABSENT_PENALTY));
        $this->assertTrue($score->items->contains(
            fn ($item) => $item->work_date->toDateString() === '2026-05-07'
                && $item->rule_key === AttendanceScoringService::RULE_LATE_PENALTY
                && $item->metadata['schedule_source'] === 'employee_schedule'
        ));
    }

    public function test_schedule_correction_recomputes_dtr_and_finalized_gamification_score(): void
    {
        $branch = Branch::create([
            'name' => 'Main',
            'address' => 'Test',
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
        ]);

        $employee = Employee::create([
            'first_name' => 'Maya',
            'last_name' => 'Santos',
            'employee_code' => 'EMP-004',
            'branch_id' => $branch->id,
            'salary_type' => 'daily',
            'rate' => 500,
            'active' => true,
        ]);
        $employee->forceFill([
            'created_at' => '2026-04-30 10:00:00',
            'updated_at' => '2026-04-30 10:00:00',
        ])->save();

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'week_start_date' => '2026-05-01',
            'rest_days' => [],
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
        ]);

        $cutoff = PayrollCutoff::create([
            'branch_id' => $branch->id,
            'name' => 'Corrected Schedule',
            'start_date' => '2026-05-01',
            'end_date' => '2026-05-01',
            'status' => 'finalized',
            'finalized_at' => '2026-05-02 10:00:00',
        ]);

        PayrollEntry::create([
            'payroll_cutoff_id' => $cutoff->id,
            'employee_id' => $employee->id,
        ]);

        $dtr = Dtr::create([
            'employee_id' => $employee->id,
            'date' => '2026-05-01',
            'time_in' => '12:35',
            'am_out' => '16:00',
            'pm_in' => '17:00',
            'time_out' => '21:00',
            'total_hours' => 7.4167,
            'late_mins' => 215,
            'undertime_mins' => 0,
            'overtime_hours' => 0,
            'ot_status' => 'none',
            'is_rest_day' => false,
        ]);

        $score = app(AttendanceScoringService::class)->scoreEmployeeForCutoff($cutoff, $employee);

        $this->assertSame(-5, $score->total_points);
        $this->assertSame(1, $score->late_days);
        $this->assertTrue($score->items->contains('rule_key', AttendanceScoringService::RULE_LATE_PENALTY));

        DailySchedule::create([
            'employee_id' => $employee->id,
            'date' => '2026-05-01',
            'work_start_time' => '13:00',
            'work_end_time' => '22:00',
            'is_day_off' => false,
        ]);

        app(AttendanceRecalculationService::class)
            ->recomputeDtrAndRefreshGamification($employee, '2026-05-01');

        $dtr->refresh();
        $score->refresh()->load('items');

        $this->assertSame(0, (int) $dtr->late_mins);
        $this->assertSame(85, $score->total_points);
        $this->assertSame(1, $score->on_time_days);
        $this->assertSame(0, $score->late_days);
        $this->assertFalse($score->items->contains('rule_key', AttendanceScoringService::RULE_LATE_PENALTY));
        $this->assertTrue($score->items->contains('rule_key', AttendanceScoringService::RULE_NO_LATE));
        $this->assertTrue($score->items->contains('rule_key', AttendanceScoringService::RULE_PERFECT_CUTOFF_BONUS));
    }
}
