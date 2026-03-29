<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Dtr;
use App\Notifications\OtApproved;
use App\Notifications\OtRejected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OtApprovalController extends Controller
{
    public function index(): View
    {
        $pendingDtrs = $this->approvableQuery()->with('employee.branch')->orderBy('date')->get();

        return view('staff.ot-approvals.index', compact('pendingDtrs'));
    }

    public function approve(Dtr $dtr): RedirectResponse
    {
        $this->ensureCanApprove($dtr);

        $dtr->update([
            'ot_status'      => 'approved',
            'ot_approved_by' => Auth::id(),
            'ot_approved_at' => now(),
            'ot_rejection_reason' => null,
        ]);

        // Notify the employee
        $staffUser = $dtr->employee->user;
        if ($staffUser) {
            $staffUser->notify(new OtApproved($dtr, Auth::user()->name));
        }

        return back()->with('success', "OT for {$dtr->employee->full_name} on {$dtr->date->format('M d, Y')} approved.");
    }

    public function reject(Request $request, Dtr $dtr): RedirectResponse
    {
        $this->ensureCanApprove($dtr);

        $request->validate(['reason' => 'nullable|string|max:500']);

        $dtr->update([
            'ot_status'           => 'rejected',
            'ot_approved_by'      => Auth::id(),
            'ot_approved_at'      => now(),
            'ot_rejection_reason' => $request->reason,
            'overtime_hours'      => 0,
        ]);

        $staffUser = $dtr->employee->user;
        if ($staffUser) {
            $staffUser->notify(new OtRejected($dtr, Auth::user()->name));
        }

        return back()->with('success', "OT for {$dtr->employee->full_name} on {$dtr->date->format('M d, Y')} rejected.");
    }

    private function approvableQuery()
    {
        $user         = Auth::user();
        $employee     = $user->employee;
        $branch       = $employee?->branch;
        $isHeadOffice = $branch && strtolower(trim($branch->name)) === 'head office';

        $query = Dtr::where('ot_status', 'pending');

        if ($user->isAdmin()) {
            // Admin approves Head Office can_approve_ot employees
            $headOffice = Branch::whereRaw('LOWER(TRIM(name)) = ?', ['head office'])->first();
            $query->whereHas('employee', function ($q) use ($headOffice) {
                $q->where('branch_id', $headOffice?->id)
                  ->whereHas('user', fn($u) => $u->where('can_approve_ot', true));
            });
        } elseif ($user->can_approve_ot && $isHeadOffice) {
            // HO approver → approves non-HO branch-level approvers
            $headOffice = Branch::whereRaw('LOWER(TRIM(name)) = ?', ['head office'])->first();
            $query->whereHas('employee', function ($q) use ($headOffice) {
                $q->where('branch_id', '!=', $headOffice?->id)
                  ->whereHas('user', fn($u) => $u->where('can_approve_ot', true));
            });
        } elseif ($user->can_approve_ot && !$isHeadOffice) {
            // Branch approver → approves all staff in same branch except themselves
            $query->whereHas('employee', function ($q) use ($branch, $employee) {
                $q->where('branch_id', $branch->id)
                  ->where('id', '!=', $employee->id);
            });
        } else {
            // No approval rights — return empty
            $query->whereRaw('0 = 1');
        }

        return $query;
    }

    private function ensureCanApprove(Dtr $dtr): void
    {
        // Re-use the same filter to confirm this DTR is in the approver's queue
        $exists = $this->approvableQuery()->where('id', $dtr->id)->exists();
        if (!$exists) {
            abort(403, 'You are not authorized to approve this overtime request.');
        }
    }
}
