<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollCutoff;
use App\Models\PayrollDeduction;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryRefund;
use App\Models\PayrollEntryVariableDeduction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PayrollImportController extends Controller
{
    public function create(PayrollCutoff $cutoff): View|RedirectResponse
    {
        abort_if($cutoff->status !== 'draft', 403, 'Can only import into a draft payroll cutoff.');

        return view('payroll.cutoffs.import', compact('cutoff'));
    }

    public function store(Request $request, PayrollCutoff $cutoff): RedirectResponse
    {
        abort_if($cutoff->status !== 'draft', 403, 'Can only import into a draft payroll cutoff.');

        $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        $path = $request->file('excel_file')->getRealPath();

        try {
            $rows = $this->parseExcel($path);
        } catch (\Throwable $e) {
            return back()->withErrors(['excel_file' => 'Could not read the Excel file: ' . $e->getMessage()]);
        }

        if (empty($rows)) {
            return back()->withErrors(['excel_file' => 'No employee data rows found in the PAYROLL sheet.']);
        }

        // Match each row to an employee in this branch
        $branchEmployees = Employee::where('branch_id', $cutoff->branch_id)->get();

        $unmatched = [];
        $matched   = [];

        foreach ($rows as $row) {
            $employee = $this->matchEmployee($row['name'], $branchEmployees);
            if (! $employee) {
                $unmatched[] = $row['name'];
            } else {
                $matched[] = ['row' => $row, 'employee' => $employee];
            }
        }

        if (! empty($unmatched)) {
            $list = implode(', ', $unmatched);
            return back()->withErrors([
                'excel_file' => "Could not match the following employee(s) in this branch: {$list}. Please correct the names in the Excel file and re-upload.",
            ]);
        }

        DB::transaction(function () use ($cutoff, $matched) {
            foreach ($matched as $item) {
                $row      = $item['row'];
                $employee = $item['employee'];

                // Delete existing entry for this employee in this cutoff if any
                $existing = PayrollEntry::where('payroll_cutoff_id', $cutoff->id)
                    ->where('employee_id', $employee->id)
                    ->first();
                if ($existing) {
                    $existing->payrollDeductions()->delete();
                    $existing->payrollRefunds()->delete();
                    $existing->payrollVariableDeductions()->delete();
                    $existing->delete();
                }

                $entry = PayrollEntry::create([
                    'payroll_cutoff_id'           => $cutoff->id,
                    'employee_id'                 => $employee->id,
                    'daily_rate'                  => $row['daily_rate'],
                    'working_days'                => $row['working_days'],
                    'total_overtime_hours'        => $row['overtime_hours'],
                    'basic_pay'                   => $row['basic_pay'],
                    'retirement_pay'              => $row['retirement_pay'],
                    'thirteenth_month_allocation' => $row['thirteenth_month_allocation'],
                    'overtime_pay'                => $row['overtime_pay'],
                    'allowance_pay'               => $row['allowance_pay'],
                    'gross_pay'                   => $row['gross_pay'],
                    'total_deductions'            => $row['sss'] + $row['phic'] + $row['pagibig']
                                                     + $row['pagibig_loan'] + $row['sl_loan']
                                                     + $row['saving'] + $row['thirteenth_month_deduction'],
                    'net_pay'                     => $row['net_pay'],
                    'total_hours_worked'          => round($row['working_days'] * 8, 2),
                    'is_imported'                 => true,
                ]);

                // Standard government deductions
                if ($row['sss'] > 0) {
                    PayrollDeduction::create(['payroll_entry_id' => $entry->id, 'type' => 'SSS', 'amount' => $row['sss']]);
                }
                if ($row['phic'] > 0) {
                    PayrollDeduction::create(['payroll_entry_id' => $entry->id, 'type' => 'PhilHealth', 'amount' => $row['phic']]);
                }
                if ($row['pagibig'] > 0) {
                    PayrollDeduction::create(['payroll_entry_id' => $entry->id, 'type' => 'PagIBIG', 'amount' => $row['pagibig']]);
                }

                // Variable deductions
                foreach ([
                    'Pag-ibig Loan'  => $row['pagibig_loan'],
                    'S/L'            => $row['sl_loan'],
                    'Savings'        => $row['saving'],
                    '13th Month'     => $row['thirteenth_month_deduction'],
                ] as $label => $amount) {
                    if ($amount > 0) {
                        PayrollEntryVariableDeduction::create([
                            'payroll_entry_id' => $entry->id,
                            'description'      => $label,
                            'amount'           => $amount,
                        ]);
                    }
                }

                // Refunds
                foreach ([
                    'SSS Refund' => $row['sss_refund'],
                    'Refund'     => $row['refund'],
                ] as $label => $amount) {
                    if ($amount > 0) {
                        PayrollEntryRefund::create([
                            'payroll_entry_id' => $entry->id,
                            'description'      => $label,
                            'amount'           => $amount,
                        ]);
                    }
                }
            }

            $cutoff->update(['status' => 'finalized']);
        });

        return redirect()
            ->route('payroll.cutoffs.show', $cutoff)
            ->with('success', count($matched) . ' employee records imported and payroll finalized.');
    }

    private function parseExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet       = $spreadsheet->getSheetByName('PAYROLL');

        if (! $sheet) {
            throw new \RuntimeException('No sheet named "PAYROLL" found in the uploaded file.');
        }

        $rows    = $sheet->toArray(null, true, true, true);
        $records = [];

        foreach ($rows as $rowNum => $row) {
            // Data rows start at row 8; skip if no row number in col A
            if ($rowNum < 8) {
                continue;
            }

            $no = trim((string) ($row['A'] ?? ''));
            if (! is_numeric($no)) {
                continue; // totals row or empty
            }

            $name = trim((string) ($row['B'] ?? ''));
            if ($name === '' || ! str_contains($name, ',')) {
                continue; // skip subtotal/branch label rows
            }

            $records[] = [
                'name'                       => $name,
                'daily_rate'                 => $this->num($row['D'] ?? 0),
                'working_days'               => $this->num($row['E'] ?? 0),
                'overtime_hours'             => $this->num($row['F'] ?? 0),
                'basic_pay'                  => $this->num($row['G'] ?? 0),
                'retirement_pay'             => $this->num($row['H'] ?? 0),
                'thirteenth_month_allocation'=> $this->num($row['I'] ?? 0),
                'overtime_pay'               => $this->num($row['J'] ?? 0),
                'allowance_pay'              => $this->num($row['L'] ?? 0),
                'gross_pay'                  => $this->num($row['M'] ?? 0),
                'sss'                        => $this->num($row['N'] ?? 0),
                'phic'                       => $this->num($row['O'] ?? 0),
                'pagibig'                    => $this->num($row['P'] ?? 0),
                'pagibig_loan'               => $this->num($row['Q'] ?? 0),
                'sl_loan'                    => $this->num($row['R'] ?? 0),
                'saving'                     => $this->num($row['S'] ?? 0),
                'sss_refund'                 => $this->num($row['T'] ?? 0),
                'refund'                     => $this->num($row['U'] ?? 0),
                'thirteenth_month_deduction' => $this->num($row['V'] ?? 0),
                'net_pay'                    => $this->num($row['X'] ?? 0),
            ];
        }

        return $records;
    }

    private function num(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        // Remove commas, spaces, currency symbols
        $clean = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) $value));
        return (float) ($clean ?: 0);
    }

    private function matchEmployee(string $excelName, $employees): ?Employee
    {
        // Excel format: "LASTNAME, FIRSTNAME MIDDLENAME"
        $parts    = explode(',', $excelName, 2);
        $lastName = strtoupper(trim($parts[0]));
        $rest     = isset($parts[1]) ? strtoupper(trim($parts[1])) : '';
        // First word of rest is first name
        $firstName = strtok($rest, ' ');

        foreach ($employees as $employee) {
            $empLast  = strtoupper(trim($employee->last_name));
            $empFirst = strtoupper(trim($employee->first_name));

            if ($empLast === $lastName && str_starts_with($empFirst, $firstName)) {
                return $employee;
            }
        }

        // Fallback: match on nickname (like schedule upload does)
        if ($rest !== '') {
            foreach ($employees as $employee) {
                if ($employee->nickname && strtoupper(trim($employee->nickname)) === strtoupper(trim($excelName))) {
                    return $employee;
                }
            }
        }

        return null;
    }
}
