<?php

namespace App\Http\Controllers;

use App\Models\AttendanceScore;
use App\Models\PayrollCutoff;
use Illuminate\View\View;

class AttendanceScoreController extends Controller
{
    public function index(PayrollCutoff $cutoff): View
    {
        $cutoff->load('branch');

        $scores = $cutoff->attendanceScores()
            ->with('employee.branch', 'employeeAttendanceBadges.badge')
            ->join('employees', 'employees.id', '=', 'attendance_scores.employee_id')
            ->orderByDesc('attendance_scores.total_points')
            ->orderBy('employees.last_name')
            ->orderBy('employees.first_name')
            ->select('attendance_scores.*')
            ->paginate(30);

        return view('attendance-scores.index', compact('cutoff', 'scores'));
    }

    public function show(PayrollCutoff $cutoff, AttendanceScore $attendanceScore): View
    {
        abort_if($attendanceScore->payroll_cutoff_id !== $cutoff->id, 404);

        $cutoff->load('branch');
        $attendanceScore->load('employee.branch', 'items.dtr', 'employeeAttendanceBadges.badge');

        $items = $attendanceScore->items
            ->sortBy([
                ['work_date', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        return view('attendance-scores.show', compact('cutoff', 'attendanceScore', 'items'));
    }
}
