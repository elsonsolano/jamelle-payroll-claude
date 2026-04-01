<?php

namespace App\Http\Controllers;

use App\Models\PayrollCutoff;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryVariableDeduction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayrollEntryVariableDeductionController extends Controller
{
    public function store(Request $request, PayrollCutoff $cutoff, PayrollEntry $entry): RedirectResponse
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0.01',
        ]);

        $entry->payrollVariableDeductions()->create($validated);

        $this->recalculateEntry($entry);

        return redirect()->route('payroll.cutoffs.entries.show', [$cutoff, $entry])
            ->with('success', 'Deduction added.');
    }

    public function update(Request $request, PayrollCutoff $cutoff, PayrollEntry $entry, PayrollEntryVariableDeduction $variableDeduction): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $variableDeduction->update($validated);

        $this->recalculateEntry($entry);

        return redirect()->route('payroll.cutoffs.entries.show', [$cutoff, $entry])
            ->with('success', 'Deduction updated.');
    }

    public function destroy(PayrollCutoff $cutoff, PayrollEntry $entry, PayrollEntryVariableDeduction $variableDeduction): RedirectResponse
    {
        $variableDeduction->delete();

        $this->recalculateEntry($entry);

        return redirect()->route('payroll.cutoffs.entries.show', [$cutoff, $entry])
            ->with('success', 'Deduction removed.');
    }

    private function recalculateEntry(PayrollEntry $entry): void
    {
        $standingTotal  = (float) $entry->payrollDeductions()->sum('amount');
        $variableTotal  = (float) $entry->payrollVariableDeductions()->sum('amount');
        $refundTotal    = (float) $entry->payrollRefunds()->sum('amount');
        $totalDeductions = round($standingTotal + $variableTotal, 2);
        $netPay          = round((float) $entry->gross_pay - $totalDeductions + $refundTotal, 2);

        $entry->update([
            'total_deductions' => $totalDeductions,
            'net_pay'          => $netPay,
        ]);
    }
}
