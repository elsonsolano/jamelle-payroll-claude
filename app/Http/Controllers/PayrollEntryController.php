<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollCutoff;
use App\Models\PayrollEntry;
use App\Services\PayrollComputationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PayrollEntryController extends Controller
{
    public function __construct(protected PayrollComputationService $payrollService)
    {
    }

    public function index(PayrollCutoff $cutoff): View
    {
        $entries = $cutoff->payrollEntries()->with('employee')->paginate(30);
        return view('payroll.entries.index', compact('cutoff', 'entries'));
    }

    public function show(PayrollCutoff $cutoff, PayrollEntry $entry): View
    {
        $entry->load('employee.branch', 'payrollDeductions', 'payrollVariableDeductions', 'payrollRefunds');
        return view('payroll.entries.show', compact('cutoff', 'entry'));
    }

    public function pdf(PayrollCutoff $cutoff, PayrollEntry $entry): \Illuminate\Http\Response
    {
        $entry->load('employee.branch', 'payrollDeductions', 'payrollVariableDeductions', 'payrollRefunds');

        $pdf = Pdf::loadView('payroll.entries.pdf', compact('cutoff', 'entry'))
            ->setPaper('a4', 'portrait');

        $filename = 'payslip-' . str($entry->employee->full_name)->slug() . '-' . $cutoff->start_date->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    public function generate(Request $request, PayrollCutoff $cutoff): RedirectResponse
    {
        if ($cutoff->status === 'voided') {
            return redirect()->route('payroll.cutoffs.show', $cutoff)
                ->with('error', 'Cannot regenerate a voided payroll cutoff.');
        }

        $cutoff->update(['status' => 'processing']);

        $employees = Employee::where('branch_id', $cutoff->branch_id)
            ->where('active', true)
            ->get();

        foreach ($employees as $employee) {
            $this->payrollService->computeEntry($cutoff, $employee);
        }

        $cutoff->update(['status' => 'finalized']);

        return redirect()->route('payroll.cutoffs.show', $cutoff)
            ->with('success', 'Payroll generated for ' . $employees->count() . ' employee(s).');
    }
}
