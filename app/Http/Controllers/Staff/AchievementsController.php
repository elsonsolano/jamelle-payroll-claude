<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\PayrollCutoff;
use App\Services\GamificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AchievementsController extends Controller
{
    public function __construct(private GamificationService $gamification) {}

    public function index(): View
    {
        $employee = Auth::user()->employee;

        $cutoff = PayrollCutoff::where('branch_id', $employee->branch_id)
            ->where('status', '!=', 'voided')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        $data = $this->gamification->achievementsData($employee, $cutoff);

        // Local preview override: ?pts=150 forces a specific point total for rank testing
        if (app()->isLocal() && request()->has('pts')) {
            $data['total_points'] = (int) request('pts');
        }

        $rank = $this->gamification->rankFor($data['total_points']);

        return view('staff.achievements', [
            'employee'           => $employee,
            'cutoff'             => $cutoff,
            'totalPoints'        => $data['total_points'],
            'thisCutoffPoints'   => $data['this_cutoff_points'],
            'totalBadgesEarned'  => $data['total_badges_earned'],
            'pointsLog'          => $data['points_log'],
            'badges'             => $data['badges'],
            'noLateStreak'       => $data['no_late_streak'],
            'rank'               => $rank,
        ]);
    }
}
