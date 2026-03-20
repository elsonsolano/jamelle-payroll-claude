<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SetupSignatureController extends Controller
{
    public function show(): View
    {
        return view('auth.setup-signature');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'signature' => 'required|string',
        ]);

        Auth::user()->update(['signature' => $request->signature]);

        return redirect()->route('staff.dashboard')->with('success', 'Signature saved successfully.');
    }
}
