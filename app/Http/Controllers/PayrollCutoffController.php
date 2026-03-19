<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\PayrollCutoff;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PayrollCutoffController extends Controller
{
    public function index(Request $request): View
    {
        $query = PayrollCutoff::with('branch')
            ->withCount('payrollEntries');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $cutoffs  = $query->orderByDesc('start_date')->paginate(20)->withQueryString();
        $branches = Branch::orderBy('name')->get();

        return view('payroll.cutoffs.index', compact('cutoffs', 'branches'));
    }

    public function create(): View
    {
        $branches = Branch::orderBy('name')->get();
        return view('payroll.cutoffs.create', compact('branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id'  => 'required|exists:branches,id',
            'name'       => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $validated['status'] = 'draft';
        PayrollCutoff::create($validated);

        return redirect()->route('payroll.cutoffs.index')->with('success', 'Payroll cutoff created successfully.');
    }

    public function show(PayrollCutoff $cutoff): View
    {
        $cutoff->load('branch');
        $entries = $cutoff->payrollEntries()
            ->with('employee')
            ->orderBy('employee_id')
            ->paginate(30);

        $summary = [
            'total_employees' => $cutoff->payrollEntries()->count(),
            'total_basic_pay' => $cutoff->payrollEntries()->sum('basic_pay'),
            'total_overtime'  => $cutoff->payrollEntries()->sum('overtime_pay'),
            'total_deductions'=> $cutoff->payrollEntries()->sum('total_deductions'),
            'total_net_pay'   => $cutoff->payrollEntries()->sum('net_pay'),
        ];

        return view('payroll.cutoffs.show', compact('cutoff', 'entries', 'summary'));
    }

    public function edit(PayrollCutoff $cutoff): View
    {
        $branches = Branch::orderBy('name')->get();
        return view('payroll.cutoffs.edit', compact('cutoff', 'branches'));
    }

    public function update(Request $request, PayrollCutoff $cutoff): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id'  => 'required|exists:branches,id',
            'name'       => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'status'     => 'required|in:draft,processing,finalized',
        ]);

        $cutoff->update($validated);

        return redirect()->route('payroll.cutoffs.show', $cutoff)->with('success', 'Cutoff updated successfully.');
    }

    public function destroy(PayrollCutoff $cutoff): RedirectResponse
    {
        $cutoff->delete();
        return redirect()->route('payroll.cutoffs.index')->with('success', 'Payroll cutoff deleted.');
    }
}
