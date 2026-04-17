<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DailySchedule;
use App\Models\ScheduleChangeRequest;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(): View
    {
        $employee = Auth::user()->employee;

        // Show 5 days back + today + 15 days ahead
        $start = today()->subDays(5);
        $end   = today()->addDays(15);

        $dailySchedules = DailySchedule::where('employee_id', $employee->id)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy(fn($d) => $d->date->toDateString());

        // Get the most recent weekly schedule that covers the start of our window
        $weeklySchedule = $employee->employeeSchedules()
            ->where('week_start_date', '<=', $start->toDateString())
            ->orderByDesc('week_start_date')
            ->first();

        // Build a day-by-day schedule for the window
        $days = [];
        foreach (CarbonPeriod::create($start, $end) as $date) {
            $dateStr = $date->toDateString();
            $daily   = $dailySchedules->get($dateStr);

            if ($daily) {
                $days[$dateStr] = [
                    'date'       => $date->copy(),
                    'source'     => 'daily',
                    'is_day_off' => $daily->is_day_off,
                    'start'      => $daily->work_start_time,
                    'end'        => $daily->work_end_time,
                    'notes'      => $daily->notes,
                    'branch'     => $daily->assignedBranch?->name,
                ];
            } elseif ($weeklySchedule) {
                $restDays  = $weeklySchedule->rest_days ?? ['Sunday'];
                $isRestDay = in_array($date->format('l'), $restDays);
                $days[$dateStr] = [
                    'date'       => $date->copy(),
                    'source'     => 'weekly',
                    'is_day_off' => $isRestDay,
                    'start'      => $isRestDay ? null : $weeklySchedule->work_start_time,
                    'end'        => $isRestDay ? null : $weeklySchedule->work_end_time,
                    'notes'      => null,
                    'branch'     => null,
                ];
            } else {
                $days[$dateStr] = [
                    'date'       => $date->copy(),
                    'source'     => 'none',
                    'is_day_off' => null,
                    'start'      => null,
                    'end'        => null,
                    'notes'      => null,
                    'branch'     => null,
                ];
            }
        }

        // Pending/rejected change requests in this window (pending takes priority per date)
        $allChangeRequests = ScheduleChangeRequest::where('employee_id', $employee->id)
            ->whereBetween('date', [$start, $end])
            ->whereIn('status', ['pending', 'rejected'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END, created_at DESC")
            ->get();

        $changeRequests = $allChangeRequests
            ->unique(fn($r) => $r->date->toDateString())
            ->keyBy(fn($r) => $r->date->toDateString());

        return view('staff.schedule', compact('days', 'employee', 'changeRequests'));
    }
}
