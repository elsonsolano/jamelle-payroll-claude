<?php

namespace App\Http\Controllers;

use App\Models\PayrollCutoff;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryRefund;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayrollEntryRefundController extends Controller
{
    public function store(Request $request, PayrollCutoff $cutoff, PayrollEntry $entry): RedirectResponse
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount'      => 'required|numeric|min:0.01',
        ]);

        $entry->payrollRefunds()->create($validated);

        $this->recalculateNetPay($entry);

        return redirect()->route('payroll.cutoffs.entries.show', [$cutoff, $entry])
            ->with('success', 'Refund added.');
    }

    public function destroy(PayrollCutoff $cutoff, PayrollEntry $entry, PayrollEntryRefund $refund): RedirectResponse
    {
        $refund->delete();

        $this->recalculateNetPay($entry);

        return redirect()->route('payroll.cutoffs.entries.show', [$cutoff, $entry])
            ->with('success', 'Refund removed.');
    }

    private function recalculateNetPay(PayrollEntry $entry): void
    {
        $totalRefunds = (float) $entry->payrollRefunds()->sum('amount');
        $entry->update([
            'net_pay' => round((float) $entry->gross_pay - (float) $entry->total_deductions + $totalRefunds, 2),
        ]);
    }
}
