<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user     = Auth::user();
        $employee = $user->employee;

        $recentDtrs = $employee->dtrs()
            ->orderByDesc('date')
            ->limit(7)
            ->get();

        $pendingOtCount = $employee->dtrs()
            ->where('ot_status', 'pending')
            ->count();

        $unreadCount = $user->unreadNotifications()->count();

        // If this user can approve OT, count pending approvals for their queue
        $pendingApprovalCount = 0;
        if ($user->can_approve_ot || $user->isAdmin()) {
            $pendingApprovalCount = $this->pendingApprovalCount($user, $employee);
        }

        return view('staff.dashboard', compact(
            'employee', 'recentDtrs', 'pendingOtCount', 'unreadCount', 'pendingApprovalCount'
        ));
    }

    private function pendingApprovalCount(\App\Models\User $user, \App\Models\Employee $employee): int
    {
        $branch       = $employee->branch;
        $isHeadOffice = strtolower(trim($branch->name)) === 'head office';

        if ($user->isAdmin()) {
            // Admin approves Head Office can_approve_ot employees
            return \App\Models\Dtr::where('ot_status', 'pending')
                ->whereHas('employee', function ($q) {
                    $headOffice = \App\Models\Branch::whereRaw('LOWER(TRIM(name)) = ?', ['head office'])->first();
                    if ($headOffice) {
                        $q->where('branch_id', $headOffice->id)
                          ->whereHas('user', fn($u) => $u->where('can_approve_ot', true));
                    }
                })->count();
        }

        if ($user->can_approve_ot && $isHeadOffice) {
            // HO approver → approves non-HO branch approvers
            $headOffice = \App\Models\Branch::whereRaw('LOWER(TRIM(name)) = ?', ['head office'])->first();
            return \App\Models\Dtr::where('ot_status', 'pending')
                ->whereHas('employee', function ($q) use ($headOffice) {
                    $q->where('branch_id', '!=', $headOffice?->id)
                      ->whereHas('user', fn($u) => $u->where('can_approve_ot', true));
                })->count();
        }

        if ($user->can_approve_ot && !$isHeadOffice) {
            // Branch approver → approves regular staff in same branch
            return \App\Models\Dtr::where('ot_status', 'pending')
                ->whereHas('employee', function ($q) use ($branch) {
                    $q->where('branch_id', $branch->id)
                      ->whereHas('user', fn($u) => $u->where('can_approve_ot', false));
                })->count();
        }

        return 0;
    }
}
