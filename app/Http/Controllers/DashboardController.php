<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
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

        return view('dashboard', compact(
            'totalEmployees',
            'totalBranches',
            'activeCutoffs',
            'dtrToday',
            'recentCutoffs',
            'employeesByBranch'
        ));
    }
}
