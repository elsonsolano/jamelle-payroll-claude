<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Dtr;
use App\Models\PayrollEntry;
use App\Notifications\OtSubmitted;
use App\Services\DtrComputationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DtrController extends Controller
{
    public function __construct(private DtrComputationService $computer) {}

    public function index(): View
    {
        $employee = Auth::user()->employee;
        $dtrs = $employee->dtrs()->orderByDesc('date')->paginate(20);

        return view('staff.dtr.index', compact('dtrs', 'employee'));
    }

    public function create(): View
    {
        return view('staff.dtr.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date'     => 'required|date|before_or_equal:today',
            'time_in'  => 'nullable|date_format:H:i',
            'am_out'   => 'nullable|date_format:H:i',
            'pm_in'    => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
            'has_ot'   => 'boolean',
            'ot_hours' => 'nullable|numeric|min:0.5|max:24|required_if:has_ot,1',
        ]);

        $employee = Auth::user()->employee;

        // Check if DTR already exists for this date
        $existing = Dtr::where('employee_id', $employee->id)
            ->where('date', $validated['date'])
            ->first();

        if ($existing) {
            return back()->withErrors(['date' => 'A DTR already exists for this date.'])->withInput();
        }

        $hasOt    = $request->boolean('has_ot') && !empty($validated['ot_hours']);
        $otHours  = $hasOt ? (float) $validated['ot_hours'] : null;

        $computed = $this->computer->compute(
            $employee,
            $validated['date'],
            $validated['time_in'] ?? null,
            $validated['am_out'] ?? null,
            $validated['pm_in'] ?? null,
            $validated['time_out'] ?? null,
            $otHours,
        );

        // Derive ot_end_time from time_out + ot_hours (for display in approval page)
        $otEndTime = null;
        if ($hasOt && !empty($validated['time_out'])) {
            $otEndTime = \Carbon\Carbon::createFromTimeString($validated['time_out'])
                ->addMinutes((int) ($otHours * 60))
                ->format('H:i:s');
        }

        $dtr = Dtr::create([
            'employee_id'    => $employee->id,
            'date'           => $validated['date'],
            'time_in'        => $validated['time_in'] ?? null,
            'am_out'         => $validated['am_out'] ?? null,
            'pm_in'          => $validated['pm_in'] ?? null,
            'time_out'       => $validated['time_out'] ?? null,
            'ot_end_time'    => $otEndTime,
            'ot_status'      => $hasOt ? 'pending' : 'none',
            'source'         => 'manual',
            'status'         => 'Approved',
            ...$computed,
        ]);

        if ($hasOt) {
            $approvers = DtrComputationService::getOtApprovers($employee, Auth::user());
            foreach ($approvers as $approver) {
                $approver->notify(new OtSubmitted($dtr));
            }
        }

        return redirect()->route('staff.dtr.index')
            ->with('success', 'DTR submitted successfully.' . ($hasOt ? ' Overtime request is pending approval.' : ''));
    }

    public function edit(Dtr $dtr): View
    {
        $this->authorizeOwner($dtr);
        $this->ensureEditable($dtr);

        return view('staff.dtr.edit', compact('dtr'));
    }

    public function update(Request $request, Dtr $dtr): RedirectResponse
    {
        $this->authorizeOwner($dtr);
        $this->ensureEditable($dtr);

        $validated = $request->validate([
            'date'     => 'required|date|before_or_equal:today',
            'time_in'  => 'nullable|date_format:H:i',
            'am_out'   => 'nullable|date_format:H:i',
            'pm_in'    => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
            'has_ot'   => 'boolean',
            'ot_hours' => 'nullable|numeric|min:0.5|max:24|required_if:has_ot,1',
        ]);

        $employee = Auth::user()->employee;

        $hasOt   = $request->boolean('has_ot') && !empty($validated['ot_hours']);
        $otHours = $hasOt ? (float) $validated['ot_hours'] : null;
        $wasOt   = $dtr->ot_status !== 'none';

        $computed = $this->computer->compute(
            $employee,
            $validated['date'],
            $validated['time_in'] ?? null,
            $validated['am_out'] ?? null,
            $validated['pm_in'] ?? null,
            $validated['time_out'] ?? null,
            $otHours,
        );

        $otEndTime = null;
        if ($hasOt && !empty($validated['time_out'])) {
            $otEndTime = \Carbon\Carbon::createFromTimeString($validated['time_out'])
                ->addMinutes((int) ($otHours * 60))
                ->format('H:i:s');
        }

        $dtr->update([
            'time_in'             => $validated['time_in'] ?? null,
            'am_out'              => $validated['am_out'] ?? null,
            'pm_in'               => $validated['pm_in'] ?? null,
            'time_out'            => $validated['time_out'] ?? null,
            'ot_end_time'         => $otEndTime,
            'ot_status'           => $hasOt ? 'pending' : 'none',
            'ot_approved_by'      => null,
            'ot_approved_at'      => null,
            'ot_rejection_reason' => null,
            ...$computed,
        ]);

        // Notify approvers if OT was newly added or re-submitted after rejection
        if ($hasOt && (!$wasOt || $dtr->wasChanged('ot_status'))) {
            $approvers = DtrComputationService::getOtApprovers($employee, Auth::user());
            foreach ($approvers as $approver) {
                $approver->notify(new OtSubmitted($dtr->fresh()));
            }
        }

        return redirect()->route('staff.dtr.index')
            ->with('success', 'DTR updated successfully.' . ($hasOt ? ' Overtime request is pending approval.' : ''));
    }

    private function authorizeOwner(Dtr $dtr): void
    {
        if ($dtr->employee_id !== Auth::user()->employee_id) {
            abort(403);
        }
    }

    private function ensureEditable(Dtr $dtr): void
    {
        // Block editing if DTR date is within a finalized payroll
        $finalized = PayrollEntry::whereHas('payrollCutoff', function ($q) use ($dtr) {
            $q->where('status', 'finalized')
              ->where('start_date', '<=', $dtr->date)
              ->where('end_date', '>=', $dtr->date);
        })->where('employee_id', $dtr->employee_id)->exists();

        if ($finalized) {
            abort(403, 'This DTR is part of a finalized payroll and cannot be edited.');
        }
    }
}
