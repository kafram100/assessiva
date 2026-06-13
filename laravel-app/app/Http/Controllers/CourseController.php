<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ParsesStudentIndexUpload;
use App\Models\Course;
use App\Models\CourseAllowedIndex;
use App\Models\Department;
use App\Support\LinkableCourses;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CourseController extends Controller
{
    use ParsesStudentIndexUpload;

    public function index(): Response
    {
        $this->authorize('viewAny', Course::class);

        $user = Auth::user();
        $courses = LinkableCourses::courseQueryForUser($user)
            ->withCount(['allowedIndexes', 'exams'])
            ->get();

        return Inertia::render('Lecturer/Courses/Index', [
            'courses' => $courses,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Course::class);

        $user = Auth::user();
        $departments = Department::query()
            ->with('school')
            ->orderBy('name')
            ->get();

        return Inertia::render('Lecturer/Courses/Create', [
            'departments' => $departments,
            'defaultDepartmentId' => $user->department_id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Course::class);

        $user = Auth::user();
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('courses', 'code')->where(fn ($q) => $q->where('user_id', $user->id)),
            ],
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:5000',
            'department_id' => 'nullable|exists:departments,id',
        ]);
        $data['user_id'] = $user->id;
        $data['code'] = strtoupper(trim($data['code']));
        if (empty($data['department_id']) && $user->department_id) {
            $data['department_id'] = $user->department_id;
        }

        $course = Course::create($data);

        return redirect()->route('lecturer.courses.show', $course)
            ->with('success', 'Course created.');
    }

    public function show(Course $course): Response
    {
        $this->authorize('view', $course);

        $course->loadCount(['allowedIndexes', 'exams']);
        $preview = $course->allowedIndexes()
            ->orderBy('student_index')
            ->limit(100)
            ->pluck('student_index');

        return Inertia::render('Lecturer/Courses/Show', [
            'course' => $course,
            'rosterPreview' => $preview,
        ]);
    }

    public function edit(Course $course): Response
    {
        $this->authorize('update', $course);

        $departments = Department::query()
            ->with('school')
            ->orderBy('name')
            ->get();

        return Inertia::render('Lecturer/Courses/Edit', [
            'course' => $course,
            'departments' => $departments,
        ]);
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('courses', 'code')
                    ->where(fn ($q) => $q->where('user_id', $course->user_id))
                    ->ignore($course->id),
            ],
            'name' => 'required|string|max:150',
            'description' => 'nullable|string|max:5000',
            'department_id' => 'nullable|exists:departments,id',
        ]);
        $data['code'] = strtoupper(trim($data['code']));
        $course->update($data);

        return redirect()->route('lecturer.courses.show', $course)
            ->with('success', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);

        if ($course->exams()->exists()) {
            return back()->withErrors([
                'course' => 'Unlink or delete exams that use this course before deleting it.',
            ]);
        }

        $course->delete();

        return redirect()->route('lecturer.courses.index')
            ->with('success', 'Course deleted.');
    }

    public function rosterStore(Request $request, Course $course): RedirectResponse
    {
        $this->authorize('update', $course);

        $request->validate([
            'indexes_text' => 'nullable|string|max:500000',
            'indexes_file' => 'nullable|file|mimes:csv,txt|max:10240',
            'replace' => 'nullable|boolean',
            'clear_all' => 'nullable|boolean',
        ]);

        $clearAll = $request->boolean('clear_all');
        $replace = $request->boolean('replace');
        $indexes = $this->normalizedIndexesFromRequest($request);

        DB::transaction(function () use ($course, $clearAll, $replace, $indexes): void {
            if ($clearAll) {
                $course->allowedIndexes()->delete();

                return;
            }

            if ($indexes === []) {
                return;
            }

            if ($replace) {
                $course->allowedIndexes()->delete();
            }

            foreach ($indexes as $index) {
                CourseAllowedIndex::query()->firstOrCreate([
                    'course_id' => $course->id,
                    'student_index' => $index,
                ]);
            }
        });

        $msg = $clearAll ? 'Roster cleared.' : 'Roster updated.';

        return back()->with('success', $msg);
    }

    public function rosterTemplate(): StreamedResponse
    {
        $this->authorize('create', Course::class);

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, ['student_index']);
            fputcsv($out, ['ENG123456']);
            fputcsv($out, ['ENG123457']);
            fclose($out);
        }, 'course-index-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function rosterExport(Course $course): StreamedResponse
    {
        $this->authorize('view', $course);

        $filename = 'course-'.$course->id.'-roster.csv';

        return response()->streamDownload(function () use ($course): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, ['student_index']);
            $course->allowedIndexes()
                ->orderBy('student_index')
                ->cursor()
                ->each(function (CourseAllowedIndex $row) use ($out): void {
                    fputcsv($out, [$row->student_index]);
                });
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
