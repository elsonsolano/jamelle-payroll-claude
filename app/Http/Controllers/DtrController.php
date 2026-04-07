<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\PayrollCutoff;
use App\Notifications\OtApproved;
use App\Notifications\OtRejected;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DtrController extends Controller
{
    public function index(Request $request): View
    {
        $query = Dtr::with('employee.branch')->whereHas('employee');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $request->branch_id));
        }

        if ($request->filled('cutoff_id')) {
            $cutoff = PayrollCutoff::findOrFail($request->cutoff_id);
            $query->whereBetween('date', [$cutoff->start_date, $cutoff->end_date])
                  ->whereHas('employee', fn($q) => $q->where('branch_id', $cutoff->branch_id));
        } else {
            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }
        }

        if ($request->boolean('pending_ot')) {
            $query->where('ot_status', 'pending');
        }

        $dtrs      = $query->orderByDesc('date')->orderBy('employee_id')->paginate(30)->withQueryString();
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        $branches  = Branch::orderBy('name')->get();
        $cutoffs   = PayrollCutoff::with('branch')->orderByDesc('start_date')->get();

        return view('dtrs.index', compact('dtrs', 'employees', 'branches', 'cutoffs'));
    }

    public function export(Request $request): \Illuminate\Http\Response
    {
        $query = Dtr::with('employee.branch')->whereHas('employee');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $request->branch_id));
        }

        if ($request->filled('cutoff_id')) {
            $cutoff = PayrollCutoff::findOrFail($request->cutoff_id);
            $query->whereBetween('date', [$cutoff->start_date, $cutoff->end_date])
                  ->whereHas('employee', fn($q) => $q->where('branch_id', $cutoff->branch_id));
        } else {
            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }
        }

        $dtrs = $query->orderBy('date')->orderBy('employee_id')->get();

        // Determine date range label for the header
        if ($request->filled('cutoff_id')) {
            $cutoffModel = PayrollCutoff::find($request->cutoff_id);
            $dateFrom = $cutoffModel->start_date->format('M d, Y');
            $dateTo   = $cutoffModel->end_date->format('M d, Y');
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            $dateFrom = $request->filled('date_from') ? \Carbon\Carbon::parse($request->date_from)->format('M d, Y') : null;
            $dateTo   = $request->filled('date_to')   ? \Carbon\Carbon::parse($request->date_to)->format('M d, Y')   : null;
        } else {
            $dateFrom = $dtrs->isNotEmpty() ? \Carbon\Carbon::parse($dtrs->min('date'))->format('M d, Y') : null;
            $dateTo   = $dtrs->isNotEmpty() ? \Carbon\Carbon::parse($dtrs->max('date'))->format('M d, Y') : null;
        }

        // Group: branch name → employee_id → DTRs
        $grouped = $dtrs
            ->sortBy(fn($d) => $d->employee->branch->name)
            ->groupBy(fn($d) => $d->employee->branch->name)
            ->map(fn($branchDtrs) => $branchDtrs
                ->sortBy(fn($d) => $d->employee->last_name . $d->employee->first_name)
                ->groupBy('employee_id')
            );

        $pdf = Pdf::loadView('dtrs.export', compact('grouped', 'dateFrom', 'dateTo'))
                  ->setPaper('a4', 'landscape');

        $filename = 'dtr-export-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function show(Dtr $dtr): View
    {
        $dtr->load('employee.branch', 'approvedBy');
        return view('dtrs.show', compact('dtr'));
    }

    public function approveOt(Dtr $dtr): RedirectResponse
    {
        abort_unless($dtr->ot_status === 'pending', 422, 'This OT request is not pending.');

        $dtr->update([
            'ot_status'           => 'approved',
            'ot_approved_by'      => Auth::id(),
            'ot_approved_at'      => now(),
            'ot_rejection_reason' => null,
        ]);

        $staffUser = $dtr->employee->user;
        if ($staffUser) {
            $staffUser->notify(new OtApproved($dtr, Auth::user()->name));
        }

        return back()->with('success', "OT for {$dtr->employee->full_name} on {$dtr->date->format('M d, Y')} approved.");
    }

    public function rejectOt(Request $request, Dtr $dtr): RedirectResponse
    {
        abort_unless($dtr->ot_status === 'pending', 422, 'This OT request is not pending.');

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
}
