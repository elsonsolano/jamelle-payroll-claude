<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DailySchedule;
use App\Models\Employee;
use App\Models\ScheduleUpload;
use App\Services\ScheduleParserService;
use Illuminate\Http\Request;

class ScheduleUploadController extends Controller
{
    public function __construct(private ScheduleParserService $parser) {}

    public function index()
    {
        $uploads = ScheduleUpload::with('branch', 'uploader')
            ->latest()
            ->paginate(20);

        return view('schedule-uploads.index', compact('uploads'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        return view('schedule-uploads.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id'     => 'required|exists:branches,id',
            'schedule_json' => 'required|string',
        ]);

        $parsed = json_decode($request->input('schedule_json'), true);
        if (json_last_error() !== JSON_ERROR_NONE || ! isset($parsed['rows'])) {
            return back()->withInput()->withErrors(['schedule_json' => 'Invalid JSON. Make sure you copied the full output from claude.ai.']);
        }

        $matched = $this->parser->matchEmployees($parsed, $request->branch_id);

        $upload = ScheduleUpload::create([
            'branch_id'   => $request->branch_id,
            'uploaded_by' => auth()->id(),
            'label'       => $matched['month'] ?? null,
            'ai_response' => $matched,
            'status'      => 'review',
        ]);

        return redirect()->route('schedule-uploads.review', $upload)
            ->with('success', 'Schedule imported. Please review before applying.');
    }

    public function review(ScheduleUpload $schedule)
    {
        if ($schedule->status === 'applied') {
            return redirect()->route('schedule-uploads.index')
                ->with('info', 'This schedule has already been applied.');
        }

        $data      = $schedule->ai_response;
        $employees = Employee::where('branch_id', $schedule->branch_id)
            ->where('active', true)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'nickname']);

        return view('schedule-uploads.review', compact('schedule', 'data', 'employees'));
    }

    public function apply(Request $request, ScheduleUpload $schedule)
    {
        if ($schedule->status === 'applied') {
            return redirect()->route('schedule-uploads.index')
                ->with('info', 'This schedule has already been applied.');
        }

        $request->validate([
            'assignments' => 'required|string',
        ]);

        $assignments = json_decode($request->input('assignments'), true);
        if (! is_array($assignments)) {
            return back()->withErrors(['assignments' => 'Invalid assignment data.']);
        }

        // Resolve branch overrides once
        $branchCache     = [];
        $unmatchedNames  = collect($schedule->ai_response['unmatched_names'] ?? []);
        $nicknamesSaved  = []; // track employee IDs already updated

        foreach ($assignments as $row) {
            $employeeId = (int) ($row['employee_id'] ?? 0);
            if (! $employeeId) {
                continue; // skip unresolved employees
            }

            // Auto-save nickname when admin manually assigned an unmatched name
            $name = trim($row['name'] ?? '');
            if ($name && $unmatchedNames->contains($name) && ! in_array($employeeId, $nicknamesSaved)) {
                $employee = Employee::find($employeeId);
                if ($employee && ! $employee->nickname) {
                    $employee->update(['nickname' => $name]);
                }
                $nicknamesSaved[] = $employeeId;
            }

            $assignedBranchId = null;
            if (! empty($row['branch_override'])) {
                $key = strtolower($row['branch_override']);
                if (! array_key_exists($key, $branchCache)) {
                    $branchCache[$key] = $this->parser->resolveBranch($row['branch_override']);
                }
                $assignedBranchId = $branchCache[$key];
            }

            DailySchedule::updateOrCreate(
                [
                    'employee_id' => $employeeId,
                    'date'        => $row['date'],
                ],
                [
                    'schedule_upload_id' => $schedule->id,
                    'work_start_time'    => $row['work_start_time'] ?: null,
                    'work_end_time'      => $row['work_end_time'] ?: null,
                    'is_day_off'         => (bool) ($row['is_day_off'] ?? false),
                    'assigned_branch_id' => $assignedBranchId,
                    'notes'              => $row['notes'] ?: null,
                ]
            );
        }

        $schedule->update(['status' => 'applied']);

        return redirect()->route('schedule-uploads.index')
            ->with('success', "Schedule \"{$schedule->label}\" applied successfully.");
    }

    public function destroy(ScheduleUpload $schedule)
    {
        $schedule->delete();

        return redirect()->route('schedule-uploads.index')
            ->with('success', 'Schedule upload deleted.');
    }

    public function assignName(Request $request, ScheduleUpload $schedule)
    {
        $validated = $request->validate([
            'name'        => 'required|string',
            'employee_id' => 'required|exists:employees,id',
        ]);

        $employee = Employee::find($validated['employee_id']);

        if (! $employee->nickname) {
            $employee->update(['nickname' => $validated['name']]);
        }

        return response()->json([
            'employee_id'   => $employee->id,
            'employee_name' => $employee->full_name,
        ]);
    }
}
