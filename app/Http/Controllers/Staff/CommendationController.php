<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\CommendationService;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CommendationController extends Controller
{
    public function __construct(
        private CommendationService $commendations,
        private GamificationService $gamification,
    ) {}

    public function show(Employee $employee): JsonResponse
    {
        $this->ensureVisibleStaff($employee);

        return response()->json([
            'summary' => $this->commendations->summaryFor($employee, Auth::user()),
        ]);
    }

    public function store(Request $request, Employee $employee): JsonResponse
    {
        $this->ensureVisibleStaff($employee);

        $validated = $request->validate([
            'trait_ids' => ['required', 'array', 'min:1', 'max:'.CommendationService::MAX_TRAITS],
            'trait_ids.*' => ['required', 'string'],
        ]);

        $beforePoints = $this->gamification->pointsFor($employee);
        $commendation = $this->commendations->create(Auth::user(), $employee, $validated['trait_ids']);
        $summary = $this->commendations->summaryFor($employee, Auth::user());
        $points = $this->gamification->pointsFor($employee);
        $this->gamification->recordRankUpIfNeeded($employee->fresh(), $beforePoints, $points, 'commendation');
        $rank = $this->gamification->rankFor($points);
        $leaderboardRow = collect($this->gamification->leaderboard(Auth::user()->employee)['allEntries'])
            ->firstWhere('employee_id', $employee->id);

        return response()->json([
            'message' => 'Commendation sent.',
            'points_awarded' => $commendation->points,
            'summary' => $summary,
            'points' => $points,
            'rank' => $leaderboardRow['rank'] ?? null,
            'rank_name' => $rank['name'],
        ], 201);
    }

    private function ensureVisibleStaff(Employee $employee): void
    {
        $employee->loadMissing('user');

        if (! $employee->active || ! $employee->user?->isStaff()) {
            throw ValidationException::withMessages([
                'recipient' => 'You can only commend active staff members.',
            ]);
        }
    }
}
