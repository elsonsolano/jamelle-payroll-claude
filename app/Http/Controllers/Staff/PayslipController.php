<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\PayrollEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayslipController extends Controller
{
    public function index(): View
    {
        $employee = Auth::user()->employee;

        $payslips = PayrollEntry::with('payrollCutoff')
            ->where('employee_id', $employee->id)
            ->whereHas('payrollCutoff', fn($q) => $q->where('status', 'finalized'))
            ->get()
            ->sortByDesc(fn($e) => $e->payrollCutoff->end_date);

        return view('staff.payslips.index', compact('payslips'));
    }

    public function show(PayrollEntry $entry): View
    {
        $employee = Auth::user()->employee;

        abort_if($entry->employee_id !== $employee->id, 403);

        $entry->load('employee.branch', 'employee.user', 'payrollCutoff', 'payrollDeductions', 'payrollVariableDeductions', 'payrollRefunds');

        return view('staff.payslips.show', compact('entry'));
    }

    public function downloadPdf(PayrollEntry $entry): \Illuminate\Http\Response
    {
        $employee = Auth::user()->employee;

        abort_if($entry->employee_id !== $employee->id, 403);

        $entry->load('employee.branch', 'employee.user', 'payrollCutoff', 'payrollDeductions', 'payrollVariableDeductions', 'payrollRefunds');

        $cutoff = $entry->payrollCutoff;

        $pdf = Pdf::loadView('payroll.entries.pdf', compact('cutoff', 'entry'))
            ->setPaper('a4', 'portrait');

        $filename = 'payslip-' . str($entry->employee->full_name)->slug() . '-' . $cutoff->start_date->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function acknowledge(PayrollEntry $entry): RedirectResponse
    {
        $employee = Auth::user()->employee;

        abort_if($entry->employee_id !== $employee->id, 403);
        abort_if($entry->payrollCutoff->status !== 'finalized', 403);

        if (! $entry->acknowledged_at) {
            $entry->update([
                'acknowledged_at' => now(),
                'acknowledged_ip' => request()->ip(),
                'acknowledged_by' => 'staff',
            ]);
        }

        return back()->with('acknowledged', true);
    }
}
