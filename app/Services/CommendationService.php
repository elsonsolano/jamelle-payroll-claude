<?php

namespace App\Services;

use App\Models\Commendation;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\CommendationReceived;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommendationService
{
    public const MAX_TRAITS = 3;

    public const TRAITS = [
        'helpful_teammate' => ['label' => 'Helpful Teammate', 'icon' => '🤝', 'color' => '#3B82F6'],
        'reliable_worker' => ['label' => 'Reliable Worker', 'icon' => '🔒', 'color' => '#10B981'],
        'good_leader' => ['label' => 'Good Leader', 'icon' => '⭐', 'color' => '#F59E0B'],
        'positive_attitude' => ['label' => 'Positive Attitude', 'icon' => '☀️', 'color' => '#F97316'],
        'team_player' => ['label' => 'Team Player', 'icon' => '🏅', 'color' => '#8B5CF6'],
        'customer_care' => ['label' => 'Customer Care', 'icon' => '💬', 'color' => '#06B6D4'],
        'problem_solver' => ['label' => 'Problem Solver', 'icon' => '💡', 'color' => '#EAB308'],
        'hard_worker' => ['label' => 'Hard Worker', 'icon' => '💪', 'color' => '#EF4444'],
        'fast_learner' => ['label' => 'Fast Learner', 'icon' => '🚀', 'color' => '#A855F7'],
        'above_beyond' => ['label' => 'Above & Beyond', 'icon' => '🏆', 'color' => '#FFD700'],
        'good_communicator' => ['label' => 'Good Communicator', 'icon' => '📣', 'color' => '#5BBF27'],
        'good_looking' => ['label' => 'Good Looking', 'icon' => '✨', 'color' => '#EC4899'],
    ];

    public function create(User $sender, Employee $recipient, array $traitIds): Commendation
    {
        $sender->loadMissing('employee');
        $recipient->loadMissing('user');

        $traitIds = $this->validateTraits($traitIds);

        if (! $sender->isStaff() || ! $sender->employee) {
            throw ValidationException::withMessages([
                'recipient' => 'Only staff can give commendations.',
            ]);
        }

        if (! $recipient->active || ! $recipient->user?->isStaff()) {
            throw ValidationException::withMessages([
                'recipient' => 'You can only commend active staff members.',
            ]);
        }

        if ((int) $sender->employee_id === (int) $recipient->id) {
            throw ValidationException::withMessages([
                'recipient' => 'You cannot commend yourself.',
            ]);
        }

        return DB::transaction(function () use ($sender, $recipient, $traitIds) {
            if (Commendation::where('sender_user_id', $sender->id)
                ->where('recipient_employee_id', $recipient->id)
                ->lockForUpdate()
                ->exists()
            ) {
                throw ValidationException::withMessages([
                    'recipient' => 'You already commended this staff member.',
                ]);
            }

            $commendation = Commendation::create([
                'sender_user_id' => $sender->id,
                'recipient_employee_id' => $recipient->id,
                'trait_ids' => $traitIds,
                'points' => count($traitIds),
            ]);

            $recipient->user?->notify(new CommendationReceived($commendation));

            return $commendation;
        });
    }

    public function summaryFor(Employee $employee, ?User $viewer = null): array
    {
        $counts = $this->countsForEmployee($employee);
        $total = array_sum($counts);
        $viewerTraitIds = [];
        $alreadyCommended = false;

        if ($viewer) {
            $commendation = Commendation::where('sender_user_id', $viewer->id)
                ->where('recipient_employee_id', $employee->id)
                ->first();

            $viewerTraitIds = $commendation?->trait_ids ?? [];
            $alreadyCommended = $commendation !== null;
        }

        return [
            'counts' => $counts,
            'total' => $total,
            'already_commended' => $alreadyCommended,
            'viewer_trait_ids' => $viewerTraitIds,
        ];
    }

    public function countsForEmployee(Employee $employee): array
    {
        $counts = array_fill_keys(array_keys(self::TRAITS), 0);

        Commendation::where('recipient_employee_id', $employee->id)
            ->get(['trait_ids'])
            ->each(function (Commendation $commendation) use (&$counts) {
                foreach ($commendation->trait_ids ?? [] as $traitId) {
                    if (array_key_exists($traitId, $counts)) {
                        $counts[$traitId]++;
                    }
                }
            });

        return array_filter($counts, fn (int $count) => $count > 0);
    }

    public function pointsByEmployee(Collection $employeeIds): Collection
    {
        if ($employeeIds->isEmpty()) {
            return collect();
        }

        return Commendation::query()
            ->whereIn('recipient_employee_id', $employeeIds->all())
            ->select('recipient_employee_id', DB::raw('SUM(points) as total_points'))
            ->groupBy('recipient_employee_id')
            ->pluck('total_points', 'recipient_employee_id')
            ->map(fn ($points) => (int) $points);
    }

    public function pointsForEmployee(Employee $employee): int
    {
        return (int) Commendation::where('recipient_employee_id', $employee->id)->sum('points');
    }

    public function traitsForClient(): array
    {
        return collect(self::TRAITS)
            ->map(fn (array $trait, string $id) => ['id' => $id] + $trait)
            ->values()
            ->all();
    }

    public function labelsFor(array $traitIds): array
    {
        return collect($traitIds)
            ->map(fn (string $traitId) => self::TRAITS[$traitId]['label'] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    private function validateTraits(array $traitIds): array
    {
        $traitIds = array_values(array_unique(array_filter($traitIds, 'is_string')));

        if (count($traitIds) < 1) {
            throw ValidationException::withMessages([
                'trait_ids' => 'Choose at least one commendation.',
            ]);
        }

        if (count($traitIds) > self::MAX_TRAITS) {
            throw ValidationException::withMessages([
                'trait_ids' => 'Choose up to 3 commendations only.',
            ]);
        }

        $invalid = array_diff($traitIds, array_keys(self::TRAITS));
        if ($invalid !== []) {
            throw ValidationException::withMessages([
                'trait_ids' => 'One or more commendations are invalid.',
            ]);
        }

        return $traitIds;
    }
}
