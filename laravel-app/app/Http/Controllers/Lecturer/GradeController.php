<?php

namespace App\Http\Controllers\Lecturer;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Mail\ExamResultsReleasedMail;
use App\Models\Answer;
use App\Models\AppSetting;
use App\Models\Attempt;
use App\Models\CheatEvent;
use App\Models\CheatingIncident;
use App\Models\Exam;
use App\Models\ExamActivityLog;
use App\Models\ExamAllowedIndex;
use App\Models\ExamSecurityLog;
use App\Models\ExamSessionProctoring;
use App\Models\Question;
use App\Models\RevisionPlan;
use App\Services\AiRevisionPlanService;
use App\Services\ProctoringPenaltyService;
use App\Support\AnswerScoring;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GradeController extends Controller
{
    public function stats(Request $request, Exam $exam): Response
    {
        Gate::authorize('view', $exam);

        $attempts = Attempt::query()
            ->with('answers')
            ->withCount(['cheatEvents', 'cheatingIncidents'])
            ->where('exam_id', $exam->id)
            ->where('status', 'completed')
            ->orderByDesc('submit_time')
            ->get();

        $avgScore = $attempts->avg('score');
        $highest = $attempts->max('score');
        $lowest = $attempts->min('score');

        $proctoringSnapshotCounts = $this->proctoringSnapshotCountsByAttemptId();

        return Inertia::render('Lecturer/Exams/Stats', [
            'exam' => $exam,
            'attempts' => $attempts,
            'avgScore' => $avgScore !== null ? round((float) $avgScore, 2) : null,
            'highest' => $highest !== null ? (float) $highest : null,
            'lowest' => $lowest !== null ? (float) $lowest : null,
            'proctoringSnapshotCounts' => $proctoringSnapshotCounts,
        ]);
    }

    public function showAttempt(Request $request, Attempt $attempt): Response
    {
        $exam = $attempt->exam;
        Gate::authorize('view', $exam);

        $attempt->loadCount(['cheatEvents', 'cheatingIncidents']);

        $penaltySummary = app(ProctoringPenaltyService::class)->penaltySummary($attempt);

        $cheatEventsForAttempt = CheatEvent::query()
            ->where('attempt_id', $attempt->id)
            ->orderByDesc('created_at')
            ->get();

        $answers = Answer::query()
            ->with('question')
            ->where('attempt_id', $attempt->id)
            ->get()
            ->keyBy('question_id');

        $questions = $exam->questions()->get();

        $contributions = [];
        foreach ($answers as $ans) {
            $contributions[$ans->id] = AnswerScoring::contribution($ans, $ans->question);
        }

        $proctoringSnapshots = $this->proctoringSnapshotsForAttempt((int) $attempt->id);

        return Inertia::render('Lecturer/Grades/Attempt', [
            'attempt' => $attempt,
            'exam' => $exam,
            'answers' => $answers->values()->all(),
            'answerContributions' => $contributions,
            'questions' => $questions,
            'proctoringSnapshots' => $proctoringSnapshots,
            'revisionPlanExists' => RevisionPlan::query()->where('exam_attempt_id', $attempt->id)->exists(),
            'penaltySummary' => $penaltySummary,
            'cheatEventsReview' => $cheatEventsForAttempt,
        ]);
    }

    public function storeGrade(Request $request, Attempt $attempt): RedirectResponse
    {
        $exam = $attempt->exam;
        Gate::authorize('view', $exam);

        $request->validate([
            'marks' => 'array',
            'marks.*' => 'nullable|numeric|min:0',
            'comments' => 'sometimes|array',
            'comments.*' => 'nullable|string|max:2000',
        ]);

        $grades = $request->input('marks', []);
        $comments = $request->input('comments', []);

        $answerIds = Answer::query()
            ->where('attempt_id', $attempt->id)
            ->pluck('id')
            ->all();

        foreach ($answerIds as $aid) {
            $key = (string) $aid;
            $marks = $grades[$key] ?? null;
            $comment = $comments[$key] ?? null;

            $payload = [];
            if ($marks !== null && $marks !== '') {
                $payload['marks_awarded'] = max(0, (float) $marks);
            }
            if (is_string($comment)) {
                $trim = trim($comment);
                $payload['lecturer_grade_comment'] = $trim === '' ? null : mb_substr($trim, 0, 2000);
            }
            if ($payload === []) {
                continue;
            }
            Answer::query()
                ->where('id', $aid)
                ->where('attempt_id', $attempt->id)
                ->update($payload);
        }

        $totalScore = Answer::query()
            ->where('attempt_id', $attempt->id)
            ->with('question')
            ->get()
            ->sum(function (Answer $ans) {
                return AnswerScoring::contribution($ans, $ans->question);
            });

        app(ProctoringPenaltyService::class)->setRawScoreFromGrading($attempt, (float) $totalScore);

        return redirect()
            ->route('lecturer.grades.attempt', $attempt)
            ->with('success', 'Grades saved.');
    }

    public function downloadResults(Request $request, Exam $exam): StreamedResponse
    {
        Gate::authorize('view', $exam);

        $attempts = Attempt::query()
            ->where('exam_id', $exam->id)
            ->where('status', 'completed')
            ->orderByDesc('submit_time')
            ->get(['student_fullname', 'student_index', 'score', 'raw_score', 'proctoring_penalty_total', 'total_marks', 'submit_time']);

        $filename = sprintf(
            'results_%s_%s.csv',
            preg_replace('/[^A-Za-z0-9_-]+/', '_', strtolower($exam->exam_code ?? 'exam')),
            now()->format('Ymd_His')
        );

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($attempts, $exam): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Exam Title', $exam->title]);
            fputcsv($out, ['Course', $exam->course_name]);
            fputcsv($out, []);
            fputcsv($out, ['Student Name', 'Index Number', 'Raw score', 'Proctoring penalty', 'Adjusted score', 'Total Marks', 'Percentage', 'Submitted At']);

            foreach ($attempts as $a) {
                $raw = (float) ($a->raw_score ?? $a->score ?? 0);
                $pen = (float) ($a->proctoring_penalty_total ?? 0);
                $adj = (float) ($a->score ?? 0);
                $total = (float) ($a->total_marks ?? 0);
                $pct = $total > 0 ? round(($adj / $total) * 100, 2) : 0;
                fputcsv($out, [
                    $a->student_fullname ?: '',
                    $a->student_index ?: '',
                    $raw,
                    $pen,
                    $adj,
                    $total,
                    $pct,
                    optional($a->submit_time)->format('Y-m-d H:i:s') ?: '',
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function releaseResults(Request $request, Exam $exam): RedirectResponse
    {
        Gate::authorize('view', $exam);

        $this->applySharedMailSettings();

        if ($exam->end_datetime && now()->lt($exam->end_datetime)) {
            return redirect()
                ->route('lecturer.exams.stats', $exam)
                ->with('error', 'You can only release results after the exam end time.');
        }

        $attempts = Attempt::query()
            ->with(['answers.question'])
            ->where('exam_id', $exam->id)
            ->where('status', 'completed')
            ->whereNotNull('student_email')
            ->get();

        if ($attempts->isEmpty()) {
            return redirect()
                ->route('lecturer.exams.stats', $exam)
                ->with('error', 'No completed attempts with student emails were found.');
        }

        $sent = 0;
        $failed = 0;

        foreach ($attempts as $attempt) {
            if (! $attempt instanceof Attempt) {
                continue;
            }
            try {
                $reviewItems = $this->buildReviewItems($attempt);
                $wrongItems = array_values(array_filter(
                    $reviewItems,
                    static fn (array $item): bool => ($item['result'] ?? '') === 'Wrong'
                ));
                Mail::to($attempt->student_email)->send(new ExamResultsReleasedMail($exam, $attempt, $wrongItems, $reviewItems));
                $sent++;
            } catch (Throwable) {
                $failed++;
            }
        }

        $exam->update(['results_released' => true]);

        try {
            app(AiRevisionPlanService::class)->generateAllForReleasedExam($exam->fresh());
        } catch (Throwable) {
            // Best-effort; revision plans can be generated later from the lecturer UI.
        }

        $message = "Results released. {$sent} email(s) sent.";
        if ($failed > 0) {
            $message .= " {$failed} failed.";
        }

        return redirect()->route('lecturer.exams.stats', $exam)->with('success', $message);
    }

    public function resetStudentAttempts(Request $request, Exam $exam): RedirectResponse
    {
        Gate::authorize('view', $exam);

        $data = $request->validate([
            'student_index' => 'required|string|max:50',
        ]);

        $normalized = ExamAllowedIndex::normalize($data['student_index']);
        if ($normalized === '') {
            return back()->with('error', 'Invalid student index.');
        }

        $attemptIds = Attempt::query()
            ->where('exam_id', $exam->id)
            ->whereRaw('UPPER(TRIM(student_index)) = ?', [$normalized])
            ->pluck('id');

        if ($attemptIds->isEmpty()) {
            return back()->with('error', 'No attempts found for that student index.');
        }

        DB::transaction(function () use ($attemptIds): void {
            foreach ($attemptIds as $id) {
                ExamSessionProctoring::query()->where('exam_attempt_id', $id)->delete();
                ExamSecurityLog::query()->where('exam_attempt_id', $id)->delete();
                ExamActivityLog::query()->where('exam_attempt_id', $id)->delete();
                CheatingIncident::query()->where('exam_attempt_id', $id)->delete();
            }
            Attempt::query()->whereIn('id', $attemptIds)->delete();
        });

        return redirect()
            ->route('lecturer.exams.stats', $exam)
            ->with('success', "Attempts reset for index {$normalized}. The student can now take the exam again.");
    }

    private function applySharedMailSettings(): void
    {
        config([
            'mail.default' => AppSetting::getValue('mail_mailer', config('mail.default')),
            'mail.mailers.smtp.host' => AppSetting::getValue('mail_host', config('mail.mailers.smtp.host')),
            'mail.mailers.smtp.port' => (int) AppSetting::getValue('mail_port', (string) config('mail.mailers.smtp.port')),
            'mail.mailers.smtp.encryption' => AppSetting::getValue('mail_encryption', config('mail.mailers.smtp.encryption')),
            'mail.mailers.smtp.username' => AppSetting::getValue('mail_username', config('mail.mailers.smtp.username')),
            'mail.mailers.smtp.password' => AppSetting::getValue('mail_password', config('mail.mailers.smtp.password')),
            'mail.from.address' => AppSetting::getValue('mail_from_address', config('mail.from.address')),
            'mail.from.name' => AppSetting::getValue('mail_from_name', config('mail.from.name')),
        ]);
    }

    /**
     * @return list<array{question: string, student_answer: string, correct_answer: string, result: string}>
     */
    private function buildReviewItems(Attempt $attempt): array
    {
        $items = [];

        foreach ($attempt->answers as $answer) {
            $question = $answer->question;

            if (! $question instanceof Question) {
                $items[] = [
                    'question' => 'Question text unavailable',
                    'student_answer' => 'Answer data unavailable',
                    'correct_answer' => 'Answer key unavailable',
                    'result' => 'Wrong',
                ];

                continue;
            }

            $isCorrect = $this->isAnswerCorrect(
                $question,
                $answer->is_correct,
                $answer->marks_awarded,
                (float) ($question->marks ?? 0)
            );

            $items[] = [
                'question' => $question->question_text,
                'student_answer' => $this->formatStudentAnswer($question, $answer),
                'correct_answer' => $this->formatCorrectAnswer($question),
                'result' => $isCorrect ? 'Correct' : 'Wrong',
            ];
        }

        return $items;
    }

    private function isAnswerCorrect(Question $question, mixed $isCorrect, mixed $marksAwarded, float $questionMarks): bool
    {
        if ($question->q_type === QuestionType::Mcq || $question->q_type === QuestionType::FillIn) {
            return (int) $isCorrect === 1;
        }

        if ($marksAwarded === null) {
            return false;
        }

        return (float) $marksAwarded >= $questionMarks;
    }

    private function formatStudentAnswer(Question $question, Answer $answer): string
    {
        if ($question->q_type === QuestionType::Mcq) {
            $key = strtoupper((string) ($answer->selected_option ?? ''));
            if ($key === '') {
                return 'No answer submitted';
            }

            $optionText = $question->{'option_'.strtolower($key)} ?? null;

            return $optionText ? "{$key}. {$optionText}" : $key;
        }

        if (in_array($question->q_type, [QuestionType::Theory, QuestionType::FillIn], true)) {
            $text = trim((string) ($answer->theory_answer ?? ''));

            return $text !== '' ? $text : 'No answer submitted';
        }

        return $answer->file_upload ? 'File submitted' : 'No file submitted';
    }

    private function formatCorrectAnswer(Question $question): string
    {
        if ($question->q_type === QuestionType::Mcq) {
            $key = strtoupper((string) ($question->correct_option ?? ''));
            if ($key !== '') {
                $optionText = $question->{'option_'.strtolower($key)} ?? null;

                return $optionText ? "{$key}. {$optionText}" : $key;
            }

            $fallback = $this->firstNonEmptyOptionText($question);

            return $fallback ?? 'Not provided';
        }

        $text = trim((string) ($question->correct_option ?? ''));
        if ($text !== '') {
            return $text;
        }

        $fallback = $this->firstNonEmptyOptionText($question);

        return $fallback ?? 'Not provided';
    }

    private function firstNonEmptyOptionText(Question $question): ?string
    {
        foreach (['option_a', 'option_b', 'option_c', 'option_d', 'option_e'] as $field) {
            $val = trim((string) ($question->{$field} ?? ''));
            if ($val !== '') {
                return $val;
            }
        }

        return null;
    }

    /**
     * @return list<array{url: string, label: string, ext: string, is_video: bool}>
     */
    private function proctoringSnapshotsForAttempt(int $attemptId): array
    {
        $disk = Storage::disk('public');
        if (! $disk->exists('proctoring')) {
            return [];
        }

        $jpgs = [];
        $videos = [];

        foreach ($disk->files('proctoring') as $path) {
            $base = basename($path);
            if (! preg_match('/^(\d+)_/', $base, $m) || (int) $m[1] !== $attemptId) {
                continue;
            }

            $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
            $name = pathinfo($base, PATHINFO_FILENAME);
            $label = 'Snapshot';
            if (preg_match('/^\d+_(.+)_(\d+)$/', $name, $mm)) {
                $label = ucwords(str_replace('_', ' ', $mm[1]));
            }

            $url = Storage::disk('public')->url($path);
            $row = [
                'url' => $url,
                'label' => $label,
                'ext' => $ext,
                'is_video' => in_array($ext, ['webm', 'mp4', 'mov'], true),
                'mtime' => $disk->lastModified($path),
            ];

            if ($row['is_video']) {
                $videos[] = $row;
            } elseif ($ext === 'jpg') {
                $jpgs[] = $row;
            }
        }

        usort($jpgs, fn ($a, $b) => ($a['mtime'] ?? 0) <=> ($b['mtime'] ?? 0));
        $jpgs = array_slice($jpgs, 0, 4);

        $out = array_merge($jpgs, $videos);
        usort($out, fn ($a, $b) => ($a['mtime'] ?? 0) <=> ($b['mtime'] ?? 0));

        return array_map(static function (array $row): array {
            unset($row['mtime']);

            return $row;
        }, $out);
    }

    /**
     * @return array<int, int>
     */
    private function proctoringSnapshotCountsByAttemptId(): array
    {
        $disk = Storage::disk('public');
        if (! $disk->exists('proctoring')) {
            return [];
        }

        $counts = [];
        foreach ($disk->files('proctoring') as $path) {
            $base = basename($path);
            if (strtolower((string) pathinfo($base, PATHINFO_EXTENSION)) !== 'jpg') {
                continue;
            }
            if (preg_match('/^(\d+)_/', $base, $m)) {
                $id = (int) $m[1];
                $counts[$id] = ($counts[$id] ?? 0) + 1;
            }
        }

        return $counts;
    }
}
