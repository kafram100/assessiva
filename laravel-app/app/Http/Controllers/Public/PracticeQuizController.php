<?php

namespace App\Http\Controllers\Public;

use App\Enums\PracticeVisibility;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Department;
use App\Models\Exam;
use App\Models\PracticeQuizAttempt;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\School;
use App\Services\PracticeFeedbackService;
use App\Services\ScriptMarkingApiService;
use App\Support\AnswerScoring;
use App\Support\AnswerTextNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PracticeQuizController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $this->filterBag($request);
        $this->rememberFiltersInSession($request);

        $base = Exam::query()
            ->with(['owner:id,name', 'targetDepartment:id,name', 'targetSchool:id,name'])
            ->withCount([
                'practiceQuizAttempts as attempts_count' => fn ($q) => $q->whereNotNull('submitted_at'),
            ])
            ->practiceBrowseable()
            ->practiceFilters($filters)
            ->practiceVisibilityForFilters($filters);

        $sort = (string) $request->input('sort', 'featured');
        match ($sort) {
            'newest' => $base->orderByDesc('exams.created_at'),
            'most_attempted' => $base->orderByDesc('attempts_count')->orderByDesc('exams.created_at'),
            'ai_recommended' => $base->orderByDesc('ai_recommended')->orderByDesc('exams.created_at'),
            default => $base->orderByDesc('is_featured_practice')
                ->orderByDesc('ai_recommended')
                ->orderByDesc('attempts_count')
                ->orderByDesc('exams.created_at'),
        };

        $quizzes = $base->limit(80)->get();

        $featured = $quizzes->where('is_featured_practice', true)->take(8)->values();
        $aiPicks = $quizzes->where('ai_recommended', true)->take(8)->values();
        $trending = $quizzes->sortByDesc('attempts_count')->take(8)->values();

        return Inertia::render('PracticeQuizzes/Index', [
            'quizzes' => $quizzes,
            'featured' => $featured,
            'aiRecommended' => $aiPicks,
            'trending' => $trending,
            'filters' => $filters,
            'sort' => $sort,
            'schools' => School::query()->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name', 'school_id']),
            'programs' => $this->distinctExamField('target_program'),
            'levels' => $this->distinctExamField('target_level'),
            'topics' => $this->distinctExamField('practice_topic'),
            'availableCourses' => Course::query()
                ->pluck('name')
                ->merge(
                    QuestionBank::query()
                        ->where('is_active', true)
                        ->whereNotNull('topic')
                        ->where('topic', '!=', '')
                        ->pluck('topic')
                )
                ->filter(fn ($v) => !empty(trim((string)$v)))
                ->unique()
                ->sort()
                ->values(),
            'difficulties' => $this->distinctExamField('difficulty_level'),
        ]);
    }

    public function show(Request $request, Exam $quiz): Response
    {
        $this->assertPracticeQuiz($quiz);
        if (! $this->viewerMaySeeQuiz($request, $quiz)) {
            abort(403, 'This practice quiz is restricted. Use the filters on the Practice Hub (department or program) that match this quiz, then open it again from the list.');
        }

        $quiz->loadCount([
            'practiceQuizAttempts as attempts_count' => fn ($q) => $q->whereNotNull('submitted_at'),
        ]);
        $quiz->loadCount('questions');
        $quiz->load(['owner:id,name']);

        return Inertia::render('PracticeQuizzes/Show', [
            'quiz' => $quiz,
            'filterQuery' => $request->only(['school_id', 'department_id', 'program', 'level']),
        ]);
    }

    public function start(Request $request, Exam $quiz): RedirectResponse
    {
        $this->assertPracticeQuiz($quiz);

        $user = auth()->user();

        if (! $quiz->allow_retry) {
            $exists = PracticeQuizAttempt::query()
                ->where('quiz_id', $quiz->id)
                ->whereNotNull('submitted_at')
                ->where('student_email', $user->email)
                ->exists();
            if ($exists) {
                return back()->withErrors([
                    'quiz' => 'This quiz allows only one completed attempt.',
                ])->withInput();
            }
        }

        $questionCount = $quiz->questions()->count();
        $tokenPrice = (int) \App\Models\AppSetting::getValue('token_price_per_question', '1');
        $cost = $questionCount * $tokenPrice;

        $available = (int) ($user->free_questions_remaining + $user->tokens);
        if ($available < $cost) {
            $needed = $cost - $available;
            $msg = $available > 0
                ? "You have {$available} question token(s) available, but this quiz requires {$cost} tokens ({$needed} more needed). Please top up your tokens."
                : "You need {$cost} tokens to start this quiz. Please Top Up.";

            return back()->withErrors([
                'quiz' => $msg,
            ])->withInput();
        }

        // Deduct tokens
        $remainingCost = $cost;
        if ($user->free_questions_remaining >= $remainingCost) {
            $user->decrement('free_questions_remaining', $remainingCost);
        } else {
            $remainingCost -= $user->free_questions_remaining;
            $user->free_questions_remaining = 0;
            $user->decrement('tokens', $remainingCost);
            $user->save();
        }

        $attempt = PracticeQuizAttempt::query()->create([
            'quiz_id' => $quiz->id,
            'student_name' => $user->name,
            'student_email' => $user->email,
            'started_at' => now(),
        ]);

        session([$this->sessionKey($quiz) => $attempt->id]);

        return redirect()->route('practice-quizzes.take', ['quiz' => $quiz->id]);
    }

    public function take(Request $request, Exam $quiz): Response|RedirectResponse
    {
        $this->assertPracticeQuiz($quiz);
        if (! $this->viewerMaySeeQuiz($request, $quiz)) {
            abort(403);
        }

        $attemptId = (int) session($this->sessionKey($quiz), 0);
        $attempt = PracticeQuizAttempt::query()->find($attemptId);
        if (! $attempt || $attempt->quiz_id !== $quiz->id || $attempt->submitted_at !== null) {
            return redirect()
                ->route('practice-quizzes.show', ['quiz' => $quiz->id] + $request->only(['school_id', 'department_id', 'program', 'level']))
                ->withErrors(['quiz' => 'Start the quiz again to continue.']);
        }

        $questions = $quiz->questions()
            ->with('questionBank:id,topic')
            ->orderBy('id')
            ->get(['id', 'exam_id', 'question_text', 'q_type', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'marks']);

        return Inertia::render('PracticeQuizzes/TakeQuiz', [
            'quiz' => $quiz->only([
                'id', 'title', 'course_name', 'instructions',
                'practice_duration_minutes', 'show_answers_after_submit', 'show_explanations',
            ]),
            'questions' => $questions->map(fn (Question $q) => $this->questionForClient($q)),
            'attemptId' => $attempt->id,
            'startedAtIso' => $attempt->started_at?->toIso8601String(),
        ]);
    }

    public function submit(Request $request, Exam $quiz, PracticeFeedbackService $feedback, ScriptMarkingApiService $marking): RedirectResponse
    {
        $this->assertPracticeQuiz($quiz);

        $attemptId = (int) session($this->sessionKey($quiz), 0);
        $attempt = PracticeQuizAttempt::query()->find($attemptId);
        if (! $attempt || $attempt->quiz_id !== $quiz->id || $attempt->submitted_at !== null) {
            return redirect()->route('practice-quizzes.show', $quiz->id)->withErrors(['quiz' => 'Invalid session. Open the quiz and start again.']);
        }

        $questions = $quiz->questions()->with('questionBank:id,topic')->orderBy('id')->get();
        if ($questions->isEmpty()) {
            return redirect()->route('practice-quizzes.show', $quiz->id)->withErrors(['quiz' => 'This quiz has no questions yet.']);
        }

        $request->validate([
            'answers' => 'nullable|array',
            'answers.*' => 'nullable|string|max:20000',
            'fill_in_answers' => 'nullable|array',
            'fill_in_answers.*' => 'nullable|string|max:20000',
            'theory_answers' => 'nullable|array',
            'theory_answers.*' => 'nullable|string|max:50000',
        ]);

        $graded = $this->gradePracticeAttempt($quiz, $questions, $request, $marking);

        $weakTopics = array_values(array_unique($graded['weak_topics']));
        $failedQuestions = $graded['failed_question_summaries'];

        $aiPayload = [
            'student_score' => $graded['percent'],
            'course' => (string) $quiz->course_name,
            'weak_topics' => $weakTopics,
            'failed_questions' => $failedQuestions,
            'difficulty_level' => (string) ($quiz->difficulty_level ?? 'medium'),
            'available_quiz_titles' => Exam::query()
                ->practiceBrowseable()
                ->where('id', '!=', $quiz->id)
                ->orderByDesc('ai_recommended')
                ->limit(12)
                ->pluck('title')
                ->all(),
        ];

        $ai = $feedback->generate($aiPayload);

        $submittedAt = now();
        $durationSeconds = max(0, (int) $submittedAt->diffInSeconds($attempt->started_at));

        DB::transaction(function () use ($attempt, $graded, $weakTopics, $ai, $submittedAt, $durationSeconds): void {
            $attempt->update([
                'score' => $graded['score'],
                'total_marks' => $graded['total_marks'],
                'responses' => $graded['responses'],
                'submitted_at' => $submittedAt,
                'duration_seconds' => $durationSeconds,
                'weak_topics' => $weakTopics,
                'ai_feedback' => $ai,
            ]);
        });

        session()->forget($this->sessionKey($quiz));

        return redirect()->route('practice-quizzes.results', [
            'quiz' => $quiz->id,
            'attempt' => $attempt->id,
        ]);
    }

    public function results(Request $request, Exam $quiz, PracticeQuizAttempt $attempt): Response
    {
        $this->assertPracticeQuiz($quiz);
        if ((int) $attempt->quiz_id !== (int) $quiz->id) {
            abort(404);
        }
        if ($attempt->submitted_at === null) {
            return redirect()->route('practice-quizzes.take', $quiz->id);
        }

        $questions = $quiz->questions()->with('questionBank:id,topic')->orderBy('id')->get();
        $responses = is_array($attempt->responses) ? $attempt->responses : [];

        $rollup = $this->rollupScoresFromQuestionsAndResponses($questions, $responses);
        if ($rollup['total_marks'] > 0) {
            $dbScore = (float) ($attempt->score ?? 0);
            $dbTotal = (int) ($attempt->total_marks ?? 0);
            if (abs($dbScore - $rollup['score']) > 0.01 || $dbTotal !== $rollup['total_marks']) {
                $attempt->update([
                    'score' => $rollup['score'],
                    'total_marks' => $rollup['total_marks'],
                ]);
                $attempt->refresh();
            }
        }

        $displayScore = $rollup['total_marks'] > 0 ? $rollup['score'] : (float) ($attempt->score ?? 0);
        $displayTotal = $rollup['total_marks'] > 0 ? $rollup['total_marks'] : (int) ($attempt->total_marks ?? 0);
        $displayPercent = $displayTotal > 0 ? (int) round(($displayScore / $displayTotal) * 100) : 0;

        return Inertia::render('PracticeQuizzes/Results', [
            'quiz' => $quiz->only([
                'id', 'title', 'course_name', 'practice_topic',
                'show_answers_after_submit', 'show_explanations', 'allow_retry',
                'difficulty_level',
            ]),
            'attempt' => [
                'id' => $attempt->id,
                'score' => $displayScore,
                'total_marks' => $displayTotal,
                'percent' => $displayPercent,
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'duration_seconds' => $attempt->duration_seconds,
                'weak_topics' => $attempt->weak_topics ?? [],
                'ai_feedback' => $attempt->ai_feedback ?? [],
            ],
            'questions' => $questions->map(function (Question $q) use ($quiz, $responses) {
                $row = $responses[(string) $q->id] ?? $responses[$q->id] ?? null;
                $show = $quiz->show_answers_after_submit;
                $base = $this->questionForClient($q);

                return array_merge($base, [
                    'your_answer' => is_array($row) ? ($row['student'] ?? null) : null,
                    'marks_awarded' => is_array($row) ? ($row['marks_awarded'] ?? null) : null,
                    'correct' => is_array($row) ? ($row['correct'] ?? null) : null,
                    'correct_option' => ($show && $this->qType($q) === 'mcq') ? $q->correct_option : null,
                    'model_answer' => ($show && in_array($this->qType($q), ['fill_in', 'theory', 'short_answer'], true)) ? $q->correct_option : null,
                ]);
            }),
        ]);
    }

    private function sessionKey(Exam $quiz): string
    {
        return 'practice_quiz_attempt_'.$quiz->id;
    }

    private function assertPracticeQuiz(Exam $exam): void
    {
        if (! $exam->practice_mode || ! $exam->is_public_practice || ! $exam->is_published) {
            abort(404);
        }
    }

    private function viewerMaySeeQuiz(Request $request, Exam $quiz): bool
    {
        $vis = $quiz->visibility_status instanceof PracticeVisibility
            ? $quiz->visibility_status
            : PracticeVisibility::tryFrom((string) $quiz->visibility_status) ?? PracticeVisibility::Public;

        if ($vis === PracticeVisibility::Public) {
            return true;
        }

        if ($vis === PracticeVisibility::DepartmentOnly) {
            $d = (int) ($request->query('department_id') ?: session('practice_filter_department_id', 0));

            return $d > 0 && (int) ($quiz->target_department_id ?? 0) === $d;
        }

        if ($vis === PracticeVisibility::ProgramOnly) {
            $p = trim((string) ($request->query('program') ?: session('practice_filter_program', '')));
            $target = trim((string) ($quiz->target_program ?? ''));

            return $p !== '' && strcasecmp($p, $target) === 0;
        }

        return false;
    }

    private function rememberFiltersInSession(Request $request): void
    {
        if ($request->filled('department_id')) {
            session(['practice_filter_department_id' => (int) $request->input('department_id')]);
        }
        if ($request->filled('program')) {
            session(['practice_filter_program' => (string) $request->input('program')]);
        }
        if ($request->filled('school_id')) {
            session(['practice_filter_school_id' => (int) $request->input('school_id')]);
        }
    }

    /**
     * @return array<string, string|null>
     */
    private function filterBag(Request $request): array
    {
        return [
            'school_id' => $request->input('school_id'),
            'department_id' => $request->input('department_id'),
            'program' => $request->input('program'),
            'level' => $request->input('level'),
            'course' => $request->input('course'),
            'topic' => $request->input('topic'),
            'owner_id' => $request->input('owner_id'),
            'difficulty' => $request->input('difficulty'),
            'search' => $request->input('search'),
        ];
    }

    /**
     * @return Collection<int, string|null>
     */
    private function distinctExamField(string $column)
    {
        return Exam::query()
            ->practiceBrowseable()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->limit(80)
            ->pluck($column)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function questionForClient(Question $q): array
    {
        return [
            'id' => $q->id,
            'question_text' => $q->question_text,
            'q_type' => $this->qType($q),
            'option_a' => $q->option_a,
            'option_b' => $q->option_b,
            'option_c' => $q->option_c,
            'option_d' => $q->option_d,
            'option_e' => $q->option_e,
            'marks' => (int) $q->marks,
            'topic' => $q->questionBank?->topic,
        ];
    }

    private function qType(Question $q): string
    {
        return AnswerScoring::qType($q);
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @return array{score: float, total_marks: int, responses: array<string, array<string, mixed>>, weak_topics: list<string>, failed_question_summaries: list<string>, percent: int}
     */
    private function gradePracticeAttempt(Exam $exam, $questions, Request $request, ScriptMarkingApiService $marking): array
    {
        $responses = [];
        $weakTopics = [];
        $failedSummaries = [];

        foreach ($questions as $q) {
            $type = $this->qType($q);
            $marks = max(0, (int) $q->marks);
            $topic = $this->topicLabel($q, $exam);
            $awarded = 0;
            $correct = false;
            $studentVal = null;

            if ($type === 'mcq') {
                $sel = $this->nestedAnswerInput($request, 'answers', $q->id);
                $studentVal = $sel !== '' ? strtoupper(substr($sel, 0, 1)) : '';
                if ($studentVal !== '' && ! str_contains('ABCDE', $studentVal)) {
                    $studentVal = '';
                }
                $corKey = $this->normalizeMcqCorrectLetter($q->correct_option);
                $correct = $studentVal !== '' && $corKey !== '' && hash_equals($corKey, $studentVal);
                $awarded = $correct ? $marks : 0;
                if (! $correct) {
                    $weakTopics[] = $topic;
                    $failedSummaries[] = Str::limit(strip_tags($q->question_text), 120);
                }
            } elseif ($type === 'fill_in') {
                $text = $this->nestedAnswerInput($request, 'fill_in_answers', $q->id);
                $studentVal = $text;
                $expected = trim((string) ($q->correct_option ?? ''));
                if (AnswerTextNormalizer::fillInMatches($text, $expected)) {
                    $correct = true;
                    $awarded = $marks;
                } elseif ($marking->shouldMarkByApi('fill_in')) {
                    $m = $marking->markAnswer($q, $text, 'fill_in');
                    if ($m !== null) {
                        $awarded = max(0, min($marks, (float) $m));
                        $correct = $awarded >= $marks;
                    }
                }
                if ($awarded < $marks) {
                    $weakTopics[] = $topic;
                    $failedSummaries[] = Str::limit(strip_tags($q->question_text), 120);
                }
            } elseif ($type === 'theory' || $type === 'short_answer') {
                $text = $this->nestedAnswerInput($request, 'theory_answers', $q->id);
                $studentVal = $text;
                if ($text !== '' && $marking->shouldMarkByApi($type)) {
                    $m = $marking->markAnswer($q, $text, $type);
                    if ($m !== null) {
                        $awarded = max(0, min($marks, (float) $m));
                        $correct = $awarded >= $marks;
                    }
                }
                if ($awarded < $marks) {
                    $weakTopics[] = $topic;
                    $failedSummaries[] = Str::limit(strip_tags($q->question_text), 120);
                }
            } else {
                $studentVal = null;
                $awarded = 0;
                $correct = false;
                $weakTopics[] = $topic;
            }

            $responses[(string) $q->id] = [
                'type' => $type,
                'student' => $studentVal,
                'marks_awarded' => $awarded,
                'max' => $marks,
                'correct' => $correct,
            ];
        }

        $synced = $this->rollupScoresFromResponsesArray($responses);
        $totalScore = $synced['score'];
        $totalMarks = $synced['total_marks'];
        $percent = $totalMarks > 0 ? (int) round(($totalScore / $totalMarks) * 100) : 0;

        return [
            'score' => round($totalScore, 2),
            'total_marks' => $totalMarks,
            'responses' => $responses,
            'weak_topics' => $weakTopics,
            'failed_question_summaries' => array_values(array_unique(array_filter($failedSummaries))),
            'percent' => $percent,
        ];
    }

    private function topicLabel(Question $q, Exam $exam): string
    {
        $t = $q->questionBank?->topic;
        if ($t !== null && trim((string) $t) !== '') {
            return trim((string) $t);
        }
        $pt = $exam->practice_topic;
        if ($pt !== null && trim((string) $pt) !== '') {
            return trim((string) $pt);
        }

        return 'General';
    }

    /**
     * Read nested JSON/form fields for answers.{id} when the client may send
     * numeric keys as strings (Inertia + JSON) so dot-notation alone can miss values.
     */
    private function nestedAnswerInput(Request $request, string $prefix, int|string $questionId): string
    {
        $idStr = (string) $questionId;
        $dotted = trim((string) $request->input("{$prefix}.{$questionId}", ''));
        if ($dotted !== '') {
            return $dotted;
        }
        $dottedStr = trim((string) $request->input("{$prefix}.{$idStr}", ''));
        if ($dottedStr !== '') {
            return $dottedStr;
        }
        $bucket = $request->input($prefix);
        if (! is_array($bucket)) {
            return '';
        }
        foreach ([$questionId, $idStr] as $key) {
            if (array_key_exists($key, $bucket)) {
                return trim((string) $bucket[$key]);
            }
        }

        return '';
    }

    /**
     * Normalize stored correct_option to a single letter A–E for comparison with the UI.
     */
    private function normalizeMcqCorrectLetter(?string $correctOption): string
    {
        $t = strtoupper(trim((string) $correctOption));
        if ($t === '') {
            return '';
        }
        if (strlen($t) === 1 && str_contains('ABCDE', $t)) {
            return $t;
        }
        if (preg_match('/^([A-E])\b/i', $t, $m)) {
            return strtoupper($m[1]);
        }
        $first = strtoupper(substr(preg_replace('/\s+/', '', $t), 0, 1));

        return str_contains('ABCDE', $first) ? $first : '';
    }

    /**
     * @param  array<string|int, mixed>  $responses
     * @return array{score: float, total_marks: int}
     */
    private function rollupScoresFromResponsesArray(array $responses): array
    {
        $score = 0.0;
        $total = 0;
        foreach ($responses as $row) {
            if (! is_array($row)) {
                continue;
            }
            $total += max(0, (int) ($row['max'] ?? 0));
            $score += max(0.0, (float) ($row['marks_awarded'] ?? 0));
        }

        return [
            'score' => round($score, 2),
            'total_marks' => $total,
        ];
    }

    /**
     * Recompute headline totals from each question row + live question marks (max fallback).
     *
     * @param  Collection<int, Question>  $questions
     * @param  array<string|int, mixed>  $responses
     * @return array{score: float, total_marks: int}
     */
    private function rollupScoresFromQuestionsAndResponses(Collection $questions, array $responses): array
    {
        $score = 0.0;
        $total = 0;
        foreach ($questions as $q) {
            $row = $responses[(string) $q->id] ?? $responses[$q->id] ?? null;
            $fromQuestion = max(0, (int) $q->marks);
            $fromRow = is_array($row) ? max(0, (int) ($row['max'] ?? 0)) : 0;
            $maxMarks = max($fromQuestion, $fromRow);
            $total += $maxMarks;
            if (is_array($row) && array_key_exists('marks_awarded', $row)) {
                $score += max(0.0, (float) $row['marks_awarded']);
            }
        }

        return [
            'score' => round($score, 2),
            'total_marks' => $total,
        ];
    }
}
