<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EmployeeImportController extends Controller
{
    /**
     * Download the Excel import template (served as a static file).
     */
    public function template(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $path = public_path('employee-import-template.xlsx');

        return response()->download(
            $path,
            'employee-import-template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * Process the uploaded Excel file and import employees.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        try {
            $path        = $request->file('file')->getRealPath();
            $spreadsheet = IOFactory::load($path);
            $sheet       = $spreadsheet->getSheet(0);
            $rows        = $sheet->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not read file: ' . $e->getMessage());
        }

        $branches = Branch::all()->keyBy('name');

        // Build a branch lookup: exact name → branch, plus lower-trimmed fallback
        $branchMap = [];
        foreach ($branches as $name => $branch) {
            $branchMap[strtolower(trim($name))] = $branch;
        }

        $imported = 0;
        $updated  = 0;
        $skipped  = [];

        // Skip row 0 (headers). Row 3 (index 3) is the notes row — skip if first cell looks like a note.
        foreach ($rows as $idx => $row) {
            if ($idx === 0) {
                continue; // header
            }

            $code      = trim((string) ($row[0] ?? ''));
            $firstName = trim((string) ($row[2] ?? ''));
            $lastName  = trim((string) ($row[4] ?? ''));
            $branchRaw = trim((string) ($row[17] ?? ''));

            // Skip blank or notes rows
            if ($code === '' || $firstName === '' || $lastName === '') {
                continue;
            }

            // Skip obvious notes rows (notes row starts with "Required" etc.)
            if (str_starts_with($code, 'Required') || str_starts_with($code, 'Optional')) {
                continue;
            }

            // Resolve branch
            $branch = $branchMap[strtolower(trim($branchRaw))] ?? null;
            if (!$branch) {
                // Partial match (e.g. "Ayala Abreeza" → "Abreeza")
                foreach ($branchMap as $key => $b) {
                    if (str_contains(strtolower($branchRaw), $key) || str_contains($key, strtolower($branchRaw))) {
                        $branch = $b;
                        break;
                    }
                }
            }

            if (!$branch) {
                $skipped[] = "Row " . ($idx + 1) . ": Unknown branch \"$branchRaw\" for $firstName $lastName ($code).";
                continue;
            }

            // Salary type logic
            $basicPay  = is_numeric($row[14] ?? '') ? (float) $row[14] : 0;
            $rateVal   = is_numeric($row[16] ?? '') ? (float) $row[16] : 0;

            if ($rateVal > 0) {
                $salaryType = 'daily';
                $rate       = $rateVal;
            } elseif ($basicPay > 0) {
                $salaryType = 'monthly';
                $rate       = $basicPay;
            } else {
                $salaryType = 'daily';
                $rate       = 0;
            }

            // Date fields
            $hiredDate = $this->parseDate($row[5] ?? '');
            $position  = trim((string) ($row[6] ?? '')) ?: null;
            $email     = trim((string) ($row[8] ?? '')) ?: null;
            $tinNo     = trim((string) ($row[10] ?? '')) ?: null;
            $sssNo     = trim((string) ($row[11] ?? '')) ?: null;
            $phicNo    = trim((string) ($row[12] ?? '')) ?: null;
            $pagibigNo = trim((string) ($row[13] ?? '')) ?: null;

            $data = [
                'first_name'  => $firstName,
                'last_name'   => $lastName,
                'branch_id'   => $branch->id,
                'salary_type' => $salaryType,
                'rate'        => $rate,
                'active'      => true,
            ];

            if ($position)  $data['position']   = $position;
            if ($hiredDate) $data['hired_date']  = $hiredDate;
            if ($email)     $data['email']       = $email;
            if ($tinNo)     $data['tin_no']      = $tinNo;
            if ($sssNo)     $data['sss_no']      = $sssNo;
            if ($phicNo)    $data['phic_no']     = $phicNo;
            if ($pagibigNo) $data['pagibig_no']  = $pagibigNo;

            $existing = Employee::where('employee_code', $code)->first();

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                Employee::create(array_merge($data, ['employee_code' => $code]));
                $imported++;
            }
        }

        $message = "$imported imported, $updated updated.";
        if ($skipped) {
            $message .= ' Skipped ' . count($skipped) . ' row(s): ' . implode(' | ', $skipped);
        }

        $flashKey = $skipped ? 'warning' : 'success';

        return redirect()->route('employees.index')->with($flashKey, $message);
    }

    private function parseDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $str = trim((string) $value);

        // Already YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
            return $str;
        }

        // PhpSpreadsheet may return a float (Excel date serial)
        if (is_numeric($str)) {
            try {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $str);
                return $date->format('Y-m-d');
            } catch (\Exception) {
                return null;
            }
        }

        // Try strtotime for other formats
        $ts = strtotime($str);
        return $ts ? date('Y-m-d', $ts) : null;
    }
}
