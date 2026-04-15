<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        $employee = Auth::user()->employee->load('branch');
        return view('staff.profile', compact('employee'));
    }

    public function updateSignature(Request $request): RedirectResponse
    {
        $request->validate([
            'signature' => 'required|string',
        ]);

        Auth::user()->update(['signature' => $request->signature]);

        return back()->with('success', 'Signature updated successfully.');
    }
}
