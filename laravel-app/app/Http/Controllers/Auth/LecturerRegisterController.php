<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LecturerRegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/LecturerRegister', [
            'schools' => School::query()->orderBy('name')->get(['id', 'name', 'code']),
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'school_id']),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $schoolId = (int) $request->input('school_id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'staff_id' => ['required', 'string', 'max:64', 'unique:users,staff_id'],
            'phone' => ['required', 'string', 'max:40'],
            'school_id' => ['required', 'integer', 'exists:schools,id'],
            'department_id' => [
                'required',
                'integer',
                Rule::exists('departments', 'id')->where(fn ($q) => $q->where('school_id', $schoolId)),
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $username = preg_replace('/\s+/', '', $validated['staff_id']);
        if ($username === '') {
            throw ValidationException::withMessages([
                'staff_id' => ['Staff ID is invalid.'],
            ]);
        }

        if (User::query()->where('username', $username)->exists()) {
            throw ValidationException::withMessages([
                'staff_id' => ['This staff ID is already in use as a username. Contact an administrator.'],
            ]);
        }

        $department = Department::query()->findOrFail((int) $validated['department_id']);
        if ((int) $department->school_id !== $schoolId) {
            throw ValidationException::withMessages([
                'department_id' => ['The selected department does not belong to the chosen school.'],
            ]);
        }

        $user = new User;
        $user->fill([
            'username' => $username,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'staff_id' => $validated['staff_id'],
            'phone' => $validated['phone'],
            'department_id' => $department->id,
            'school_id' => $schoolId,
        ]);
        $user->password = $validated['password'];
        $user->role = UserRole::Lecturer;
        $user->status = UserStatus::Pending;
        $user->is_active = true;
        $user->save();

        return redirect()->route('login')->with(
            'status',
            'Registration received. Your account is pending approval by an administrator. You will be able to sign in once it is approved.',
        );
    }
}
