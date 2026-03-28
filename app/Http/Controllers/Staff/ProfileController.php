<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function index(): View
    {
        $employee = Auth::user()->employee->load('branch');
        return view('staff.profile', compact('employee'));
    }
}
