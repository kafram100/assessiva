<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Concerns\ParsesStudentIndexUpload;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamAllowedIndex;
use App\Services\AiRevisionPlanService;
use App\Support\LinkableCourses;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamController extends Controller
{
    use ParsesStudentIndexUpload;

    public function index(): Response
    {
        $this->authorize('viewAny', Exam::class);

        $user = Auth::user();
        $exams = Exam::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->latest()
            ->get();

        return Inertia::render('Lecturer/Exams/Index', [
            'exams' => $exams,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Exam::class);

        return Inertia::render('Lecturer/Exams/Create', [
            'linkableCourses' => LinkableCourses::forUser(Auth::user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Exam::class);

        $rawExamCode = $request->input('exam_code');
        if ($rawExamCode === null || trim((string) $rawExamCode) === '') {
            $request->merge(['exam_code' => null]);
        }
        if (trim((string) $request->input('course_code', '')) === '') {
            $request->merge(['course_code' => null]);
        }
        if ($request->input('course_id') === '' || $request->input('course_id') === null) {
            $request->merge(['course_id' => null]);
        }

        $data = $request->validate([
            'title' => 'required|string|max:100',
            'course_name' => 'required|string|max:100',
            'course_code' => 'nullable|string|max:50',
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')],
            'exam_type' => 'required|string|max:50',
            'proctoring_mode' => ['required', 'string', Rule::in(['auto', 'enabled', 'disabled'])],
            'shuffle_questions' => 'nullable|boolean',
            'duration' => 'required|integer|min:0',
            'attempts_allowed' => 'required|integer|min:1',
            'instructions' => 'nullable|string',
            'start_datetime' => 'nullable|date',
            'end_datetime' => 'nullable|date',
            'exam_code' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('exams', 'exam_code'),
            ],
            'assessment_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'submission_password_enabled' => 'nullable|boolean',
            'submission_password' => 'nullable|string|min:4|max:100',
            'show_student_ai_feedback' => 'nullable|boolean',
            'show_student_expected_answers' => 'nullable|boolean',
            'show_student_revision_suggestions' => 'nullable|boolean',
        ]);

        if (! empty($data['course_id'])) {
            $this->authorize('view', Course::findOrFail((int) $data['course_id']));
        } else {
            $data['course_id'] = null;
        }

        $data['user_id'] = Auth::id();
        $customCode = isset($data['exam_code']) ? strtoupper(trim((string) $data['exam_code'])) : '';
        if ($customCode !== '') {
            $data['exam_code'] = $customCode;
        } else {
            $data['exam_code'] = $this->generateUniqueCode();
        }
        $data['course_code'] = isset($data['course_code']) && $data['course_code'] !== null
            ? trim((string) $data['course_code'])
            : null;
        $data['shuffle_questions'] = $request->boolean('shuffle_questions');
        $data['submission_password_enabled'] = $request->boolean('submission_password_enabled');
        $data['show_student_ai_feedback'] = $request->boolean('show_student_ai_feedback', true);
        $data['show_student_expected_answers'] = $request->boolean('show_student_expected_answers', true);
        $data['show_student_revision_suggestions'] = $request->boolean('show_student_revision_suggestions', true);

        $plainSubmissionPassword = trim((string) $request->input('submission_password', ''));
        if ($data['submission_password_enabled']) {
            if ($plainSubmissionPassword === '') {
                return back()
                    ->withErrors(['submission_password' => 'Set a submission password when this feature is enabled.'])
                    ->withInput();
            }
            $data['submission_password'] = Hash::make($plainSubmissionPassword);
        } else {
            $data['submission_password'] = null;
        }

        if ($request->hasFile('assessment_file')) {
            $data['assessment_file'] = $request->file('assessment_file')
                ->store('assessments', 'public');
        }

        $exam = Exam::create($data);

        return redirect()->route('lecturer.exams.show', $exam)
            ->with('success', 'Exam created successfully.');
    }

    public function show(Exam $exam): Response
    {
        $this->authorize('view', $exam);

        $exam->load(['questions' => fn ($q) => $q->orderBy('id'), 'course', 'fairnessReport']);
        $allowedIndexesCount = $exam->allowedIndexes()->count();
        $rosterPreview = $exam->allowedIndexes()
            ->orderBy('student_index')
            ->limit(50)
            ->pluck('student_index');

        return Inertia::render('Lecturer/Exams/Show', [
            'exam' => $exam,
            'fairnessReport' => $exam->fairnessReport,
            'studentEntryUrl' => route('student.entry'),
            'allowedIndexesCount' => $allowedIndexesCount,
            'rosterPreview' => $rosterPreview,
            'linkableCourses' => LinkableCourses::forUser(Auth::user()),
        ]);
    }

    public function edit(Exam $exam): Response
    {
        $this->authorize('update', $exam);

        $exam->load(['fairnessReport']);
        $exam->loadCount('questions');

        return Inertia::render('Lecturer/Exams/Edit', [
            'exam' => $exam,
            'studentEntryUrl' => route('student.entry'),
            'linkableCourses' => LinkableCourses::forUser(Auth::user()),
        ]);
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        if (trim((string) $request->input('course_code', '')) === '') {
            $request->merge(['course_code' => null]);
        }
        if ($request->input('course_id') === '' || $request->input('course_id') === null) {
            $request->merge(['course_id' => null]);
        }

        $data = $request->validate([
            'title' => 'required|string|max:100',
            'course_name' => 'required|string|max:100',
            'course_code' => 'nullable|string|max:50',
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')],
            'exam_type' => 'required|string|max:50',
            'proctoring_mode' => ['required', 'string', Rule::in(['auto', 'enabled', 'disabled'])],
            'shuffle_questions' => 'nullable|boolean',
            'duration' => 'required|integer|min:0',
            'attempts_allowed' => 'required|integer|min:1',
            'instructions' => 'nullable|string',
            'start_datetime' => 'nullable|date',
            'end_datetime' => 'nullable|date',
            'exam_code' => 'required|string|max:20|regex:/^[A-Za-z0-9_-]+$/|unique:exams,exam_code,'.$exam->id,
            'submission_password_enabled' => 'nullable|boolean',
            'submission_password' => 'nullable|string|min:4|max:100',
            'show_student_ai_feedback' => 'nullable|boolean',
            'show_student_expected_answers' => 'nullable|boolean',
            'show_student_revision_suggestions' => 'nullable|boolean',
        ]);

        if (! empty($data['course_id'])) {
            $this->authorize('view', Course::findOrFail((int) $data['course_id']));
        } else {
            $data['course_id'] = null;
        }

        $data['exam_code'] = strtoupper(trim($data['exam_code']));
        $data['course_code'] = isset($data['course_code']) && $data['course_code'] !== null
            ? trim((string) $data['course_code'])
            : null;
        $data['shuffle_questions'] = $request->boolean('shuffle_questions');
        $data['submission_password_enabled'] = $request->boolean('submission_password_enabled');
        $data['show_student_ai_feedback'] = $request->boolean('show_student_ai_feedback');
        $data['show_student_expected_answers'] = $request->boolean('show_student_expected_answers');
        $data['show_student_revision_suggestions'] = $request->boolean('show_student_revision_suggestions');

        $plainSubmissionPassword = trim((string) $request->input('submission_password', ''));
        if ($data['submission_password_enabled']) {
            if ($plainSubmissionPassword !== '') {
                $data['submission_password'] = Hash::make($plainSubmissionPassword);
            } elseif (empty($exam->submission_password)) {
                return back()
                    ->withErrors(['submission_password' => 'Set a submission password when this feature is enabled.'])
                    ->withInput();
            } else {
                $data['submission_password'] = $exam->submission_password;
            }
        } else {
            $data['submission_password'] = null;
        }

        $exam->update($data);

        return redirect()->route('lecturer.exams.show', $exam)
            ->with('success', 'Exam updated.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorize('delete', $exam);
        $exam->delete();

        return redirect()->route('lecturer.exams.index')
            ->with('success', 'Exam deleted.');
    }

    public function publish(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('publish', $exam);

        $action = $request->input('action', 'publish');
        $msg = 'Done.';

        if ($action === 'publish') {
            $exam->update(['is_published' => true]);
            $msg = 'Exam published.';
        } elseif ($action === 'unpublish') {
            $exam->update(['is_published' => false]);
            $msg = 'Exam unpublished.';
        } elseif ($action === 'release_results') {
            $exam->update(['results_released' => true]);
            try {
                app(AiRevisionPlanService::class)->generateAllForReleasedExam($exam->fresh());
            } catch (\Throwable) {
                // Best-effort; lecturers can bulk-generate from the results UI.
            }
            $msg = 'Results released.';
        } elseif ($action === 'hide_results') {
            $exam->update(['results_released' => false]);
            $msg = 'Results hidden.';
        }

        return redirect()->route('lecturer.exams.show', $exam)->with('success', $msg);
    }

    public function rosterStore(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $request->validate([
            'indexes_text' => 'nullable|string|max:500000',
            'indexes_file' => 'nullable|file|mimes:csv,txt|max:10240',
            'replace' => 'nullable|boolean',
            'clear_all' => 'nullable|boolean',
        ]);

        $clearAll = $request->boolean('clear_all');
        $replace = $request->boolean('replace');
        $indexes = $this->normalizedIndexesFromRequest($request);

        DB::transaction(function () use ($exam, $clearAll, $replace, $indexes): void {
            if ($clearAll) {
                $exam->allowedIndexes()->delete();

                return;
            }

            if ($indexes === []) {
                return;
            }

            if ($replace) {
                $exam->allowedIndexes()->delete();
            }

            foreach ($indexes as $index) {
                ExamAllowedIndex::query()->firstOrCreate([
                    'exam_id' => $exam->id,
                    'student_index' => $index,
                ]);
            }
        });

        $msg = $clearAll ? 'Exam roster cleared.' : 'Exam roster updated.';

        return back()->with('success', $msg);
    }

    public function downloadAllowedIndexTemplate(): StreamedResponse
    {
        $this->authorize('create', Exam::class);

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, ['student_index']);
            fputcsv($out, ['ENG123456']);
            fputcsv($out, ['ENG123457']);
            fclose($out);
        }, 'exam-index-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function rosterExport(Exam $exam): StreamedResponse
    {
        $this->authorize('view', $exam);

        $filename = 'exam-'.$exam->id.'-roster.csv';

        return response()->streamDownload(function () use ($exam): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, ['student_index']);
            $exam->allowedIndexes()
                ->orderBy('student_index')
                ->cursor()
                ->each(function (ExamAllowedIndex $row) use ($out): void {
                    fputcsv($out, [$row->student_index]);
                });
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function syncRosterFromCourse(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $courseId = $exam->course_id;
        if ($courseId === null) {
            $validated = $request->validate([
                'course_id' => ['required', 'integer', Rule::exists('courses', 'id')],
            ]);
            $courseId = (int) $validated['course_id'];
        }

        $course = Course::findOrFail($courseId);
        $this->authorize('view', $course);

        $indexes = $course->allowedIndexes()->orderBy('student_index')->pluck('student_index')->all();

        DB::transaction(function () use ($exam, $indexes): void {
            $exam->allowedIndexes()->delete();
            foreach ($indexes as $index) {
                ExamAllowedIndex::query()->create([
                    'exam_id' => $exam->id,
                    'student_index' => $index,
                ]);
            }
        });

        return back()->with('success', 'Exam roster replaced from the selected course ('.count($indexes).' index numbers).');
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Exam::where('exam_code', $code)->exists());

        return $code;
    }
}
