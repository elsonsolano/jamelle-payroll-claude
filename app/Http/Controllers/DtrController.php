<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Dtr;
use App\Models\Employee;
use App\Models\PayrollCutoff;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DtrController extends Controller
{
    public function index(Request $request): View
    {
        $query = Dtr::with('employee.branch');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn($q) => $q->where('branch_id', $request->branch_id));
        }

        if ($request->filled('cutoff_id')) {
            $cutoff = PayrollCutoff::findOrFail($request->cutoff_id);
            $query->whereBetween('date', [$cutoff->start_date, $cutoff->end_date]);
        } else {
            if ($request->filled('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }
        }

        $dtrs      = $query->orderByDesc('date')->orderBy('employee_id')->paginate(30)->withQueryString();
        $employees = Employee::orderBy('last_name')->orderBy('first_name')->get();
        $branches  = Branch::orderBy('name')->get();
        $cutoffs   = PayrollCutoff::with('branch')->orderByDesc('start_date')->get();

        return view('dtrs.index', compact('dtrs', 'employees', 'branches', 'cutoffs'));
    }

    public function show(Dtr $dtr): View
    {
        $dtr->load('employee.branch');
        return view('dtrs.show', compact('dtr'));
    }
}
