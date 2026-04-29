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
use App\Models\PayrollCutoff;
use App\Models\PayrollEntry;
use App\Services\AttendanceBadgeService;
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
            'created_at' => '2026-03-31 10:00:00',
            'updated_at' => '2026-03-31 10:00:00',
        ])->save();

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'week_start_date' => '2026-04-01',
            'rest_days' => ['Sunday'],
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
        ]);

        DailySchedule::create([
            'employee_id' => $employee->id,
            'date' => '2026-04-02',
            'work_start_time' => '10:00',
            'work_end_time' => '19:00',
            'is_day_off' => false,
        ]);

        $cutoff = PayrollCutoff::create([
            'branch_id' => $branch->id,
            'name' => 'April 1st',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-03',
            'status' => 'finalized',
            'finalized_at' => '2026-04-04 10:00:00',
        ]);

        PayrollEntry::create([
            'payroll_cutoff_id' => $cutoff->id,
            'employee_id' => $employee->id,
        ]);

        $firstDtr = Dtr::create([
            'employee_id' => $employee->id,
            'date' => '2026-04-01',
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
                'work_date' => '2026-04-01',
                'event_key' => $eventKey,
                'logged_time' => $loggedTime,
                'submitted_at' => '2026-04-01 18:05:00',
                'source' => 'staff_dashboard',
            ]);
        }

        Dtr::create([
            'employee_id' => $employee->id,
            'date' => '2026-04-02',
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
            'date' => '2026-04-03',
            'time_in' => '09:15',
            'time_out' => '18:00',
            'late_mins' => 15,
        ]);

        $service = app(AttendanceScoringService::class);

        $estimate = $service->estimateEmployeeForCutoff($cutoff, $employee);

        $this->assertSame(39, $estimate['totals']['total_points']);
        $this->assertSame(0, $estimate['totals']['approved_ot_days']);
        $this->assertSame(1, $estimate['totals']['same_day_complete_days']);
        $this->assertSame(3, $estimate['totals']['no_absent_days']);
        $this->assertSame(1, $estimate['totals']['late_days']);
        $this->assertCount(7, $estimate['items']);
        $this->assertDatabaseCount('attendance_scores', 0);
        $this->assertDatabaseCount('attendance_score_items', 0);
        $this->assertSame(2, $service->completeDtrStreak(
            $employee,
            Carbon::parse('2026-04-02'),
            Carbon::parse('2026-04-01'),
        ));

        $score = $service->scoreEmployeeForCutoff($cutoff, $employee);

        $this->assertSame(39, $score->total_points);
        $this->assertSame(2, $score->complete_dtr_days);
        $this->assertSame(2, $score->on_time_days);
        $this->assertSame(3, $score->proper_time_out_days);
        $this->assertSame(1, $score->same_day_complete_days);
        $this->assertSame(3, $score->no_absent_days);
        $this->assertSame(0, $score->approved_ot_days);
        $this->assertSame(1, $score->late_days);
        $this->assertSame(15, $score->late_minutes);
        $this->assertCount(7, $score->items);

        AttendanceScoreItem::create([
            'attendance_score_id' => $score->id,
            'rule_key' => 'stale_item',
            'description' => 'Stale item',
            'points' => 99,
        ]);

        $rescored = $service->scoreEmployeeForCutoff($cutoff, $employee);

        $this->assertSame($score->id, $rescored->id);
        $this->assertSame(39, $rescored->total_points);
        $this->assertCount(7, $rescored->items);
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
}
