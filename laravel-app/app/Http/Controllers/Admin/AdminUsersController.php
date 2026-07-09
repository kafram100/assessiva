<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AdminUsersController extends Controller
{
    public function index()
    {
        $admins = User::where('role', UserRole::Admin)->get();
        return Inertia::render('Admin/Admins/Index', [
            'admins' => $admins,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Admins/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'not_regex:/super\s*admin/i'],
            'username' => 'required|string|max:50|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::Admin,
            'status' => UserStatus::Approved,
            'permissions' => $validated['permissions'] ?? [],
            'is_active' => true,
        ]);

        return redirect()->route('admin.admins.index')->with('success', 'Admin created successfully.');
    }

    public function edit(User $admin)
    {
        if ($admin->id === 1) {
            return redirect()->route('admin.admins.index')->with('error', 'Cannot edit the Super Admin.');
        }

        return Inertia::render('Admin/Admins/Edit', [
            'adminUser' => $admin,
        ]);
    }

    public function update(Request $request, User $admin)
    {
        if ($admin->id === 1) {
            return redirect()->route('admin.admins.index')->with('error', 'Cannot edit the Super Admin.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'not_regex:/super\s*admin/i'],
            'username' => 'required|string|max:50|unique:users,username,' . $admin->id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $admin->id,
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $admin->name = $validated['name'];
        $admin->username = $validated['username'];
        $admin->email = $validated['email'];
        if (!empty($validated['password'])) {
            $admin->password = Hash::make($validated['password']);
        }
        $admin->permissions = $validated['permissions'] ?? [];
        $admin->save();

        return redirect()->route('admin.admins.index')->with('success', 'Admin updated successfully.');
    }

    public function destroy(User $admin)
    {
        if ($admin->id === 1) {
            return redirect()->route('admin.admins.index')->with('error', 'Cannot delete the Super Admin.');
        }

        $admin->delete();
        return redirect()->route('admin.admins.index')->with('success', 'Admin deleted successfully.');
    }
}
