<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Attempt;
use App\Models\CheatingIncident;
use App\Models\Department;
use App\Models\Exam;
use App\Models\ExamActivityLog;
use App\Models\ExamAllowedIndex;
use App\Models\ExamSecurityLog;
use App\Models\ExamSessionProctoring;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminController extends Controller
{
    public function dashboard(): InertiaResponse
    {
        $studentCount = User::query()->where('role', UserRole::Student)->count();
        $questionCount = \App\Models\QuestionBank::query()->count();
        $attemptCount = Attempt::query()->count();
        $totalTokens = User::query()->where('role', UserRole::Student)->sum('tokens');

        $now = Carbon::now();

        // 1. Daily History (Last 7 days)
        $dailyHistory = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $now->copy()->subDays($i);
            $sum = \App\Models\Transaction::where('status', 'success')
                ->whereDate('created_at', $date->toDateString())
                ->sum('amount');
            
            $label = $i === 0 ? 'Today' : ($i === 1 ? 'Yesterday' : $date->format('M j'));
            $dailyHistory[] = ['label' => $label, 'value' => $sum];
        }

        // 2. Weekly History (Last 4 weeks)
        $weeklyHistory = [];
        for ($i = 0; $i < 4; $i++) {
            $startOfWeek = $now->copy()->subWeeks($i)->startOfWeek();
            $endOfWeek = $now->copy()->subWeeks($i)->endOfWeek();
            
            $sum = \App\Models\Transaction::where('status', 'success')
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->sum('amount');

            $label = $i === 0 ? 'This Week' : ($i === 1 ? 'Last Week' : $startOfWeek->format('M j') . ' - ' . $endOfWeek->format('M j'));
            $weeklyHistory[] = ['label' => $label, 'value' => $sum];
        }

        // 3. Monthly History (Last 6 months)
        $monthlyHistory = [];
        for ($i = 0; $i < 6; $i++) {
            $month = $now->copy()->subMonths($i);
            
            $sum = \App\Models\Transaction::where('status', 'success')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->sum('amount');

            $label = $i === 0 ? 'This Month' : ($i === 1 ? 'Last Month' : $month->format('F Y'));
            $monthlyHistory[] = ['label' => $label, 'value' => $sum];
        }

        // 4. Yearly History (Last 3 years)
        $yearlyHistory = [];
        for ($i = 0; $i < 3; $i++) {
            $year = $now->copy()->subYears($i)->year;
            
            $sum = \App\Models\Transaction::where('status', 'success')
                ->whereYear('created_at', $year)
                ->sum('amount');

            $label = (string)$year;
            $yearlyHistory[] = ['label' => $label, 'value' => $sum];
        }

        return Inertia::render('Admin/Dashboard', [
            'studentCount' => $studentCount,
            'questionCount' => $questionCount,
            'attemptCount' => $attemptCount,
            'totalTokens' => $totalTokens,
            'revenue' => [
                'daily' => [
                    'current' => $dailyHistory[0]['value'],
                    'history' => $dailyHistory,
                ],
                'weekly' => [
                    'current' => $weeklyHistory[0]['value'],
                    'history' => $weeklyHistory,
                ],
                'monthly' => [
                    'current' => $monthlyHistory[0]['value'],
                    'history' => $monthlyHistory,
                ],
                'yearly' => [
                    'current' => $yearlyHistory[0]['value'],
                    'history' => $yearlyHistory,
                ],
            ],
        ]);
    }

    public function schools(): InertiaResponse
    {
        $schools = School::query()->withCount('departments')->orderBy('name')->get();

        return Inertia::render('Admin/Schools/Index', [
            'schools' => $schools,
        ]);
    }

    public function createSchool(): InertiaResponse
    {
        return Inertia::render('Admin/Schools/Create');
    }

    public function storeSchool(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:20|unique:schools,code',
            'description' => 'nullable|string',
        ]);
        $data = $request->only('name', 'description');
        $data['code'] = $request->filled('code') ? $request->string('code')->toString() : null;
        School::query()->create($data);

        return redirect()->route('admin.schools')->with('success', 'School created.');
    }

    public function editSchool(School $school): InertiaResponse
    {
        return Inertia::render('Admin/Schools/Edit', [
            'school' => $school,
        ]);
    }

    public function updateSchool(Request $request, School $school): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:20|unique:schools,code,'.$school->id,
            'description' => 'nullable|string',
        ]);
        $school->update($request->only('name', 'code', 'description'));

        return redirect()->route('admin.schools')->with('success', 'School updated.');
    }

    public function destroySchool(School $school): RedirectResponse
    {
        $school->delete();

        return redirect()->route('admin.schools')->with('success', 'School deleted.');
    }

    public function departments(): InertiaResponse
    {
        $departments = Department::query()
            ->with('school')
            ->withCount('lecturers')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Departments/Index', [
            'departments' => $departments,
        ]);
    }

    public function createDepartment(): InertiaResponse
    {
        $schools = School::query()->orderBy('name')->get();

        return Inertia::render('Admin/Departments/Create', [
            'schools' => $schools,
        ]);
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);
        Department::query()->create($request->only('school_id', 'name', 'code', 'description'));

        return redirect()->route('admin.departments')->with('success', 'Department created.');
    }

    public function editDepartment(Department $department): InertiaResponse
    {
        $schools = School::query()->orderBy('name')->get();

        return Inertia::render('Admin/Departments/Edit', [
            'department' => $department,
            'schools' => $schools,
        ]);
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $request->validate([
            'school_id' => 'required|exists:schools,id',
            'name' => 'required|string|max:150',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);
        $department->update($request->only('school_id', 'name', 'code', 'description'));

        return redirect()->route('admin.departments')->with('success', 'Department updated.');
    }

    public function destroyDepartment(Department $department): RedirectResponse
    {
        $department->delete();

        return redirect()->route('admin.departments')->with('success', 'Department deleted.');
    }

    public function lecturers(): InertiaResponse
    {
        $lecturers = User::query()
            ->academicStaff()
            ->with('department.school')
            ->orderByRaw('CASE status WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 ELSE 3 END', [
                UserStatus::Pending->value,
                UserStatus::Approved->value,
                UserStatus::Suspended->value,
            ])
            ->orderBy('username')
            ->get();

        $departments = Department::query()
            ->with('school')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Lecturers/Index', [
            'lecturers' => $lecturers,
            'departments' => $departments,
        ]);
    }

    public function pendingLecturers(): InertiaResponse
    {
        $pending = User::query()
            ->where('role', UserRole::Hod)
            ->where('status', UserStatus::Pending)
            ->with(['department.school'])
            ->orderBy('created_at')
            ->get();

        return Inertia::render('Admin/Lecturers/Pending', [
            'lecturers' => $pending,
        ]);
    }

    public function suspendLecturer(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== UserRole::Hod) {
            abort(403);
        }

        $user->status = UserStatus::Suspended;
        $user->approved_by = $request->user()->id;
        $user->save();

        return redirect()
            ->route('admin.lecturers')
            ->with('success', "Lecturer {$user->username} has been suspended.");
    }

    public function updateStaffDepartment(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== UserRole::Hod) {
            abort(403);
        }

        $rules = [
            'department_id' => ['nullable', 'exists:departments,id'],
        ];
        if ($user->role === UserRole::Hod) {
            $rules['department_id'] = ['required', 'exists:departments,id'];
        }

        $validated = $request->validate($rules);
        $departmentId = isset($validated['department_id'])
            ? (int) $validated['department_id']
            : null;

        if ($user->role === UserRole::Hod && $departmentId !== null) {
            $exists = User::query()
                ->where('role', UserRole::Hod)
                ->where('department_id', $departmentId)
                ->where('status', UserStatus::Approved)
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->exists();
            if ($exists) {
                return redirect()
                    ->route('admin.lecturers')
                    ->with('error', 'Another active HOD is already assigned to that department.');
            }
        }

        $schoolId = $departmentId
            ? Department::query()->whereKey($departmentId)->value('school_id')
            : null;

        $user->department_id = $departmentId;
        $user->school_id = $schoolId;
        $user->save();

        return redirect()
            ->route('admin.lecturers')
            ->with('success', "Department updated for {$user->username}.");
    }

    public function approveLecturer(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== UserRole::Hod) {
            abort(403);
        }

        if ($user->role === UserRole::Hod && ! $user->department_id) {
            return redirect()
                ->route('admin.lecturers')
                ->with(
                    'error',
                    "Cannot approve HOD «{$user->username}» until a department is assigned. Use “Set department” on the staff list or Add staff with a department.",
                );
        }

        $user->status = UserStatus::Approved;
        $user->approved_at = now();
        $user->approved_by = $request->user()->id;
        $user->is_active = true;
        $user->save();

        $label = $user->role === UserRole::Hod ? 'HOD' : 'Lecturer';

        return redirect()->route('admin.lecturers')->with('success', "{$label} {$user->username} has been approved.");
    }

    public function rejectLecturer(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== UserRole::Hod) {
            abort(403);
        }

        $user->status = UserStatus::Rejected;
        $user->approved_by = $request->user()->id;
        $user->save();

        $label = $user->role === UserRole::Hod ? 'HOD' : 'Lecturer';

        return redirect()->route('admin.lecturers')->with('success', "{$label} {$user->username} has been rejected.");
    }

    public function createLecturer(): InertiaResponse
    {
        $departments = Department::query()->with('school')->orderBy('name')->get();

        return Inertia::render('Admin/Lecturers/Create', [
            'departments' => $departments,
        ]);
    }

    public function storeLecturer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'role' => 'required|in:'.UserRole::Hod->value,
            'department_id' => 'nullable|exists:departments,id|required_if:role,'.UserRole::Hod->value,
        ]);

        $role = UserRole::from($request->string('role')->toString());

        $departmentId = isset($validated['department_id'])
            ? (int) $validated['department_id']
            : null;

        if ($role === UserRole::Hod && $departmentId !== null) {
            $exists = User::query()
                ->where('role', UserRole::Hod)
                ->where('department_id', $departmentId)
                ->where('status', UserStatus::Approved)
                ->where('is_active', true)
                ->exists();
            if ($exists) {
                return redirect()
                    ->route('admin.lecturers.create')
                    ->with('error', 'That department already has an active Head of Department.');
            }
        }

        $schoolId = $departmentId
            ? Department::query()->whereKey($departmentId)->value('school_id')
            : null;

        $user = new User;
        $user->fill([
            'name' => $validated['username'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'department_id' => $departmentId,
            'school_id' => $schoolId,
        ]);
        $user->password = $validated['password'];
        $user->role = $role;
        $user->status = UserStatus::Approved;
        $user->is_active = true;
        $user->approved_at = now();
        $user->approved_by = $request->user()->id;
        $user->save();

        $label = $role === UserRole::Hod ? 'HOD' : 'Lecturer';

        return redirect()->route('admin.lecturers')->with('success', "{$label} {$validated['username']} has been added.");
    }

    public function allExams(Request $request): InertiaResponse
    {
        $now = Carbon::now();

        $query = Exam::query()
            ->with(['user.department.school'])
            ->withCount([
                'attempts as attempts_total_count',
                'attempts as attempts_completed_count' => function ($q): void {
                    $q->where('status', 'completed');
                },
                'attempts as attempts_ongoing_count' => function ($q): void {
                    $q->where('status', 'ongoing');
                },
            ]);

        $schedule = $request->input('schedule', 'all');
        if (! in_array($schedule, ['all', 'active', 'upcoming', 'ended', 'unpublished'], true)) {
            $schedule = 'all';
        }

        match ($schedule) {
            'unpublished' => $query->where('is_published', false),
            'upcoming' => $query->where('is_published', true)
                ->whereNotNull('start_datetime')
                ->where('start_datetime', '>', $now),
            'ended' => $query->where('is_published', true)
                ->whereNotNull('end_datetime')
                ->where('end_datetime', '<', $now),
            'active' => $query->where('is_published', true)
                ->where(function ($q) use ($now): void {
                    $q->whereNull('start_datetime')->orWhere('start_datetime', '<=', $now);
                })
                ->where(function ($q) use ($now): void {
                    $q->whereNull('end_datetime')->orWhere('end_datetime', '>=', $now);
                }),
            default => null,
        };

        if ($request->filled('school_id')) {
            $schoolId = (int) $request->input('school_id');
            $query->whereHas('user.department', fn ($q) => $q->where('school_id', $schoolId));
        }

        if ($request->filled('department_id')) {
            $deptId = (int) $request->input('department_id');
            $query->whereHas('user', fn ($q) => $q->where('department_id', $deptId));
        }

        if ($request->filled('lecturer_id')) {
            $query->where('user_id', (int) $request->input('lecturer_id'));
        }

        if ($request->filled('q')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $request->input('q'))).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('title', 'like', $term)
                    ->orWhere('course_name', 'like', $term)
                    ->orWhere('course_code', 'like', $term)
                    ->orWhere('exam_code', 'like', $term);
            });
        }

        $exams = $query
            ->orderByDesc('start_datetime')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $schools = School::query()->orderBy('name')->get();
        $departmentsQuery = Department::query()->with('school')->orderBy('name');
        if ($request->filled('school_id')) {
            $departmentsQuery->where('school_id', (int) $request->input('school_id'));
        }
        $departments = $departmentsQuery->get();

        $lecturers = User::query()
            ->lecturers()
            ->where('status', UserStatus::Approved)
            ->with('department.school')
            ->orderBy('username')
            ->get();

        return Inertia::render('Admin/Exams/Index', [
            'exams' => $exams,
            'schools' => $schools,
            'departments' => $departments,
            'lecturers' => $lecturers,
            'schedule' => $schedule,
            'filters' => $request->only(['school_id', 'department_id', 'lecturer_id', 'q']),
            'now' => $now->toIso8601String(),
        ]);
    }

    public function showExamOverview(Exam $exam): InertiaResponse
    {
        $exam->load(['user.department.school']);
        $attempts = Attempt::query()
            ->where('exam_id', $exam->id)
            ->orderByRaw("CASE status WHEN 'ongoing' THEN 0 WHEN 'completed' THEN 1 ELSE 2 END")
            ->orderByDesc('start_time')
            ->paginate(40)
            ->withQueryString();

        return Inertia::render('Admin/Exams/Show', [
            'exam' => $exam,
            'attempts' => $attempts,
            'now' => Carbon::now()->toIso8601String(),
        ]);
    }

    public function resetStudentExamAttempts(Request $request, Exam $exam): RedirectResponse
    {
        $data = $request->validate([
            'student_index' => 'required|string|max:50',
        ]);

        $normalized = ExamAllowedIndex::normalize($data['student_index']);
        if ($normalized === '') {
            return back()->withErrors(['student_index' => 'Invalid index number.']);
        }

        $attemptIds = Attempt::query()
            ->where('exam_id', $exam->id)
            ->whereRaw('UPPER(TRIM(student_index)) = ?', [$normalized])
            ->pluck('id');

        if ($attemptIds->isEmpty()) {
            return back()->with('error', 'No attempts found for that index on this exam.');
        }

        DB::transaction(function () use ($attemptIds): void {
            ExamSessionProctoring::query()->whereIn('exam_attempt_id', $attemptIds)->delete();
            ExamSecurityLog::query()->whereIn('exam_attempt_id', $attemptIds)->delete();
            ExamActivityLog::query()->whereIn('exam_attempt_id', $attemptIds)->delete();
            CheatingIncident::query()->whereIn('exam_attempt_id', $attemptIds)->delete();
            Attempt::query()->whereIn('id', $attemptIds)->delete();
        });

        return back()->with('success', "All attempts for index {$normalized} on this exam have been cleared. The student may start again.");
    }

    public function allQuizzes(Request $request): InertiaResponse
    {
        $search = trim((string) $request->input('q', ''));

        $attempts = Attempt::query()
            ->with(['exam.user.department.school'])
            ->where('status', 'completed')
            ->when($search !== '', function ($q) use ($search): void {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
                $q->where(function ($qq) use ($term): void {
                    $qq->where('student_index', 'like', $term)
                        ->orWhere('student_fullname', 'like', $term)
                        ->orWhere('student_email', 'like', $term)
                        ->orWhereHas('exam', function ($ex) use ($term): void {
                            $ex->where('title', 'like', $term)
                                ->orWhere('course_name', 'like', $term)
                                ->orWhere('course_code', 'like', $term);
                        });
                });
            })
            ->orderByDesc('submit_time')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Quizzes', [
            'attempts' => $attempts,
            'filters' => [
                'q' => $search,
            ],
        ]);
    }

    public function allStudents(Request $request): InertiaResponse
    {
        $search = trim((string) $request->input('q', ''));

        $students = User::query()
            ->students()
            ->when($search !== '', function ($q) use ($search): void {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
                $q->where(function ($qq) use ($term): void {
                    $qq->where('username', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->withCount('practiceSessions')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Students/Index', [
            'students' => $students,
            'search' => $search,
        ]);
    }

    public function exportEmails(Request $request)
    {
        $query = User::students()->select('email', 'name');
        
        if ($request->has('ids') && !empty($request->query('ids'))) {
            $ids = explode(',', $request->query('ids'));
            $query->whereIn('id', $ids);
        }
        
        $users = $query->get();
        
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=user_emails.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];
        
        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Email']);
            foreach ($users as $user) {
                fputcsv($file, [$user->name, $user->email]);
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    public function exportPhones(Request $request)
    {
        $query = User::students()->select('phone')->whereNotNull('phone');
        
        if ($request->has('ids') && !empty($request->query('ids'))) {
            $ids = explode(',', $request->query('ids'));
            $query->whereIn('id', $ids);
        }
        
        $users = $query->get();
        
        $headers = [
            'Content-type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename=user_phones.txt',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];
        
        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');
            foreach ($users as $user) {
                if (trim($user->phone) !== '') {
                    fwrite($file, $user->phone . PHP_EOL);
                }
            }
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    public function deleteStudents(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:users,id',
        ]);

        User::students()->whereIn('id', $data['ids'])->delete();

        return redirect()->back()->with('success', 'Selected users deleted successfully.');
    }

    public function frequentCheaters(Request $request): InertiaResponse
    {
        $preset = $request->input('preset', '20');
        $limit = in_array($preset, ['10', '20', '50'], true)
            ? (int) $preset
            : min(100, max(1, (int) $request->input('limit', 20)));
        $studentIndex = trim((string) $request->input('student_index', ''));
        $studentName = trim((string) $request->input('student_name', ''));

        $coursesSql = $this->coursesAggregateSql();

        $cheatersQuery = DB::table('cheat_events')
            ->join('attempts', 'cheat_events.attempt_id', '=', 'attempts.id')
            ->join('exams', 'attempts.exam_id', '=', 'exams.id')
            ->leftJoin('users', 'exams.user_id', '=', 'users.id')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->leftJoin('schools', 'departments.school_id', '=', 'schools.id')
            ->selectRaw("
                attempts.student_index,
                MAX(attempts.student_fullname) as student_fullname,
                MAX(attempts.student_level) as student_level,
                {$coursesSql} as courses,
                MAX(departments.name) as department_name,
                MAX(schools.name) as school_name,
                COUNT(cheat_events.id) as cheat_count,
                COUNT(DISTINCT attempts.id) as quiz_count
            ");

        if ($studentIndex !== '') {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $studentIndex).'%';
            $cheatersQuery->where('attempts.student_index', 'like', $term);
        }
        if ($studentName !== '') {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $studentName).'%';
            $cheatersQuery->where('attempts.student_fullname', 'like', $term);
        }

        $cheaters = $cheatersQuery
            ->groupBy('attempts.student_index')
            ->orderByDesc('cheat_count')
            ->limit($limit)
            ->get();

        return Inertia::render('Admin/FrequentCheaters', [
            'cheaters' => $cheaters,
            'limit' => $limit,
            'preset' => $preset,
            'studentIndex' => $studentIndex,
            'studentName' => $studentName,
        ]);
    }

    public function emailSettings(): InertiaResponse
    {
        $settings = [
            'mail_mailer' => AppSetting::getValue('mail_mailer', config('mail.default')),
            'mail_host' => AppSetting::getValue('mail_host', config('mail.mailers.smtp.host')),
            'mail_port' => AppSetting::getValue('mail_port', (string) config('mail.mailers.smtp.port')),
            'mail_encryption' => AppSetting::getValue('mail_encryption', config('mail.mailers.smtp.encryption')),
            'mail_username' => AppSetting::getValue('mail_username', config('mail.mailers.smtp.username')),
            'mail_password' => AppSetting::getValue('mail_password', ''),
            'mail_from_address' => AppSetting::getValue('mail_from_address', config('mail.from.address')),
            'mail_from_name' => AppSetting::getValue('mail_from_name', config('mail.from.name')),
            'mail_reply_to' => AppSetting::getValue('mail_reply_to', ''),
        ];

        return Inertia::render('Admin/EmailSettings', [
            'settings' => $settings,
        ]);
    }

    public function updateEmailSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mail_mailer' => 'required|string|max:20',
            'mail_host' => 'required|string|max:191',
            'mail_port' => 'required|integer|min:1|max:65535',
            'mail_encryption' => 'nullable|string|max:10',
            'mail_username' => 'required|string|max:191',
            'mail_password' => 'nullable|string|max:191',
            'mail_from_address' => 'required|email|max:191',
            'mail_from_name' => 'required|string|max:100',
            'mail_reply_to' => 'nullable|email|max:191',
        ]);

        foreach ($data as $key => $value) {
            AppSetting::setValue($key, $value === '' ? null : (string) $value);
        }

        return redirect()->route('admin.email-settings')->with('success', 'Email settings saved. Lecturers now use this shared sender account.');
    }

    public function billingSettings(): InertiaResponse
    {
        $settings = [
            'active_payment_gateway' => AppSetting::getValue('active_payment_gateway', 'paystack'),
            'token_price_per_question' => AppSetting::getValue('token_price_per_question', '1'),
            'paystack_public_key' => AppSetting::getValue('paystack_public_key', ''),
            'paystack_secret_key' => AppSetting::getValue('paystack_secret_key', ''),
            'moolre_public_key' => AppSetting::getValue('moolre_public_key', ''),
            'moolre_secret_key' => AppSetting::getValue('moolre_secret_key', ''),
        ];

        return Inertia::render('Admin/BillingSettings', [
            'settings' => $settings,
        ]);
    }

    public function updateBillingSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'active_payment_gateway' => 'required|in:paystack,moolre',
            'token_price_per_question' => 'required|numeric|min:0',
            'paystack_public_key' => 'nullable|string',
            'paystack_secret_key' => 'nullable|string',
            'moolre_public_key' => 'nullable|string',
            'moolre_secret_key' => 'nullable|string',
        ]);

        foreach ($data as $key => $value) {
            AppSetting::setValue($key, $value === '' ? null : (string) $value);
        }

        return redirect()->route('admin.billing-settings')->with('success', 'Billing settings updated.');
    }

    private function coursesAggregateSql(): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "string_agg(distinct exams.course_name::text, ', ' order by exams.course_name::text)",
            'sqlite' => 'group_concat(distinct exams.course_name)',
            default => "GROUP_CONCAT(DISTINCT exams.course_name ORDER BY exams.course_name SEPARATOR ', ')",
        };
    }
}
