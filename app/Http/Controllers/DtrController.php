<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DailySchedule;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\PayrollCutoff;
use App\Notifications\OtApproved;
use App\Notifications\OtRejected;
use App\Services\DtrComputationService;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DtrController extends Controller
{
    public function __construct(private DtrComputationService $computer) {}

    public function index(Request $request): View
    {
        $query = Dtr::with('employee.branch')->whereHas('employee');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $request->branch_id));
        }

        if ($request->filled('cutoff_id')) {
            $cutoff = PayrollCutoff::findOrFail($request->cutoff_id);
            $query->whereBetween('date', [$cutoff->start_date, $cutoff->end_date]);
            if ($cutoff->branch_id) {
                $query->whereHas('employee', fn($q) => $q->where('branch_id', $cutoff->branch_id));
            }
        } else {
            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }
        }

        if ($request->boolean('pending_ot')) {
            $query->where('ot_status', 'pending');
        }

        if ($request->boolean('pending_dtr')) {
            $query->where('status', 'Pending');
        }

        $dtrs      = $query->orderByDesc('date')->orderBy('employee_id')->paginate(30)->withQueryString();
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        $branches  = Branch::orderBy('name')->get();
        $cutoffs   = PayrollCutoff::with('branch')->orderByDesc('start_date')->get();

        return view('dtrs.index', compact('dtrs', 'employees', 'branches', 'cutoffs'));
    }

    public function export(Request $request): \Illuminate\Http\Response
    {
        $query = Dtr::with('employee.branch')->whereHas('employee');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $request->branch_id));
        }

        if ($request->filled('cutoff_id')) {
            $cutoff = PayrollCutoff::findOrFail($request->cutoff_id);
            $query->whereBetween('date', [$cutoff->start_date, $cutoff->end_date]);
            if ($cutoff->branch_id) {
                $query->whereHas('employee', fn($q) => $q->where('branch_id', $cutoff->branch_id));
            }
        } else {
            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }
        }

        $dtrs = $query->orderBy('date')->orderBy('employee_id')->get();

        // Determine date range label for the header
        if ($request->filled('cutoff_id')) {
            $cutoffModel = PayrollCutoff::find($request->cutoff_id);
            $dateFrom = $cutoffModel->start_date->format('M d, Y');
            $dateTo   = $cutoffModel->end_date->format('M d, Y');
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            $dateFrom = $request->filled('date_from') ? \Carbon\Carbon::parse($request->date_from)->format('M d, Y') : null;
            $dateTo   = $request->filled('date_to')   ? \Carbon\Carbon::parse($request->date_to)->format('M d, Y')   : null;
        } else {
            $dateFrom = $dtrs->isNotEmpty() ? \Carbon\Carbon::parse($dtrs->min('date'))->format('M d, Y') : null;
            $dateTo   = $dtrs->isNotEmpty() ? \Carbon\Carbon::parse($dtrs->max('date'))->format('M d, Y') : null;
        }

        // Group: branch name → employee_id → DTRs
        $grouped = $dtrs
            ->sortBy(fn($d) => $d->employee->branch->name)
            ->groupBy(fn($d) => $d->employee->branch->name)
            ->map(fn($branchDtrs) => $branchDtrs
                ->sortBy(fn($d) => $d->employee->last_name . $d->employee->first_name)
                ->groupBy('employee_id')
            );

        $pdf = Pdf::loadView('dtrs.export', compact('grouped', 'dateFrom', 'dateTo'))
                  ->setPaper('a4', 'landscape');

        $filename = 'dtr-export-' . now()->format('Y-m-d_His') . '.pdf';

        return $pdf->download($filename);
    }

    public function exportExcel(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = Dtr::with('employee.branch')->whereHas('employee');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $request->branch_id));
        }

        if ($request->filled('cutoff_id')) {
            $cutoff = PayrollCutoff::findOrFail($request->cutoff_id);
            $query->whereBetween('date', [$cutoff->start_date, $cutoff->end_date]);
            if ($cutoff->branch_id) {
                $query->whereHas('employee', fn($q) => $q->where('branch_id', $cutoff->branch_id));
            }
        } else {
            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }
        }

        $dtrs = $query->orderBy('date')->orderBy('employee_id')->get();

        // Group: branch name → employee_id → DTRs
        $grouped = $dtrs
            ->sortBy(fn($d) => $d->employee->branch->name)
            ->groupBy(fn($d) => $d->employee->branch->name)
            ->map(fn($branchDtrs) => $branchDtrs
                ->sortBy(fn($d) => $d->employee->last_name . $d->employee->first_name)
                ->groupBy('employee_id')
            );

        // Determine date range label
        if ($request->filled('cutoff_id')) {
            $cutoffModel = PayrollCutoff::find($request->cutoff_id);
            $dateLabel = $cutoffModel->start_date->format('M d, Y') . ' – ' . $cutoffModel->end_date->format('M d, Y');
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            $from = $request->filled('date_from') ? \Carbon\Carbon::parse($request->date_from)->format('M d, Y') : null;
            $to   = $request->filled('date_to')   ? \Carbon\Carbon::parse($request->date_to)->format('M d, Y')   : null;
            $dateLabel = implode(' – ', array_filter([$from, $to]));
        } else {
            $dateLabel = 'All records';
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // remove default blank sheet

        $sheetIndex = 0;

        foreach ($grouped as $branchName => $employeeGroups) {
            $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, substr($branchName, 0, 31));
            $spreadsheet->addSheet($sheet, $sheetIndex++);

            // Header row 1: title
            $sheet->mergeCells('A1:L1');
            $sheet->setCellValue('A1', 'Daily Time Record (DTR) – ' . $branchName);
            $sheet->getStyle('A1')->applyFromArray([
                'font'      => ['bold' => true, 'size' => 13],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);

            // Header row 2: date range
            $sheet->mergeCells('A2:L2');
            $sheet->setCellValue('A2', 'Period: ' . $dateLabel);
            $sheet->getStyle('A2')->applyFromArray([
                'font'      => ['color' => ['argb' => 'FF6B7280'], 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            ]);

            $currentRow = 3;

            $colHeaders = ['Date', 'Day', 'Rest Day', 'Time In', 'Start Break', 'End Break', 'Time Out', 'Hours', 'Billable', 'OT Hrs', 'Late (mins)', 'UT (mins)'];
            $colWidths  = [14,      10,    10,          11,        13,            11,           11,          8,        9,           8,        13,            12];

            foreach ($employeeGroups as $employeeId => $empDtrs) {
                $employee = $empDtrs->first()->employee;

                // Employee name row
                $currentRow++;
                $sheet->mergeCells("A{$currentRow}:L{$currentRow}");
                $sheet->setCellValue("A{$currentRow}", $employee->full_name);
                $sheet->getStyle("A{$currentRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10, 'color' => ['argb' => 'FF1D4ED8']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDBEAFE']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'indent' => 1],
                ]);

                // Column header row
                $currentRow++;
                foreach ($colHeaders as $colIdx => $header) {
                    $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                    $sheet->setCellValue("{$col}{$currentRow}", $header);
                    $sheet->getStyle("{$col}{$currentRow}")->applyFromArray([
                        'font'      => ['bold' => true, 'size' => 9],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF3F4F6']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    $sheet->getColumnDimensionByColumn($colIdx + 1)->setWidth($colWidths[$colIdx]);
                }
                $sheet->getStyle("A{$currentRow}:L{$currentRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                    ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFD1D5DB'));

                // DTR data rows
                foreach ($empDtrs as $dtr) {
                    $currentRow++;

                    $isOvernight = $dtr->time_in && $dtr->time_out &&
                        \Carbon\Carbon::createFromTimeString($dtr->time_out)
                            ->lte(\Carbon\Carbon::createFromTimeString($dtr->time_in));

                    $timeOut = $dtr->time_out
                        ? \Carbon\Carbon::parse($dtr->time_out)->format('h:i A') . ($isOvernight ? ' (+1)' : '')
                        : '—';

                    $dayLabel = $dtr->date->format('l') . ($dtr->is_rest_day ? ' · Rest' : '');

                    $row = [
                        $dtr->date->format('M d, Y'),
                        $dayLabel,
                        $dtr->is_rest_day ? 'Yes' : '—',
                        $dtr->time_in  ? \Carbon\Carbon::parse($dtr->time_in)->format('h:i A')  : '—',
                        $dtr->am_out   ? \Carbon\Carbon::parse($dtr->am_out)->format('h:i A')   : '—',
                        $dtr->pm_in    ? \Carbon\Carbon::parse($dtr->pm_in)->format('h:i A')    : '—',
                        $timeOut,
                        $dtr->time_in  ? number_format($dtr->total_hours, 2)                     : '—',
                        $dtr->time_in  ? number_format(min((float) $dtr->total_hours, 8.0), 2)  : '—',
                        ($dtr->overtime_hours > 0 && $dtr->ot_status !== 'rejected')
                            ? number_format($dtr->overtime_hours, 2)
                            : '—',
                        $dtr->late_mins > 0 ? $dtr->late_mins : '—',
                        $dtr->undertime_mins > 0 ? $dtr->undertime_mins : '—',
                    ];

                    foreach ($row as $colIdx => $value) {
                        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                        $sheet->setCellValue("{$col}{$currentRow}", $value);
                        $sheet->getStyle("{$col}{$currentRow}")->applyFromArray([
                            'font'      => ['size' => 9],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                    }

                    $sheet->getStyle("A{$currentRow}:L{$currentRow}")->getBorders()->getAllBorders()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)
                        ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFE5E7EB'));

                    // Zebra stripe
                    if ($currentRow % 2 === 0) {
                        $sheet->getStyle("A{$currentRow}:L{$currentRow}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFFAFAFA');
                    }

                    // Highlight rest day cell in amber
                    if ($dtr->is_rest_day) {
                        $sheet->getStyle("C{$currentRow}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFFEF3C7');
                        $sheet->getStyle("C{$currentRow}")->getFont()
                            ->setBold(true)
                            ->getColor()->setARGB('FF92400E');
                    }
                }
            }

            // Freeze top 2 rows
            $sheet->freezePane('A3');
        }

        $filename = 'dtr-export-' . now()->format('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    public function show(Dtr $dtr): View
    {
        $dtr->load('employee.branch', 'approvedBy', 'statusChangedBy');

        // Resolve schedule for this DTR's date — DailySchedule takes priority
        $dailySchedule = DailySchedule::where('employee_id', $dtr->employee_id)
            ->where('date', $dtr->date)
            ->first();

        $weeklySchedule = $dailySchedule ? null : EmployeeSchedule::where('employee_id', $dtr->employee_id)
            ->where('week_start_date', '<=', $dtr->date)
            ->orderByDesc('week_start_date')
            ->first();

        return view('dtrs.show', compact('dtr', 'dailySchedule', 'weeklySchedule'));
    }

    public function edit(Dtr $dtr): View
    {
        $dtr->load('employee.branch');
        return view('dtrs.edit', compact('dtr'));
    }

    public function toggleStatus(Dtr $dtr): JsonResponse
    {
        $newStatus = $dtr->status === 'Approved' ? 'Pending' : 'Approved';

        $dtr->update([
            'status'            => $newStatus,
            'status_changed_by' => Auth::id(),
            'status_changed_at' => now(),
        ]);

        return response()->json([
            'status'   => $newStatus,
            'by'       => Auth::user()->name,
            'at'       => now()->format('M d, Y h:i A'),
        ]);
    }

    public function update(Request $request, Dtr $dtr): RedirectResponse
    {
        $validated = $request->validate([
            'time_in'  => 'nullable|date_format:H:i',
            'am_out'   => 'nullable|date_format:H:i',
            'pm_in'    => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
            'ot_hours' => 'nullable|numeric|min:0.25|max:24',
            'notes'    => 'nullable|string|max:500',
            'status'   => 'nullable|in:Pending,Approved',
        ]);

        $otHours = !empty($validated['ot_hours']) ? (float) $validated['ot_hours'] : null;

        $computed = $this->computer->compute(
            $dtr->employee,
            $dtr->date->format('Y-m-d'),
            $validated['time_in'] ?? null,
            $validated['am_out'] ?? null,
            $validated['pm_in'] ?? null,
            $validated['time_out'] ?? null,
            $otHours,
        );

        // Recompute ot_end_time if time_out and ot_hours are present
        $otEndTime = null;
        if ($otHours && !empty($validated['time_out'])) {
            $otEndTime = \Carbon\Carbon::createFromTimeString($validated['time_out'])
                ->addMinutes((int) ($otHours * 60))
                ->format('H:i:s');
        }

        // If OT hours changed, reset ot_status to pending; otherwise preserve
        $prevOtHours = $dtr->overtime_hours;
        $newOtStatus = $dtr->ot_status;
        if ($otHours && abs($otHours - $prevOtHours) > 0.001) {
            $newOtStatus = 'pending';
        } elseif (!$otHours) {
            $newOtStatus = 'none';
        }

        $newStatus = $validated['status'] ?? $dtr->status;
        $statusChanged = $newStatus !== $dtr->status;

        $dtr->update([
            'time_in'           => $validated['time_in'] ?? null,
            'am_out'            => $validated['am_out'] ?? null,
            'pm_in'             => $validated['pm_in'] ?? null,
            'time_out'          => $validated['time_out'] ?? null,
            'ot_end_time'       => $otEndTime,
            'ot_status'         => $newOtStatus,
            'notes'             => $validated['notes'] ?? null,
            'status'            => $newStatus,
            'status_changed_by' => $statusChanged ? Auth::id() : $dtr->status_changed_by,
            'status_changed_at' => $statusChanged ? now()       : $dtr->status_changed_at,
            ...$computed,
        ]);

        return redirect()->route('dtr.show', $dtr)
            ->with('success', 'DTR updated successfully.');
    }

    public function approveOt(Dtr $dtr): RedirectResponse
    {
        abort_unless($dtr->ot_status === 'pending', 422, 'This OT request is not pending.');

        $dtr->update([
            'ot_status'           => 'approved',
            'ot_approved_by'      => Auth::id(),
            'ot_approved_at'      => now(),
            'ot_rejection_reason' => null,
        ]);

        $staffUser = $dtr->employee->user;
        if ($staffUser) {
            $staffUser->notify(new OtApproved($dtr, Auth::user()->name));
        }

        return back()->with('success', "OT for {$dtr->employee->full_name} on {$dtr->date->format('M d, Y')} approved.");
    }

    public function rejectOt(Request $request, Dtr $dtr): RedirectResponse
    {
        abort_unless($dtr->ot_status === 'pending', 422, 'This OT request is not pending.');

        $request->validate(['reason' => 'nullable|string|max:500']);

        $dtr->update([
            'ot_status'           => 'rejected',
            'ot_approved_by'      => Auth::id(),
            'ot_approved_at'      => now(),
            'ot_rejection_reason' => $request->reason,
            'overtime_hours'      => 0,
        ]);

        $staffUser = $dtr->employee->user;
        if ($staffUser) {
            $staffUser->notify(new OtRejected($dtr, Auth::user()->name));
        }

        return back()->with('success', "OT for {$dtr->employee->full_name} on {$dtr->date->format('M d, Y')} rejected.");
    }
}
