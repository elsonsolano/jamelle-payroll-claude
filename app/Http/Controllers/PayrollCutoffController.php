<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\PayrollCutoff;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PayrollCutoffController extends Controller
{
    public function index(Request $request): View
    {
        $query = PayrollCutoff::with('branch')
            ->withCount('payrollEntries')
            ->withSum('payrollEntries as total_basic_pay', 'basic_pay')
            ->withSum('payrollEntries as total_deductions', 'total_deductions')
            ->withSum('payrollEntries as total_net_pay', 'net_pay');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } elseif (! $request->boolean('show_voided')) {
            $query->where('status', '!=', 'voided');
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
            'branch_ids'   => 'required|array|min:1',
            'branch_ids.*' => 'exists:branches,id',
            'name'         => 'required|string|max:255',
            'start_date'   => 'required|date',
            'end_date'     => 'required|date|after_or_equal:start_date',
        ]);

        foreach ($validated['branch_ids'] as $branchId) {
            PayrollCutoff::create([
                'branch_id'  => $branchId,
                'name'       => $validated['name'],
                'start_date' => $validated['start_date'],
                'end_date'   => $validated['end_date'],
                'status'     => 'draft',
            ]);
        }

        $count = count($validated['branch_ids']);
        $label = $count === 1 ? '1 cutoff created.' : "{$count} cutoffs created.";

        return redirect()->route('payroll.cutoffs.index')->with('success', $label);
    }

    public function show(Request $request, PayrollCutoff $cutoff): View|Response
    {
        if ($request->query('export') === 'pdf') {
            return $this->pdf($cutoff);
        }

        $cutoff->load('branch');
        $entries = $cutoff->payrollEntries()
            ->with('employee')
            ->join('employees', 'employees.id', '=', 'payroll_entries.employee_id')
            ->orderBy('employees.last_name')
            ->orderBy('employees.first_name')
            ->select('payroll_entries.*')
            ->paginate(30);

        $summary = [
            'total_employees'   => $cutoff->payrollEntries()->count(),
            'total_basic_pay'   => $cutoff->payrollEntries()->sum('basic_pay'),
            'total_overtime'    => $cutoff->payrollEntries()->sum('overtime_pay'),
            'total_deductions'  => $cutoff->payrollEntries()->sum('total_deductions'),
            'total_net_pay'     => $cutoff->payrollEntries()->sum('net_pay'),
            'total_acknowledged'=> $cutoff->payrollEntries()->whereNotNull('acknowledged_at')->count(),
        ];

        return view('payroll.cutoffs.show', compact('cutoff', 'entries', 'summary'));
    }

    public function pdf(PayrollCutoff $cutoff): Response
    {
        $cutoff->load('branch');

        $entries = $cutoff->payrollEntries()
            ->with('employee', 'payrollRefunds')
            ->join('employees', 'employees.id', '=', 'payroll_entries.employee_id')
            ->orderBy('employees.last_name')
            ->orderBy('employees.first_name')
            ->select('payroll_entries.*')
            ->get();

        $summary = [
            'total_employees'  => $entries->count(),
            'total_basic_pay'  => $entries->sum('basic_pay'),
            'total_overtime'   => $entries->sum('overtime_pay'),
            'total_holiday'    => $entries->sum('holiday_pay'),
            'total_allowance'  => $entries->sum('allowance_pay'),
            'total_gross_pay'  => $entries->sum('gross_pay'),
            'total_deductions' => $entries->sum('total_deductions'),
            'total_refunds'    => $entries->sum(fn ($entry) => $entry->payrollRefunds->sum('amount')),
            'total_net_pay'    => $entries->sum('net_pay'),
        ];

        $pdf = Pdf::loadView('payroll.cutoffs.pdf', compact('cutoff', 'entries', 'summary'))
            ->setPaper('a4', 'landscape');

        $filename = 'payroll-cutoff-' . str($cutoff->name)->slug() . '-' . $cutoff->id . '.pdf';

        return $pdf->download($filename);
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
            'status'     => 'required|in:draft,processing,finalized,voided',
        ]);

        $cutoff->update($validated);

        return redirect()->route('payroll.cutoffs.show', $cutoff)->with('success', 'Cutoff updated successfully.');
    }

    public function void(Request $request, PayrollCutoff $cutoff): RedirectResponse
    {
        $request->validate([
            'void_reason' => 'required|string|max:500',
        ]);

        if ($cutoff->status !== 'finalized') {
            return redirect()->route('payroll.cutoffs.show', $cutoff)
                ->with('error', 'Only finalized cutoffs can be voided.');
        }

        $cutoff->update([
            'status'      => 'voided',
            'void_reason' => $request->void_reason,
        ]);

        return redirect()->route('payroll.cutoffs.show', $cutoff)
            ->with('success', 'Payroll cutoff has been voided.');
    }

    public function unvoid(PayrollCutoff $cutoff): RedirectResponse
    {
        if ($cutoff->status !== 'voided') {
            return redirect()->route('payroll.cutoffs.show', $cutoff)
                ->with('error', 'This cutoff is not voided.');
        }

        $cutoff->update([
            'status'      => 'finalized',
            'void_reason' => null,
        ]);

        return redirect()->route('payroll.cutoffs.show', $cutoff)
            ->with('success', 'Payroll cutoff has been reinstated.');
    }

    public function bdoExport(PayrollCutoff $cutoff): Response
    {
        $entries = $cutoff->payrollEntries()
            ->with('employee')
            ->join('employees', 'employees.id', '=', 'payroll_entries.employee_id')
            ->orderBy('employees.last_name')
            ->orderBy('employees.first_name')
            ->select('payroll_entries.*')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('BDO Payroll');

        $row = 1;
        foreach ($entries as $entry) {
            $employee = $entry->employee;
            $name     = strtoupper(trim($employee->first_name . ' ' . $employee->last_name));

            $sheet->setCellValue('A' . $row, $employee->bdo_account_number ?? '');
            $sheet->setCellValue('B' . $row, round((float) $entry->net_pay, 2));
            $sheet->setCellValue('C' . $row, $name);
            $sheet->setCellValue('D' . $row, ''); // REMARKS — left blank

            // Format amount as plain decimal number (no currency symbol)
            $sheet->getStyle('B' . $row)
                ->getNumberFormat()
                ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED2);

            $row++;
        }

        // Auto-size columns
        foreach (['A', 'B', 'C', 'D'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'BDO-' . str_replace(' ', '-', $cutoff->name) . '-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function destroy(PayrollCutoff $cutoff): RedirectResponse
    {
        $cutoff->delete();
        return redirect()->route('payroll.cutoffs.index')->with('success', 'Payroll cutoff deleted.');
    }
}
