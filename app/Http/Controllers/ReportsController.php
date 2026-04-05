<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Dtr;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function lates(Request $request)
    {
        $branches = Branch::orderBy('name')->get();

        $from     = $request->input('from', now()->startOfMonth()->toDateString());
        $to       = $request->input('to', now()->toDateString());
        $branchId = $request->input('branch_id');
        $search   = $request->input('search');

        $query = Dtr::query()
            ->where('late_mins', '>', 0)
            ->whereBetween('date', [$from, $to])
            ->with('employee.branch')
            ->orderBy('date');

        if ($branchId) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $branchId));
        }

        if ($search) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", ["%{$search}%"]);
            });
        }

        $grouped = $query->get()
            ->groupBy('employee_id')
            ->map(function ($dtrs) {
                $employee = $dtrs->first()->employee;
                return [
                    'employee'        => $employee,
                    'occurrences'     => $dtrs->count(),
                    'total_late_mins' => $dtrs->sum('late_mins'),
                    'dtrs'            => $dtrs->sortBy('date')->values(),
                ];
            })
            ->sortBy(fn($row) => $row['employee']->full_name)
            ->values();

        return view('reports.lates', compact('branches', 'grouped', 'from', 'to', 'branchId', 'search'));
    }
}
