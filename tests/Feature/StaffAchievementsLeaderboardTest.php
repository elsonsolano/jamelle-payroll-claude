<?php

namespace Tests\Feature;

use App\Models\AttendanceScore;
use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\PayrollCutoff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StaffAchievementsLeaderboardTest extends TestCase
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

    public function test_staff_can_view_company_wide_leaderboard_on_achievements_page(): void
    {
        $branch = $this->branch('Main');
        $cutoff = $this->finalizedCutoff($branch);

        $viewer = $this->staffEmployee($branch, 'Viewer', 'Person', 'VIEWER');
        $leader = $this->staffEmployee($branch, 'Ada', 'Lovelace', 'LEADER');
        $this->staffEmployee($branch, 'Grace', 'Hopper', 'GRACE');

        $this->score($cutoff, $viewer, 40);
        $this->score($cutoff, $leader, 90);

        $response = $this->actingAs($viewer->user)->get(route('staff.achievements'));

        $response->assertOk();
        $response->assertSee('Leaderboard');
        $response->assertSee('Ada');
        $response->assertSee('Main');
        $response->assertSee('90');
        $response->assertSee('Bagong Swirl');
    }

    public function test_local_preview_query_bypasses_achievements_launch_countdown(): void
    {
        Carbon::setTestNow('2026-04-30 08:00:00');

        $branch = $this->branch('Main');
        $cutoff = $this->finalizedCutoff($branch);

        $viewer = $this->staffEmployee($branch, 'Viewer', 'Person', 'VIEWER');
        $this->score($cutoff, $viewer, 40);

        $response = $this->actingAs($viewer->user)->get(route('staff.achievements', ['preview' => 1]));

        $response->assertOk();
        $response->assertSee('Leaderboard');
        $response->assertSee('Top 10 Staff');
        $response->assertSee('40');
        $response->assertDontSee('Go Live Tomorrow');
    }

    public function test_current_time_in_points_are_net_of_live_absence_penalties(): void
    {
        // May 1 is the gamification launch date. Schedule from May 1 gives 3 absent days
        // (May 1 Fri, May 2 Sat, May 4 Mon) before an on-time May 5, netting -14 points.
        Carbon::setTestNow('2026-05-05 08:05:00');

        $branch = $this->branch('Main');
        $viewer = $this->staffEmployee($branch, 'Viewer', 'Person', 'VIEWER');
        $viewer->forceFill([
            'created_at' => '2026-05-01 08:00:00',
            'updated_at' => '2026-05-01 08:00:00',
        ])->save();
        $viewer->user->forceFill([
            'created_at' => '2026-05-01 08:00:00',
            'updated_at' => '2026-05-01 08:00:00',
        ])->save();

        EmployeeSchedule::create([
            'employee_id' => $viewer->id,
            'week_start_date' => '2026-05-01',
            'rest_days' => ['Sunday'],
            'work_start_time' => '08:00',
            'work_end_time' => '17:00',
        ]);

        Dtr::create([
            'employee_id' => $viewer->id,
            'date' => '2026-05-05',
            'time_in' => '08:00',
            'source' => 'manual',
            'status' => 'Pending',
            'late_mins' => 0,
            'undertime_mins' => 0,
            'total_hours' => 0,
            'overtime_hours' => 0,
            'is_rest_day' => false,
            'ot_status' => 'none',
        ]);

        $response = $this->actingAs($viewer->user)->get(route('staff.achievements'));

        $response->assertOk();
        $response->assertSee('-14');
        $response->assertSee('64 pts to Bagong Swirl');

        Carbon::setTestNow();
    }

    public function test_leaderboard_only_includes_active_employees_with_staff_accounts(): void
    {
        $branch = $this->branch('Main');
        $cutoff = $this->finalizedCutoff($branch);

        $viewer = $this->staffEmployee($branch, 'Viewer', 'Person', 'VIEWER');
        $activeStaff = $this->staffEmployee($branch, 'Active', 'Staff', 'ACTIVE');
        $inactiveStaff = $this->staffEmployee($branch, 'Inactive', 'Staff', 'INACTIVE', active: false);
        $adminLinkedEmployee = $this->employee($branch, 'Admin', 'Linked', 'ADMINLINK');

        User::factory()->create([
            'name' => 'Admin Linked',
            'role' => 'admin',
            'employee_id' => $adminLinkedEmployee->id,
            'must_change_password' => false,
        ]);

        $this->score($cutoff, $viewer, 10);
        $this->score($cutoff, $activeStaff, 90);
        $this->score($cutoff, $inactiveStaff, 200);
        $this->score($cutoff, $adminLinkedEmployee, 300);

        $response = $this->actingAs($viewer->user)->get(route('staff.achievements'));

        $response->assertOk();
        $response->assertDontSee('Inactive Staff');
        $response->assertDontSee('Admin Linked');

        $searchResponse = $this->actingAs($viewer->user)->getJson(route('staff.achievements.search', ['q' => 'Staff']));

        $searchResponse->assertOk();
        $searchResponse->assertJsonFragment(['name' => 'Active Staff']);
        $searchResponse->assertJsonMissing(['name' => 'Inactive Staff']);
        $searchResponse->assertJsonMissing(['name' => 'Admin Linked']);
    }

    public function test_leaderboard_is_limited_to_top_10_and_shows_viewer_rank_outside_top_10(): void
    {
        $branch = $this->branch('Main');
        $cutoff = $this->finalizedCutoff($branch);
        $viewer = $this->staffEmployee($branch, 'Viewer', 'Person', 'VIEWER');

        $this->score($cutoff, $viewer, 10);

        for ($i = 1; $i <= 11; $i++) {
            $employee = $this->staffEmployee($branch, 'Staff', str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'EMP'.$i);
            $this->score($cutoff, $employee, (120 - $i) * 10);
        }

        $response = $this->actingAs($viewer->user)->get(route('staff.achievements'));

        $response->assertOk();
        $response->assertSee('Staff');
        $response->assertSee('Staff 10');
        $response->assertSee('Your rank');
        $response->assertSee('#12');
        $response->assertSee('Viewer Person');
    }

    public function test_leaderboard_shows_empty_champions_state_until_someone_reaches_ten_points(): void
    {
        $branch = $this->branch('Main');
        $cutoff = $this->finalizedCutoff($branch);

        $viewer = $this->staffEmployee($branch, 'Viewer', 'Person', 'VIEWER');
        $nearMiss = $this->staffEmployee($branch, 'Almost', 'There', 'ALMOST');

        $this->score($cutoff, $nearMiss, 9);

        $response = $this->actingAs($viewer->user)->get(route('staff.achievements'));

        $response->assertOk();
        $response->assertSee('No Champions Yet');
        $response->assertSee('The leaderboard is waiting for its first hero.');
        $response->assertDontSee('Almost There');

        $searchResponse = $this->actingAs($viewer->user)->getJson(route('staff.achievements.search', ['q' => 'Almost']));

        $searchResponse->assertOk();
        $searchResponse->assertExactJson(['results' => []]);
    }

    public function test_staff_can_search_leaderboard_for_rank_and_points_outside_top_10(): void
    {
        $branch = $this->branch('Main');
        $cutoff = $this->finalizedCutoff($branch);
        $viewer = $this->staffEmployee($branch, 'Viewer', 'Person', 'VIEWER');

        $this->score($cutoff, $viewer, 5);

        for ($i = 1; $i <= 11; $i++) {
            $employee = $this->staffEmployee($branch, 'Staff', str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'EMP'.$i);
            $this->score($cutoff, $employee, (120 - $i) * 10);
        }

        $response = $this->actingAs($viewer->user)->getJson(route('staff.achievements.search', ['q' => 'Staff 11']));

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'Staff 11',
            'points' => 1090,
            'rank' => 11,
            'rank_name' => 'Crunch Keeper',
        ]);
    }

    public function test_staff_search_shows_empty_state_when_no_leaderboard_match_exists(): void
    {
        $branch = $this->branch('Main');
        $cutoff = $this->finalizedCutoff($branch);
        $viewer = $this->staffEmployee($branch, 'Viewer', 'Person', 'VIEWER');
        $leader = $this->staffEmployee($branch, 'Ada', 'Lovelace', 'LEADER');

        $this->score($cutoff, $viewer, 40);
        $this->score($cutoff, $leader, 90);

        // Search is client-side; the empty-state message is present in the modal HTML (shown by Alpine when no match)
        $response = $this->actingAs($viewer->user)->get(route('staff.achievements'));

        $response->assertOk();
        $response->assertSee('No player found matching');
    }

    public function test_leaderboard_uses_name_order_as_stable_tie_breaker(): void
    {
        $branch = $this->branch('Main');
        $cutoff = $this->finalizedCutoff($branch);

        $viewer = $this->staffEmployee($branch, 'Viewer', 'Person', 'VIEWER');
        $zara = $this->staffEmployee($branch, 'Zara', 'Alpha', 'ZARA');
        $ada = $this->staffEmployee($branch, 'Ada', 'Beta', 'ADA');

        $this->score($cutoff, $viewer, 10);
        $this->score($cutoff, $zara, 80);
        $this->score($cutoff, $ada, 80);

        $response = $this->actingAs($viewer->user)->getJson(route('staff.achievements.search', ['q' => 'a']));

        $response->assertOk();

        $names = collect($response->json('results'))->pluck('name')->all();

        $this->assertLessThan(
            array_search('Zara Alpha', $names, true),
            array_search('Ada Beta', $names, true)
        );
    }

    public function test_staff_achievements_route_is_registered_once(): void
    {
        $routes = collect(Route::getRoutes())->filter(fn ($route) => $route->getName() === 'staff.achievements');

        $this->assertCount(1, $routes);
    }

    private function branch(string $name): Branch
    {
        return Branch::create([
            'name' => $name,
            'address' => 'Test',
            'work_start_time' => '09:00',
            'work_end_time' => '18:00',
        ]);
    }

    private function finalizedCutoff(Branch $branch): PayrollCutoff
    {
        return PayrollCutoff::create([
            'branch_id' => $branch->id,
            'name' => 'Finalized Cutoff',
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-15',
            'status' => 'finalized',
            'finalized_at' => '2026-04-16 10:00:00',
        ]);
    }

    private function staffEmployee(Branch $branch, string $firstName, string $lastName, string $code, bool $active = true): Employee
    {
        $employee = $this->employee($branch, $firstName, $lastName, $code, $active);

        User::factory()->create([
            'name' => $employee->full_name,
            'role' => 'staff',
            'employee_id' => $employee->id,
            'signature' => 'data:image/png;base64,test',
            'must_change_password' => false,
        ]);

        return $employee->load('user');
    }

    private function employee(Branch $branch, string $firstName, string $lastName, string $code, bool $active = true): Employee
    {
        return Employee::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'employee_code' => $code,
            'branch_id' => $branch->id,
            'salary_type' => 'daily',
            'rate' => 500,
            'active' => $active,
        ]);
    }

    private function score(PayrollCutoff $cutoff, Employee $employee, int $points): void
    {
        AttendanceScore::create([
            'payroll_cutoff_id' => $cutoff->id,
            'employee_id' => $employee->id,
            'total_points' => $points,
            'on_time_days' => intdiv($points, 10),
            'same_day_complete_days' => $points % 10 === 5 ? 1 : 0,
            'finalized_at' => $cutoff->finalized_at,
        ]);
    }
}
