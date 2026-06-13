<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class HodManagementController extends Controller
{
    public function index(): InertiaResponse
    {
        $hods = User::query()
            ->where('role', UserRole::Hod)
            ->with(['department.school', 'approvedByUser:id,username,name'])
            ->orderBy('username')
            ->get();

        $departments = Department::query()->with('school')->orderBy('name')->get();

        return Inertia::render('Admin/Hods/Index', [
            'hods' => $hods,
            'departments' => $departments,
        ]);
    }

    public function create(): InertiaResponse
    {
        $departments = Department::query()->with('school')->orderBy('name')->get();

        return Inertia::render('Admin/Hods/Create', [
            'departments' => $departments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'staff_id' => ['nullable', 'string', 'max:64', 'unique:users,staff_id'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        $department = Department::query()->findOrFail((int) $validated['department_id']);

        if ($this->activeHodExistsForDepartment($department->id, excludeUserId: null)) {
            return redirect()
                ->route('admin.hods.create')
                ->with('error', 'This department already has an active Head of Department. Deactivate the current HOD or assign a different department.');
        }

        $user = new User;
        $user->fill([
            'username' => $validated['username'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'staff_id' => $validated['staff_id'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'department_id' => $department->id,
            'school_id' => $department->school_id,
        ]);
        $user->password = $validated['password'];
        $user->role = UserRole::Hod;
        $user->status = UserStatus::Approved;
        $user->is_active = true;
        $user->approved_at = now();
        $user->approved_by = $request->user()->id;
        $user->save();

        return redirect()->route('admin.hods.index')->with('success', 'Head of Department account created.');
    }

    public function promoteFromLecturer(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== UserRole::Lecturer) {
            abort(403);
        }

        if ($user->status !== UserStatus::Approved || ! $user->is_active) {
            return redirect()
                ->back()
                ->with('error', 'Only approved, active lecturers can be promoted to HOD.');
        }

        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        $departmentId = (int) $validated['department_id'];

        if ($this->activeHodExistsForDepartment($departmentId, excludeUserId: null)) {
            return redirect()
                ->back()
                ->with('error', 'That department already has an active Head of Department.');
        }

        $department = Department::query()->findOrFail($departmentId);

        DB::transaction(function () use ($user, $department, $request): void {
            $user->role = UserRole::Hod;
            $user->department_id = $department->id;
            $user->school_id = $department->school_id;
            $user->status = UserStatus::Approved;
            $user->is_active = true;
            $user->approved_at = now();
            $user->approved_by = $request->user()->id;
            $user->save();
        });

        return redirect()
            ->route('admin.hods.index')
            ->with('success', "{$user->username} has been promoted to Head of Department.");
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== UserRole::Hod) {
            abort(403);
        }

        $user->is_active = false;
        $user->approved_by = $request->user()->id;
        $user->save();

        return redirect()->route('admin.hods.index')->with('success', 'HOD account deactivated.');
    }

    public function updateDepartment(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== UserRole::Hod) {
            abort(403);
        }

        $validated = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        $departmentId = (int) $validated['department_id'];

        if ($this->activeHodExistsForDepartment($departmentId, excludeUserId: $user->id)) {
            return redirect()
                ->back()
                ->with('error', 'Another active HOD is already assigned to that department.');
        }

        $department = Department::query()->findOrFail($departmentId);

        $user->department_id = $department->id;
        $user->school_id = $department->school_id;
        $user->approved_by = $request->user()->id;
        $user->save();

        return redirect()->route('admin.hods.index')->with('success', 'HOD department updated.');
    }

    private function activeHodExistsForDepartment(int $departmentId, ?int $excludeUserId): bool
    {
        $q = User::query()
            ->where('role', UserRole::Hod)
            ->where('department_id', $departmentId)
            ->where('status', UserStatus::Approved)
            ->where('is_active', true);

        if ($excludeUserId !== null) {
            $q->where('id', '!=', $excludeUserId);
        }

        return $q->exists();
    }
}
