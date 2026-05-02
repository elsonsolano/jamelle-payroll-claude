<?php

namespace Tests\Feature;

use App\Models\AttendanceScore;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\PayrollCutoff;
use App\Models\RankUpEvent;
use App\Models\User;
use App\Services\GamificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankUpEventTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-05-02 08:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_record_rank_up_creates_one_event_when_crossing_threshold(): void
    {
        $employee = $this->staffEmployee($this->branch(), 'Marcus', 'Santos', 'MARCUS');

        $service = app(GamificationService::class);
        $event = $service->recordRankUpIfNeeded($employee, 49, 50, 'test');

        $this->assertNotNull($event);
        $this->assertDatabaseHas('rank_up_events', [
            'employee_id' => $employee->id,
            'user_id' => $employee->user->id,
            'old_rank_number' => 1,
            'old_rank_name' => 'Empty Cup',
            'new_rank_number' => 2,
            'new_rank_name' => 'Bagong Swirl',
            'points' => 50,
            'source' => 'test',
        ]);

        $service->recordRankUpIfNeeded($employee, 49, 55, 'test_again');

        $this->assertSame(1, RankUpEvent::where('employee_id', $employee->id)->count());
    }

    public function test_staff_cannot_mark_another_employee_rank_up_event(): void
    {
        $branch = $this->branch();
        $owner = $this->staffEmployee($branch, 'Owner', 'Staff', 'OWNER');
        $other = $this->staffEmployee($branch, 'Other', 'Staff', 'OTHER');

        $event = RankUpEvent::create([
            'employee_id' => $owner->id,
            'user_id' => $owner->user->id,
            'old_rank_number' => 1,
            'old_rank_name' => 'Empty Cup',
            'new_rank_number' => 2,
            'new_rank_name' => 'Bagong Swirl',
            'points' => 50,
            'source' => 'test',
            'occurred_at' => now(),
        ]);

        $this->actingAs($other->user)
            ->postJson(route('staff.rank-up-events.seen', $event))
            ->assertForbidden();

        $this->assertNull($event->fresh()->seen_at);
    }

    public function test_dashboard_time_in_records_rank_up_event_when_points_cross_threshold(): void
    {
        $branch = $this->branch();
        $employee = $this->staffEmployee($branch, 'Marcus', 'Santos', 'MARCUS');

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'week_start_date' => '2026-05-01',
            'rest_days' => ['Sunday'],
            'work_start_time' => '08:00',
            'work_end_time' => '17:00',
        ]);

        $cutoff = PayrollCutoff::create([
            'branch_id' => $branch->id,
            'name' => 'Previous Cutoff',
            'start_date' => '2026-04-14',
            'end_date' => '2026-04-29',
            'status' => 'finalized',
            'finalized_at' => '2026-04-30 10:00:00',
        ]);

        AttendanceScore::create([
            'payroll_cutoff_id' => $cutoff->id,
            'employee_id' => $employee->id,
            'total_points' => 40,
            'on_time_days' => 4,
            'same_day_complete_days' => 0,
            'finalized_at' => $cutoff->finalized_at,
        ]);

        $this->actingAs($employee->user)
            ->postJson(route('staff.dtr.log-event'), [
                'date' => '2026-05-02',
                'event' => 'time_in',
                'time' => '08:00',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('celebration.points_earned', 10);

        $this->assertDatabaseHas('rank_up_events', [
            'employee_id' => $employee->id,
            'user_id' => $employee->user->id,
            'old_rank_name' => 'Empty Cup',
            'new_rank_name' => 'Bagong Swirl',
            'points' => 50,
            'source' => 'time_in',
        ]);
    }

    private function branch(): Branch
    {
        return Branch::create([
            'name' => 'Main',
            'address' => 'Test',
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
        ]);
    }

    private function staffEmployee(Branch $branch, string $firstName, string $lastName, string $code): Employee
    {
        $employee = Employee::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'employee_code' => $code,
            'branch_id' => $branch->id,
            'salary_type' => 'daily',
            'rate' => 500,
            'active' => true,
            'created_at' => '2026-05-02 07:00:00',
            'updated_at' => '2026-05-02 07:00:00',
        ]);

        User::factory()->create([
            'name' => $employee->full_name,
            'role' => 'staff',
            'employee_id' => $employee->id,
            'signature' => 'data:image/png;base64,test',
            'must_change_password' => false,
            'created_at' => '2026-05-02 07:00:00',
            'updated_at' => '2026-05-02 07:00:00',
        ]);

        return $employee->load('user');
    }
}
