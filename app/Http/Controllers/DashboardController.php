<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\PayrollCutoff;

class DashboardController extends Controller
{
    public function index()
    {
        $totalEmployees    = Employee::where('active', true)->count();
        $totalBranches     = Branch::count();
        $activeCutoffs     = PayrollCutoff::whereIn('status', ['draft', 'processing'])->count();
        $dtrToday          = Dtr::whereDate('date', today())->count();

        $recentCutoffs = PayrollCutoff::with('branch')
            ->orderByDesc('start_date')
            ->limit(5)
            ->get();

        $employeesByBranch = Branch::withCount(['employees' => function ($q) {
            $q->where('active', true);
        }])->get();

        // Calendar events — preloaded for client-side month navigation
        $calendarEvents = $this->buildCalendarEvents();

        return view('dashboard', compact(
            'totalEmployees',
            'totalBranches',
            'activeCutoffs',
            'dtrToday',
            'recentCutoffs',
            'employeesByBranch',
            'calendarEvents'
        ));
    }

    private function buildCalendarEvents(): array
    {
        $holidays = Holiday::orderBy('date')->get()->map(fn ($h) => [
            'date' => $h->date->format('Y-m-d'),
            'name' => $h->name,
            'type' => $h->type,
        ])->values()->toArray();

        $employees = Employee::where('active', true)
            ->select('first_name', 'last_name', 'birthday', 'hired_date')
            ->get();

        $birthdays = $employees
            ->filter(fn ($e) => $e->birthday !== null)
            ->map(fn ($e) => [
                'month' => (int) $e->birthday->month,
                'day'   => (int) $e->birthday->day,
                'name'  => $e->full_name,
            ])->values()->toArray();

        $anniversaries = $employees
            ->filter(fn ($e) => $e->hired_date !== null)
            ->map(fn ($e) => [
                'month'      => (int) $e->hired_date->month,
                'day'        => (int) $e->hired_date->day,
                'name'       => $e->full_name,
                'hire_year'  => (int) $e->hired_date->year,
            ])->values()->toArray();

        return compact('holidays', 'birthdays', 'anniversaries');
    }
}
