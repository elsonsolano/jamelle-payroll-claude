<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeImportController extends Controller
{
    /**
     * Download the Excel import template.
     */
    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        // ── Sheet 1: Data ──────────────────────────────────────────────
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Employees');

        $headers = [
            'EE #',
            'FULL NAME',
            'FIRST NAME',
            'MIDDLE NAME',
            'LAST NAME',
            'DATE HIRED',
            'POSITION',
            'BIRTHDATE',
            'EMAIL ADDRESS',
            'MOBILE NUMBER',
            'TIN',
            'SSS',
            'PHEALTH',
            'PIBIG',
            'BASIC PAY',
            'ALLOWANCE',
            'RATE',
            'BRANCH',
        ];

        // Header row
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4338CA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:R1')->applyFromArray($headerStyle);

        // Sample rows
        $samples = [
            ['EMP-001', 'dela Cruz, Juan M.', 'Juan', 'Matapang', 'dela Cruz', '2023-01-15', 'Crew', '1995-06-20', 'juan@example.com', '09171234567', '123-456-789', '01-2345678-9', '12-345678901-2', '1234-5678-9012', 650.00, '', 650.00, 'Abreeza'],
            ['EMP-002', 'Santos, Maria L.',   'Maria', 'Lim',       'Santos',    '2022-03-01', 'Cashier', '1990-11-05', '', '', '', '', '', '', 18000.00, 2000.00, '', 'SM Lanang'],
        ];

        $sheet->fromArray($samples, null, 'A2');

        // Notes row
        $notesRow = 4;
        $notes = [
            'Required. Unique.',
            'Optional (ignored on import)',
            'Required',
            'Optional (ignored)',
            'Required',
            'Optional. YYYY-MM-DD',
            'Optional',
            'Optional. YYYY-MM-DD',
            'Optional',
            'Optional (ignored)',
            'Optional (ignored)',
            'Optional',
            'Optional',
            'Optional',
            'Daily rate (if daily employee)',
            'Optional (ignored)',
            'Same as BASIC PAY for daily; leave blank for monthly',
            'Must match a branch name exactly',
        ];
        $sheet->fromArray($notes, null, 'A' . $notesRow);
        $sheet->getStyle('A' . $notesRow . ':R' . $notesRow)->applyFromArray([
            'font' => ['italic' => true, 'color' => ['argb' => 'FF6B7280']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF9FAFB']],
        ]);

        // Column widths
        $widths = [10, 24, 14, 14, 14, 13, 16, 13, 24, 15, 14, 16, 16, 16, 12, 12, 10, 16];
        foreach ($widths as $i => $w) {
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth($w);
        }

        // Format date columns
        foreach (['F', 'H'] as $col) {
            $sheet->getStyle($col . '2:' . $col . '200')->getNumberFormat()->setFormatCode('YYYY-MM-DD');
        }

        // ── Sheet 2: Instructions ──────────────────────────────────────
        $info = $spreadsheet->createSheet();
        $info->setTitle('Instructions');

        $lines = [
            ['EMPLOYEE IMPORT TEMPLATE — INSTRUCTIONS'],
            [''],
            ['1. Fill in the "Employees" sheet. Row 1 = headers (do not modify). Row 4 = notes (you may delete it before importing).'],
            ['2. Required columns: EE # (unique employee code), FIRST NAME, LAST NAME, BRANCH.'],
            ['3. BRANCH must exactly match one of the branch names in the system (e.g. "Abreeza", "SM Lanang", "SM Ecoland", "NCCC", "Head Office").'],
            ['4. Salary type is determined automatically:'],
            ['   - If RATE column has a value > 0, the employee is treated as Daily with that daily rate.'],
            ['   - If RATE is blank/zero but BASIC PAY has a value, the employee is Monthly with that monthly rate.'],
            ['   - If both are blank, the employee is created as Monthly with rate = 0 (update manually).'],
            ['5. DATE HIRED and BIRTHDATE should be in YYYY-MM-DD format.'],
            ['6. Existing employees (matched by EE #) will be updated, not duplicated.'],
            ['7. Rows missing EE #, FIRST NAME, LAST NAME, or a valid BRANCH will be skipped and reported.'],
        ];

        $info->fromArray($lines, null, 'A1');
        $info->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13],
        ]);
        $info->getColumnDimension('A')->setWidth(110);

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'employee-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Process the uploaded Excel file and import employees.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120',
        ]);

        $path        = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $sheet       = $spreadsheet->getSheet(0);
        $rows        = $sheet->toArray(null, true, true, false); // 0-indexed

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
                $salaryType = 'monthly';
                $rate       = 0;
            }

            // Date fields
            $hiredDate = $this->parseDate($row[5] ?? '');
            $position  = trim((string) ($row[6] ?? '')) ?: null;
            $email     = trim((string) ($row[8] ?? '')) ?: null;
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
