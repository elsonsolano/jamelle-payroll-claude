<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Commendation;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\CommendationReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StaffCommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_commend_another_staff_with_up_to_three_traits(): void
    {
        Notification::fake();

        $branch = $this->branch();
        $sender = $this->staffEmployee($branch, 'Sender', 'Staff', 'SENDER');
        $recipient = $this->staffEmployee($branch, 'Receiver', 'Staff', 'RECEIVER');

        $response = $this->actingAs($sender->user)->postJson(route('staff.commendations.store', $recipient), [
            'trait_ids' => ['helpful_teammate', 'reliable_worker', 'good_leader'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('points_awarded', 3)
            ->assertJsonPath('summary.total', 3)
            ->assertJsonPath('summary.counts.helpful_teammate', 1)
            ->assertJsonPath('summary.already_commended', true)
            ->assertJsonPath('points', 3);

        $this->assertDatabaseHas('commendations', [
            'sender_user_id' => $sender->user->id,
            'recipient_employee_id' => $recipient->id,
            'points' => 3,
        ]);

        Notification::assertSentTo($recipient->user, CommendationReceived::class, function (CommendationReceived $notification) {
            return $notification->toArray($notification->commendation->recipient->user)['message']
                === 'Someone commended you for Helpful Teammate, Reliable Worker, and Good Leader. +3 points';
        });
    }

    public function test_staff_can_commend_with_one_or_two_traits(): void
    {
        $branch = $this->branch();
        $sender = $this->staffEmployee($branch, 'Sender', 'One', 'SENDER1');
        $recipient = $this->staffEmployee($branch, 'Receiver', 'One', 'RECEIVER1');
        $otherSender = $this->staffEmployee($branch, 'Sender', 'Two', 'SENDER2');

        $this->actingAs($sender->user)->postJson(route('staff.commendations.store', $recipient), [
            'trait_ids' => ['helpful_teammate'],
        ])->assertCreated()->assertJsonPath('points_awarded', 1);

        $this->actingAs($otherSender->user)->postJson(route('staff.commendations.store', $recipient), [
            'trait_ids' => ['team_player', 'good_looking'],
        ])->assertCreated()->assertJsonPath('points_awarded', 2);

        $this->assertSame(3, Commendation::where('recipient_employee_id', $recipient->id)->sum('points'));
    }

    public function test_self_duplicate_invalid_and_too_many_commendations_are_rejected(): void
    {
        $branch = $this->branch();
        $sender = $this->staffEmployee($branch, 'Sender', 'Staff', 'SENDER');
        $recipient = $this->staffEmployee($branch, 'Receiver', 'Staff', 'RECEIVER');

        $this->actingAs($sender->user)->postJson(route('staff.commendations.store', $sender), [
            'trait_ids' => ['helpful_teammate'],
        ])->assertUnprocessable();

        $this->actingAs($sender->user)->postJson(route('staff.commendations.store', $recipient), [
            'trait_ids' => ['helpful_teammate', 'reliable_worker', 'good_leader', 'team_player'],
        ])->assertUnprocessable();

        $this->actingAs($sender->user)->postJson(route('staff.commendations.store', $recipient), [
            'trait_ids' => ['not_real'],
        ])->assertUnprocessable();

        $this->actingAs($sender->user)->postJson(route('staff.commendations.store', $recipient), [
            'trait_ids' => ['helpful_teammate'],
        ])->assertCreated();

        $this->actingAs($sender->user)->postJson(route('staff.commendations.store', $recipient), [
            'trait_ids' => ['good_leader'],
        ])->assertUnprocessable();
    }

    public function test_leaderboard_search_includes_commendation_points_and_public_breakdown(): void
    {
        $branch = $this->branch();
        $viewer = $this->staffEmployee($branch, 'Viewer', 'Staff', 'VIEWER');
        $recipient = $this->staffEmployee($branch, 'Aime', 'Ajero', 'AIME');
        $otherSender = $this->staffEmployee($branch, 'Other', 'Sender', 'OTHER');

        $senders = collect([$otherSender]);
        for ($i = 1; $i <= 3; $i++) {
            $senders->push($this->staffEmployee($branch, 'Other', 'Sender'.$i, 'OTHER'.$i));
        }

        foreach ($senders as $sender) {
            Commendation::create([
                'sender_user_id' => $sender->user->id,
                'recipient_employee_id' => $recipient->id,
                'trait_ids' => ['good_looking', 'good_leader', 'helpful_teammate'],
                'points' => 3,
            ]);
        }

        $response = $this->actingAs($viewer->user)->getJson(route('staff.achievements.search', ['q' => 'Aime']));

        $response->assertOk()
            ->assertJsonPath('results.0.name', 'Aime Ajero')
            ->assertJsonPath('results.0.points', 12)
            ->assertJsonPath('results.0.commendations.total', 12)
            ->assertJsonPath('results.0.commendations.counts.good_looking', 4)
            ->assertJsonPath('results.0.commendations.already_commended', false);
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
        ]);

        User::factory()->create([
            'name' => $employee->full_name,
            'role' => 'staff',
            'employee_id' => $employee->id,
            'signature' => 'data:image/png;base64,test',
            'must_change_password' => false,
        ]);

        return $employee->load('user');
    }
}
