<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Employee::with('branch');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%$s%")
                  ->orWhere('last_name', 'like', "%$s%")
                  ->orWhere('employee_code', 'like', "%$s%")
                  ->orWhere('position', 'like', "%$s%");
            });
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('status')) {
            $query->where('active', $request->status === 'active');
        }

        $employees = $query->orderBy('last_name')->orderBy('first_name')->paginate(20)->withQueryString();
        $branches  = Branch::orderBy('name')->get();

        return view('employees.index', compact('employees', 'branches'));
    }

    public function create(): View
    {
        $branches = Branch::orderBy('name')->get();
        return view('employees.create', compact('branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'nullable|email|max:255|unique:employees,email',
            'employee_code' => 'required|string|max:50|unique:employees,employee_code',
            'branch_id'     => 'required|exists:branches,id',
            'timemark_id'   => 'nullable|string|max:100|unique:employees,timemark_id',
            'salary_type'   => 'required|in:daily,monthly',
            'rate'          => 'required|numeric|min:0',
            'hired_date'    => 'nullable|date',
            'position'      => 'nullable|string|max:255',
            'active'        => 'boolean',
        ]);

        $validated['active'] = $request->boolean('active', true);

        Employee::create($validated);

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee): View
    {
        $employee->load('branch', 'employeeStandingDeductions', 'user');
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee): View
    {
        $branches = Branch::orderBy('name')->get();
        return view('employees.edit', compact('employee', 'branches'));
    }

    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'email'         => 'nullable|email|max:255|unique:employees,email,' . $employee->id,
            'employee_code' => 'required|string|max:50|unique:employees,employee_code,' . $employee->id,
            'branch_id'     => 'required|exists:branches,id',
            'timemark_id'   => 'nullable|string|max:100|unique:employees,timemark_id,' . $employee->id,
            'salary_type'   => 'required|in:daily,monthly',
            'rate'          => 'required|numeric|min:0',
            'hired_date'    => 'nullable|date',
            'position'      => 'nullable|string|max:255',
            'active'        => 'boolean',
        ]);

        $validated['active'] = $request->boolean('active');

        $employee->update($validated);

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }

    // --- Staff account management ---

    public function createAccount(Request $request, Employee $employee): RedirectResponse
    {
        if ($employee->user) {
            return back()->with('error', 'This employee already has a login account.');
        }

        $request->validate([
            'email'          => 'required|email|unique:users,email',
            'can_approve_ot' => 'boolean',
        ]);

        User::create([
            'name'                 => $employee->full_name,
            'email'                => $request->email,
            'password'             => Hash::make('password'),
            'role'                 => 'staff',
            'employee_id'          => $employee->id,
            'can_approve_ot'       => $request->boolean('can_approve_ot'),
            'must_change_password' => true,
        ]);

        return back()->with('success', 'Account created. Default password is: password');
    }

    public function updateAccount(Request $request, Employee $employee): RedirectResponse
    {
        if (!$employee->user) {
            return back()->with('error', 'No account found for this employee.');
        }

        $request->validate([
            'can_approve_ot' => 'boolean',
        ]);

        $employee->user->update([
            'can_approve_ot' => $request->boolean('can_approve_ot'),
        ]);

        return back()->with('success', 'Account updated.');
    }

    public function resetPassword(Employee $employee): RedirectResponse
    {
        if (!$employee->user) {
            return back()->with('error', 'No account found for this employee.');
        }

        $employee->user->update([
            'password'             => Hash::make('password'),
            'must_change_password' => true,
        ]);

        return back()->with('success', 'Password has been reset to: password');
    }
}
