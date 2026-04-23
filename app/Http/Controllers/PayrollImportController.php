<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\PayrollCutoff;
use App\Models\PayrollEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PayrollImportController extends Controller
{
    public function create(): View
    {
        return view('payroll.import');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'excel_file' => ['required', 'file', 'mimes:xlsx,xls'],
        ]);

        $path = $request->file('excel_file')->getRealPath();

        try {
            $rows = $this->parseExcel($path);
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['excel_file' => 'Could not read the Excel file: ' . $e->getMessage()]);
        }

        if (empty($rows)) {
            return back()->withInput()->withErrors(['excel_file' => 'No employee data rows found in the uploaded file.']);
        }

        $allEmployees = Employee::all();

        $unmatched = [];
        $matched   = [];

        foreach ($rows as $row) {
            $employee = $this->matchEmployee($row['name'], $allEmployees);
            if (! $employee) {
                $unmatched[] = $row['name'];
            } else {
                $matched[] = ['row' => $row, 'employee' => $employee];
            }
        }

        if (! empty($unmatched)) {
            $list = implode(', ', $unmatched);
            return back()->withInput()->withErrors([
                'excel_file' => "Could not match the following employee(s): {$list}. Please correct the names in the Excel file and re-upload.",
            ]);
        }

        $cutoff = DB::transaction(function () use ($request, $matched) {
            $cutoff = PayrollCutoff::create([
                'branch_id'  => null,
                'name'       => $request->name,
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
                'status'     => 'draft',
            ]);

            foreach ($matched as $item) {
                $row      = $item['row'];
                $employee = $item['employee'];

                $existing = PayrollEntry::where('payroll_cutoff_id', $cutoff->id)
                    ->where('employee_id', $employee->id)
                    ->first();

                if ($existing) {
                    $existing->payrollDeductions()->delete();
                    $existing->payrollRefunds()->delete();
                    $existing->payrollVariableDeductions()->delete();
                    $existing->delete();
                }

                PayrollEntry::create([
                    'payroll_cutoff_id'           => $cutoff->id,
                    'employee_id'                 => $employee->id,
                    'daily_rate'                  => $row['daily_rate'],
                    'basic_pay'                   => $row['basic_pay'],
                    'gross_pay'                   => $row['basic_pay'],
                    'net_pay'                     => $row['net_pay'],
                    'thirteenth_month_allocation' => round($row['basic_pay'] / 12, 2),
                    'retirement_pay'              => round($row['daily_rate'] * 22.5 / 12 / 2, 2),
                    'is_imported'                 => true,
                ]);
            }

            $cutoff->update(['status' => 'finalized']);

            return $cutoff;
        });

        return redirect()
            ->route('payroll.cutoffs.show', $cutoff)
            ->with('success', count($matched) . ' employee records imported and payroll finalized.');
    }

    private function parseExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = $sheet->toArray(null, true, true, true);
        $records     = [];

        foreach ($rows as $rowNum => $row) {
            if ($rowNum === 1) {
                continue;
            }

            $name = trim((string) ($row['A'] ?? ''));
            if ($name === '') {
                continue;
            }

            $records[] = [
                'name'       => $name,
                'daily_rate' => $this->num($row['B'] ?? 0),
                'basic_pay'  => $this->num($row['C'] ?? 0),
                'net_pay'    => $this->num($row['D'] ?? 0),
            ];
        }

        return $records;
    }

    private function num(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        $clean = preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) $value));
        return (float) ($clean ?: 0);
    }

    private function matchEmployee(string $excelName, $employees): ?Employee
    {
        $parts     = explode(',', $excelName, 2);
        $lastName  = strtoupper(trim($parts[0]));
        $rest      = isset($parts[1]) ? strtoupper(trim($parts[1])) : '';
        $firstName = strtok($rest, ' ');

        foreach ($employees as $employee) {
            $empLast  = strtoupper(trim($employee->last_name));
            $empFirst = strtoupper(trim($employee->first_name));

            if ($empLast === $lastName && str_starts_with($empFirst, $firstName)) {
                return $employee;
            }
        }

        foreach ($employees as $employee) {
            if ($employee->nickname && strtoupper(trim($employee->nickname)) === strtoupper(trim($excelName))) {
                return $employee;
            }
        }

        return null;
    }
}
