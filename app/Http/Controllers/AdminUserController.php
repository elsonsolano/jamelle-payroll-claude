<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $users = User::where('role', 'admin')->orderBy('name')->get();
        return view('admin-users.index', compact('users'));
    }

    public function create(): View
    {
        return view('admin-users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|max:255|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => 'admin',
        ]);

        return redirect()->route('admin-users.index')->with('success', 'Admin user created successfully.');
    }

    public function edit(User $adminUser): View
    {
        abort_if($adminUser->role !== 'admin', 404);
        return view('admin-users.edit', ['user' => $adminUser]);
    }

    public function update(Request $request, User $adminUser): RedirectResponse
    {
        abort_if($adminUser->role !== 'admin', 404);
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email,' . $adminUser->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $adminUser->name  = $validated['name'];
        $adminUser->email = $validated['email'];

        if (!empty($validated['password'])) {
            $adminUser->password = Hash::make($validated['password']);
        }

        $adminUser->save();

        return redirect()->route('admin-users.index')->with('success', 'Admin user updated successfully.');
    }

    public function destroy(User $adminUser): RedirectResponse
    {
        abort_if($adminUser->role !== 'admin', 404);

        if ($adminUser->id === Auth::id()) {
            return redirect()->route('admin-users.index')->with('error', 'You cannot delete your own account.');
        }

        $adminUser->delete();

        return redirect()->route('admin-users.index')->with('success', 'Admin user deleted.');
    }
}
