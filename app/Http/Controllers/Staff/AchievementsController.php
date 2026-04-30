<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\GamificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AchievementsController extends Controller
{
    public function __construct(private GamificationService $gamification) {}

    public function index(Request $request): View
    {
        $launchAt = Carbon::parse('2026-05-01 06:00:00', 'Asia/Manila');
        $preview = app()->environment(['local', 'testing']) && $request->boolean('preview');
        $comingSoon = now('Asia/Manila')->lt($launchAt) && ! $preview;

        if ($comingSoon) {
            return view('staff.achievements', [
                'comingSoon' => true,
                'launchTimestamp' => $launchAt->timestamp * 1000,
            ]);
        }

        $employee = Auth::user()->employee;

        $cutoff = $this->gamification->currentCutoffFor($employee);
        $data = $this->gamification->achievementsData($employee, $cutoff);
        $leaderboard = $this->gamification->leaderboard($employee);

        // Local preview override: ?pts=150 forces a specific point total for rank testing
        if (app()->isLocal() && request()->has('pts')) {
            $data['total_points'] = (int) request('pts');
        }

        $rank = $this->gamification->rankFor($data['total_points']);

        return view('staff.achievements', [
            'comingSoon' => false,
            'launchTimestamp' => $launchAt->timestamp * 1000,
            'employee' => $employee,
            'cutoff' => $cutoff,
            'totalPoints' => $data['total_points'],
            'thisCutoffPoints' => $data['this_cutoff_points'],
            'totalBadgesEarned' => $data['total_badges_earned'],
            'pointsLog' => $data['points_log'],
            'badges' => $data['badges'],
            'noLateStreak' => $data['no_late_streak'],
            'rank' => $rank,
            'leaderboard' => $leaderboard,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $query = trim($validated['q'] ?? '');

        if ($query === '') {
            return response()->json([
                'results' => [],
            ]);
        }

        $employee = Auth::user()->employee;
        $needle = mb_strtolower($query);

        $results = collect($this->gamification->leaderboard($employee)['allEntries'])
            ->filter(fn (array $row) => str_contains(mb_strtolower($row['name']), $needle))
            ->take(10)
            ->values()
            ->all();

        return response()->json([
            'results' => $results,
        ]);
    }
}
