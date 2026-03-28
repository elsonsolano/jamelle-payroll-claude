<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function impersonate(Employee $employee): RedirectResponse
    {
        $staffUser = $employee->user;

        abort_unless($staffUser && $staffUser->isStaff(), 422, 'This employee has no staff account.');

        session([
            'impersonator_id'         => Auth::id(),
            'impersonator_return_url' => url()->previous(),
        ]);

        Auth::login($staffUser);

        return redirect()->route('staff.dashboard');
    }

    public function exit(): RedirectResponse
    {
        $adminId    = session('impersonator_id');
        $returnUrl  = session('impersonator_return_url', route('employees.index'));

        abort_unless($adminId, 403);

        $admin = \App\Models\User::findOrFail($adminId);

        Auth::login($admin);

        session()->forget(['impersonator_id', 'impersonator_return_url']);

        return redirect($returnUrl);
    }
}
