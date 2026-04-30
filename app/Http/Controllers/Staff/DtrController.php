<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Dtr;
use App\Models\DtrLogEvent;
use App\Models\PayrollEntry;
use App\Notifications\OtSubmitted;
use App\Services\DtrComputationService;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DtrController extends Controller
{
    public function __construct(
        private DtrComputationService $computer,
        private GamificationService $gamification,
    ) {}

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
            'time_in'  => 'required|date_format:H:i',
            'am_out'   => 'nullable|date_format:H:i',
            'pm_in'    => 'nullable|date_format:H:i',
            'time_out' => 'nullable|date_format:H:i',
            'has_ot'   => 'boolean',
            'ot_hours' => 'nullable|numeric|min:0.5|max:24|required_if:has_ot,1',
            'notes'    => 'nullable|string|max:500',
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
            'status'         => 'Pending',
            'notes'          => $validated['notes'] ?? null,
            ...$computed,
        ]);

        foreach (['time_in', 'am_out', 'pm_in', 'time_out'] as $eventKey) {
            if (! empty($validated[$eventKey])) {
                $this->recordLogEvent($dtr, $eventKey, $validated[$eventKey], 'staff_form');
            }
        }

        if ($hasOt) {
            $approvers = DtrComputationService::getOtApprovers($employee, Auth::user());
            foreach ($approvers as $approver) {
                $approver->notify(new OtSubmitted($dtr));
            }
        }

        return redirect()->route('staff.dtr.index')
            ->with('success', 'DTR submitted successfully.' . ($hasOt ? ' Overtime request is pending approval.' : ''));
    }

    public function logEvent(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'event'    => 'required|in:time_in,am_out,pm_in,time_out',
            'time'     => 'required|date_format:H:i',
            'date'     => 'required|date|before_or_equal:today',
            'has_ot'   => 'boolean',
            'ot_hours' => 'nullable|numeric|min:0.5|max:24|required_if:has_ot,1',
            'notes'    => 'nullable|string|max:500',
        ]);

        $employee = Auth::user()->employee;

        $dtr = Dtr::firstOrNew([
            'employee_id' => $employee->id,
            'date'        => $validated['date'],
        ]);

        $this->ensureEditable($dtr);

        if (!$dtr->exists) {
            $dtr->source    = 'manual';
            $dtr->ot_status = 'none';
        } else {
            // Staff is editing an existing DTR — clear any prior admin approval
            $dtr->status_changed_by = null;
            $dtr->status_changed_at = null;
        }

        $dtr->status = 'Pending';

        $dtr->{$validated['event']} = $validated['time'];

        if (!empty($validated['notes'])) {
            $dtr->notes = $validated['notes'];
        }

        // Handle OT when logging Time Out
        $hasOt   = $validated['event'] === 'time_out' && $request->boolean('has_ot') && !empty($validated['ot_hours']);
        $otHours = $hasOt ? (float) $validated['ot_hours'] : (($dtr->overtime_hours > 0) ? (float) $dtr->overtime_hours : null);

        if ($validated['event'] === 'time_out') {
            $dtr->ot_status = $hasOt ? 'pending' : 'none';
        }

        $computed = $this->computer->compute(
            $employee,
            $validated['date'],
            $dtr->time_in,
            $dtr->am_out,
            $dtr->pm_in,
            $dtr->time_out,
            $otHours,
        );

        foreach ($computed as $key => $value) {
            $dtr->$key = $value;
        }

        // Compute ot_end_time
        if ($hasOt && $dtr->time_out) {
            $dtr->ot_end_time = \Carbon\Carbon::createFromTimeString($dtr->time_out)
                ->addMinutes((int) ($otHours * 60))
                ->format('H:i:s');
        }

        $dtr->save();

        $this->recordLogEvent($dtr, $validated['event'], $validated['time'], 'staff_dashboard');

        if ($hasOt) {
            $approvers = DtrComputationService::getOtApprovers($employee, Auth::user());
            foreach ($approvers as $approver) {
                $approver->notify(new OtSubmitted($dtr));
            }
        }

        $labels = [
            'time_in'  => 'Clock In',
            'am_out'   => 'Start Break',
            'pm_in'    => 'End Break',
            'time_out' => 'Clock Out',
        ];

        $loggedTimeDisplay = date('g:i A', strtotime($validated['time']));

        if ($request->expectsJson()) {
            $launchAt = \Carbon\Carbon::parse('2026-05-01 06:00:00', 'Asia/Manila');
            $celebration = now('Asia/Manila')->gte($launchAt)
                ? $this->gamification->celebrationData($employee, $dtr->fresh())
                : null;
            return response()->json([
                'success'      => true,
                'logged_time'  => $loggedTimeDisplay,
                'celebration'  => $celebration,
            ]);
        }

        return redirect()->route('staff.dashboard')
            ->with('success', $labels[$validated['event']] . ' logged at ' . $loggedTimeDisplay . '.' . ($hasOt ? ' Overtime request sent for approval.' : ''));
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
            'notes'    => 'nullable|string|max:500',
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
            'date'                => $validated['date'],
            'time_in'             => $validated['time_in'] ?? null,
            'am_out'              => $validated['am_out'] ?? null,
            'pm_in'               => $validated['pm_in'] ?? null,
            'time_out'            => $validated['time_out'] ?? null,
            'ot_end_time'         => $otEndTime,
            'ot_status'           => $hasOt ? 'pending' : 'none',
            'ot_approved_by'      => null,
            'ot_approved_at'      => null,
            'ot_rejection_reason' => null,
            'notes'               => $validated['notes'] ?? null,
            'status'              => 'Pending',
            'status_changed_by'   => null,
            'status_changed_at'   => null,
            ...$computed,
        ]);

        foreach (['time_in', 'am_out', 'pm_in', 'time_out'] as $eventKey) {
            if (! empty($validated[$eventKey])) {
                $this->recordLogEvent($dtr->fresh(), $eventKey, $validated[$eventKey], 'staff_form');
            }
        }

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

    private function recordLogEvent(Dtr $dtr, string $eventKey, string $loggedTime, string $source): void
    {
        DtrLogEvent::create([
            'dtr_id'       => $dtr->id,
            'employee_id'  => $dtr->employee_id,
            'work_date'    => $dtr->date,
            'event_key'    => $eventKey,
            'logged_time'  => $loggedTime,
            'submitted_at' => now(),
            'source'       => $source,
            'created_by'   => Auth::id(),
        ]);
    }
}
