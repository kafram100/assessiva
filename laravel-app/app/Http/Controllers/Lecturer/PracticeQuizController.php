<?php

namespace App\Http\Controllers\Lecturer;

use App\Enums\PracticeVisibility;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Models\PracticeQuizAttempt;
use App\Models\QuestionBank;
use App\Models\School;
use App\Services\QuestionBankService;
use App\Support\LinkableCourses;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PracticeQuizController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Exam::class);

        $user = Auth::user();
        $quizzes = Exam::query()
            ->where('practice_mode', true)
            ->when(! $user->isAdmin(), fn ($q) => $q->where('user_id', $user->id))
            ->withCount([
                'practiceQuizAttempts as attempts_count' => fn ($q) => $q->whereNotNull('submitted_at'),
            ])
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('Lecturer/PracticeQuizzes/Index', [
            'quizzes' => $quizzes,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Exam::class);

        return Inertia::render('Lecturer/PracticeQuizzes/Create', [
            'linkableCourses' => LinkableCourses::forUser(Auth::user()),
            'schools' => School::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name', 'school_id']),
            'visibilityOptions' => collect(PracticeVisibility::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => str_replace('_', ' ', ucfirst($c->value)),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Exam::class);

        $data = $this->validatedExam($request, null);
        $data['user_id'] = Auth::id();
        $data['practice_mode'] = true;
        $data['exam_type'] = 'practice';
        $data['proctoring_mode'] = 'disabled';
        $data['is_published'] = false;
        $data['is_public_practice'] = false;
        $data['exam_code'] = $this->generateUniqueCode();
        $data['attempts_allowed'] = 999;
        $data['shuffle_questions'] = false;
        $data['submission_password_enabled'] = false;
        $data['submission_password'] = null;
        $data['results_released'] = true;

        $exam = Exam::query()->create($data);

        return redirect()
            ->route('lecturer.practice-quizzes.edit', $exam)
            ->with('success', 'Practice quiz created. Add questions, then publish when ready.');
    }

    public function edit(Exam $quiz): Response
    {
        $this->authorizePractice($quiz);

        $quiz->loadCount('questions');

        $user = Auth::user();
        $onQuizBankIds = $quiz->questions()
            ->whereNotNull('question_bank_id')
            ->pluck('question_bank_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $bankQuestions = QuestionBank::query()
            ->visibleToLecturer($user)
            ->whereIn('status', ['approved', 'official'])
            ->whereNotIn('status', ['archived', 'draft', 'rejected', 'pending_official_approval'])
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'question_text', 'topic', 'question_type', 'suggested_marks'])
            ->map(fn (QuestionBank $q) => [
                'id' => $q->id,
                'preview' => Str::limit(strip_tags((string) $q->question_text), 140),
                'topic' => $q->topic,
                'question_type' => $q->question_type,
                'suggested_marks' => $q->suggested_marks,
                'already_on_quiz' => in_array((int) $q->id, $onQuizBankIds, true),
            ])
            ->values()
            ->all();

        return Inertia::render('Lecturer/PracticeQuizzes/Edit', [
            'quiz' => $quiz,
            'linkableCourses' => LinkableCourses::forUser($user),
            'schools' => School::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name', 'school_id']),
            'visibilityOptions' => collect(PracticeVisibility::cases())->map(fn ($c) => [
                'value' => $c->value,
                'label' => str_replace('_', ' ', ucfirst($c->value)),
            ]),
            'questionRoutes' => [
                'create' => route('lecturer.questions.create', $quiz),
                'import' => route('lecturer.questions.import', $quiz),
                'examShow' => route('lecturer.exams.show', $quiz),
            ],
            'bankQuestions' => $bankQuestions,
        ]);
    }

    public function attachBankQuestions(Request $request, Exam $quiz, QuestionBankService $questionBankService): RedirectResponse
    {
        $this->authorizePractice($quiz);

        $ids = $request->validate([
            'question_bank_ids' => 'required|array|min:1|max:50',
            'question_bank_ids.*' => 'integer|distinct|exists:question_bank,id',
        ])['question_bank_ids'];

        $added = $questionBankService->attachBankQuestionsToPracticeExam($quiz, Auth::user(), $ids);

        if ($added === 0) {
            return back()->withErrors([
                'question_bank_ids' => 'No new questions were added. Choose approved or official items in your bank that are not already on this quiz.',
            ]);
        }

        return back()->with('success', "{$added} question(s) added from your question bank.");
    }

    public function update(Request $request, Exam $quiz): RedirectResponse
    {
        $this->authorizePractice($quiz);

        $quiz->update($this->validatedExam($request, $quiz));

        return back()->with('success', 'Practice quiz updated.');
    }

    public function publish(Exam $quiz): RedirectResponse
    {
        $this->authorizePractice($quiz);

        if ($quiz->questions()->count() === 0) {
            return back()->withErrors(['publish' => 'Add at least one question before publishing.']);
        }

        $quiz->update([
            'is_published' => true,
            'is_public_practice' => true,
        ]);

        return back()->with('success', 'Practice quiz is now visible on the public Practice Hub (subject to visibility rules).');
    }

    public function unpublish(Exam $quiz): RedirectResponse
    {
        $this->authorizePractice($quiz);

        $quiz->update([
            'is_public_practice' => false,
        ]);

        return back()->with('success', 'Practice quiz removed from the public hub. Students with a direct link can no longer browse it from the hub listing.');
    }

    public function analytics(Exam $quiz): Response
    {
        $this->authorizePractice($quiz);

        $attempts = PracticeQuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->limit(500)
            ->get(['id', 'score', 'total_marks', 'weak_topics', 'responses', 'submitted_at', 'duration_seconds']);

        $count = $attempts->count();
        $avgPercent = 0.0;
        if ($count > 0) {
            $sum = 0.0;
            foreach ($attempts as $a) {
                $tm = max(1, (int) $a->total_marks);
                $sum += ((float) $a->score / $tm) * 100;
            }
            $avgPercent = round($sum / $count, 1);
        }

        $weakAgg = [];
        foreach ($attempts as $a) {
            $wt = $a->weak_topics;
            if (! is_array($wt)) {
                continue;
            }
            foreach ($wt as $t) {
                $t = trim((string) $t);
                if ($t === '') {
                    continue;
                }
                $weakAgg[$t] = ($weakAgg[$t] ?? 0) + 1;
            }
        }
        arsort($weakAgg);
        $weakTopics = collect($weakAgg)->take(15)->map(fn ($c, $t) => ['topic' => $t, 'count' => $c])->values()->all();

        $questionFails = $this->aggregateQuestionFailures($quiz, $attempts);

        $byDay = [];
        foreach ($attempts as $a) {
            if ($a->submitted_at === null) {
                continue;
            }
            $d = $a->submitted_at->format('Y-m-d');
            $byDay[$d] = ($byDay[$d] ?? 0) + 1;
        }
        ksort($byDay);
        $trend = collect($byDay)
            ->map(fn (int $c, string $d) => ['d' => $d, 'c' => $c])
            ->values()
            ->slice(-30)
            ->values()
            ->all();

        return Inertia::render('Lecturer/PracticeQuizzes/Analytics', [
            'quiz' => $quiz->only(['id', 'title', 'course_name', 'is_public_practice', 'is_published']),
            'stats' => [
                'attempts' => $count,
                'average_percent' => $avgPercent,
            ],
            'weakTopics' => $weakTopics,
            'questionFailures' => $questionFails,
            'trend' => $trend,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, PracticeQuizAttempt>  $attempts
     * @return list<array{question_id: int, misses: int, question_preview: string}>
     */
    private function aggregateQuestionFailures(Exam $quiz, Collection $attempts): array
    {
        $questions = $quiz->questions()->get(['id', 'question_text']);
        $fails = [];
        foreach ($questions as $q) {
            $fails[$q->id] = ['misses' => 0, 'preview' => Str::limit(strip_tags($q->question_text), 80)];
        }

        foreach ($attempts as $att) {
            $resp = $att->responses;
            if (! is_array($resp)) {
                continue;
            }
            foreach ($resp as $qid => $row) {
                $qid = (int) $qid;
                if (! isset($fails[$qid])) {
                    continue;
                }
                $correct = is_array($row) ? ($row['correct'] ?? false) : false;
                $awarded = is_array($row) ? (float) ($row['marks_awarded'] ?? 0) : 0.0;
                $max = is_array($row) ? (int) ($row['max'] ?? 0) : 0;
                if (! $correct || ($max > 0 && $awarded < $max)) {
                    $fails[$qid]['misses']++;
                }
            }
        }

        return collect($fails)
            ->map(fn ($meta, $qid) => [
                'question_id' => (int) $qid,
                'misses' => $meta['misses'],
                'question_preview' => $meta['preview'],
            ])
            ->sortByDesc('misses')
            ->values()
            ->take(20)
            ->all();
    }

    private function authorizePractice(Exam $quiz): void
    {
        if (! $quiz->practice_mode) {
            abort(404);
        }
        $this->authorize('update', $quiz);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedExam(Request $request, ?Exam $existing): array
    {
        foreach (['practice_duration_minutes', 'target_school_id', 'target_department_id', 'course_id'] as $k) {
            if ($request->input($k) === '') {
                $request->merge([$k => null]);
            }
        }

        if ($request->input('course_id') === '' || $request->input('course_id') === null) {
            $request->merge(['course_id' => null]);
        }
        if (trim((string) $request->input('course_code', '')) === '') {
            $request->merge(['course_code' => null]);
        }

        $data = $request->validate([
            'title' => 'required|string|max:100',
            'course_name' => 'required|string|max:100',
            'course_code' => 'nullable|string|max:50',
            'course_id' => ['nullable', 'integer', Rule::exists('courses', 'id')],
            'instructions' => 'nullable|string',
            'duration' => 'required|integer|min:1|max:600',
            'practice_duration_minutes' => 'nullable|integer|min:1|max:600',
            'target_school_id' => 'nullable|integer|exists:schools,id',
            'target_department_id' => 'nullable|integer|exists:departments,id',
            'target_program' => 'nullable|string|max:120',
            'target_level' => 'nullable|string|max:60',
            'visibility_status' => ['required', 'string', Rule::enum(PracticeVisibility::class)],
            'difficulty_level' => 'nullable|string|max:32',
            'practice_topic' => 'nullable|string|max:150',
            'allow_retry' => 'boolean',
            'show_answers_after_submit' => 'boolean',
            'show_explanations' => 'boolean',
            'ai_recommended' => 'boolean',
            'is_featured_practice' => 'boolean',
        ]);

        if (! empty($data['course_id'])) {
            $this->authorize('view', Course::findOrFail((int) $data['course_id']));
        } else {
            $data['course_id'] = null;
        }

        $data['allow_retry'] = $request->boolean('allow_retry', true);
        $data['show_answers_after_submit'] = $request->boolean('show_answers_after_submit', true);
        $data['show_explanations'] = $request->boolean('show_explanations', true);
        $data['ai_recommended'] = $request->boolean('ai_recommended', false);
        $data['is_featured_practice'] = $request->boolean('is_featured_practice', false);

        return $data;
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'PQ'.strtoupper(Str::random(6));
        } while (Exam::where('exam_code', $code)->exists());

        return $code;
    }
}
