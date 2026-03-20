<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function show(): View
    {
        return view('auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();
        $user->update([
            'password'             => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        if ($user->isStaff() && empty($user->signature)) {
            return redirect()->route('signature.setup');
        }

        if ($user->isStaff()) {
            return redirect()->route('staff.dashboard')->with('success', 'Password updated successfully.');
        }

        return redirect()->route('dashboard')->with('success', 'Password updated successfully.');
    }
}
