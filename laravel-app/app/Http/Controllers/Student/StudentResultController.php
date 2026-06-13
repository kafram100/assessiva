<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\ExamAllowedIndex;
use App\Models\Question;
use App\Models\RevisionPlan;
use App\Services\AiRevisionPlanService;
use App\Support\AnswerScoring;
use App\Support\AnswerTextNormalizer;
use App\Support\StudentFeedbackPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StudentResultController extends Controller
{
    public function lookupForm(Request $request): Response
    {
        $code = strtoupper(trim((string) $request->query('exam_code', '')));
        $index = trim((string) $request->query('student_index', ''));

        return Inertia::render('Student/Results/Lookup', [
            'prefill' => [
                'exam_code' => Str::limit($code, 32, ''),
                'student_index' => Str::limit($index, 50, ''),
            ],
        ]);
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'exam_code' => 'required|string|max:32',
            'student_index' => 'required|string|max:50',
        ]);

        $normalizedIndex = ExamAllowedIndex::normalize($data['student_index']);
        if ($normalizedIndex === '') {
            return back()->withErrors(['student_index' => 'Enter a valid index number.'])->withInput();
        }

        $code = strtoupper(trim($data['exam_code']));
        $exam = Exam::query()->where('exam_code', $code)->first();

        if ($exam === null) {
            return back()->withErrors(['exam_code' => 'Unknown exam code.'])->withInput();
        }

        $attempt = Attempt::query()
            ->where('exam_id', $exam->id)
            ->whereRaw('UPPER(TRIM(student_index)) = ?', [$normalizedIndex])
            ->where('status', 'completed')
            ->latest('submit_time')
            ->first();

        if ($attempt === null) {
            return back()->withErrors(['exam_code' => 'No completed attempt found for that index number.'])->withInput();
        }

        session([
            'student_results_verified_attempt_id' => $attempt->id,
            'student_results_exam_id' => $exam->id,
            'student_results_student_index' => $normalizedIndex,
            'student_results_student_name' => $attempt->student_fullname ?? '',
        ]);

        return redirect()->route('student.results.show', $attempt);
    }

    public function show(Attempt $attempt): Response|RedirectResponse
    {
        $exam = $attempt->exam;
        if ($exam === null) {
            abort(404);
        }

        $planExists = RevisionPlan::query()->where('exam_attempt_id', $attempt->id)->exists();
        $released = (bool) $exam->results_released;

        $attempt->loadMissing(['answers.question']);
        $revisionPlan = RevisionPlan::query()->where('exam_attempt_id', $attempt->id)->first();

        return Inertia::render('Student/Results/Show', [
            'attempt' => [
                'id' => $attempt->id,
                'score' => $released ? (float) $attempt->score : null,
                'total_marks' => $released ? (int) $attempt->total_marks : null,
                'raw_score' => $released ? ($attempt->raw_score !== null ? (float) $attempt->raw_score : null) : null,
                'proctoring_penalty_total' => $released ? ($attempt->proctoring_penalty_total !== null ? (float) $attempt->proctoring_penalty_total : null) : null,
                'status' => $attempt->status,
                'student_index' => $attempt->student_index,
                'student_name' => $attempt->student_fullname,
            ],
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'course_name' => $exam->course_name,
                'results_released' => $released,
                'show_student_ai_feedback' => (bool) $exam->show_student_ai_feedback,
                'show_student_expected_answers' => (bool) $exam->show_student_expected_answers,
                'show_student_revision_suggestions' => (bool) $exam->show_student_revision_suggestions,
            ],
            'revisionPlanExists' => $planExists,
            'feedback' => $this->buildStudentFeedbackPayload($attempt, $exam, $revisionPlan),
        ]);
    }

    public function showRevisionPlan(Attempt $attempt, AiRevisionPlanService $revisionPlanService): Response|RedirectResponse
    {
        $exam = $attempt->exam;
        if ($exam === null) {
            abort(404);
        }

        if (! $exam->results_released) {
            return redirect()
                ->route('student.results.show', $attempt)
                ->with('error', 'Results have not been released yet.');
        }

        if (! $exam->show_student_revision_suggestions) {
            return redirect()
                ->route('student.results.show', $attempt)
                ->with('error', 'Revision suggestions are not enabled for this exam.');
        }

        $plan = RevisionPlan::query()->where('exam_attempt_id', $attempt->id)->first();

        if ($plan === null) {
            try {
                $plan = $revisionPlanService->generateAndStore($attempt, null, false);
            } catch (\Throwable) {
                return Inertia::render('Student/RevisionPlan/Show', [
                    'attempt' => ['id' => $attempt->id],
                    'exam' => ['title' => $exam->title],
                    'revisionPlan' => null,
                    'generationPending' => true,
                    'message' => 'Revision plan is being prepared. Please check again later.',
                ]);
            }
        }

        return Inertia::render('Student/RevisionPlan/Show', [
            'attempt' => [
                'id' => $attempt->id,
                'score' => (float) $attempt->score,
                'total_marks' => (int) $attempt->total_marks,
            ],
            'exam' => [
                'title' => $exam->title,
                'course_name' => $exam->course_name,
            ],
            'revisionPlan' => $this->serializePlan($plan),
            'generationPending' => false,
            'message' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePlan(RevisionPlan $plan): array
    {
        return [
            'risk_level' => $plan->risk_level,
            'weak_topics' => $plan->weak_topics ?? [],
            'failed_questions' => $plan->failed_questions ?? [],
            'summary' => $plan->summary,
            'revision_notes' => $plan->revision_notes ?? [],
            'flashcards' => $plan->flashcards ?? [],
            'practice_questions' => $plan->practice_questions ?? [],
            'study_plan' => $plan->study_plan ?? [],
            'recommended_actions' => $plan->recommended_actions ?? [],
            'generated_at' => $plan->generated_at?->toIso8601String(),
        ];
    }

    /**
     * Per-question feedback and summaries — only when results are released.
     *
     * @return array<string, mixed>
     */
    private function buildStudentFeedbackPayload(Attempt $attempt, Exam $exam, ?RevisionPlan $revisionPlan): array
    {
        if (! $exam->results_released) {
            return [
                'released' => false,
                'flags' => [
                    'show_ai_feedback' => false,
                    'show_expected_answers' => false,
                    'show_revision_suggestions' => false,
                ],
                'score_summary' => null,
                'revision_plan_summary' => null,
                'questions' => [],
                'aggregated_missing_concepts' => [],
                'aggregated_detected_concepts' => [],
                'learning_themes' => [
                    'to_strengthen' => [],
                    'demonstrated' => [],
                ],
            ];
        }

        $showAi = (bool) $exam->show_student_ai_feedback;
        $showExpected = (bool) $exam->show_student_expected_answers;
        $showRevision = (bool) $exam->show_student_revision_suggestions;

        $raw = $attempt->raw_score !== null ? (float) $attempt->raw_score : null;
        $penalty = $attempt->proctoring_penalty_total !== null ? (float) $attempt->proctoring_penalty_total : null;
        $final = (float) ($attempt->score ?? 0);
        $totalMarks = (int) ($attempt->total_marks ?? 0);

        if ($raw === null && $penalty !== null) {
            $raw = $final - $penalty;
        }
        if ($raw === null) {
            $raw = $final;
        }

        $answers = $this->orderedAnswers($attempt);

        $questions = [];
        $missingBag = [];
        $detectedBag = [];

        foreach ($answers as $answer) {
            $q = $answer->question;
            if (! $q instanceof Question) {
                continue;
            }
            $row = $this->serializeStudentQuestionRow($answer, $q, $showAi, $showExpected);
            $questions[] = $row;

            if ($showAi) {
                foreach ($row['missing_concept_tags'] ?? [] as $c) {
                    $k = mb_strtolower(trim((string) $c), 'UTF-8');
                    if ($k !== '') {
                        $missingBag[$k] = trim((string) $c);
                    }
                }
                foreach ($row['detected_concepts'] ?? [] as $c) {
                    $k = mb_strtolower(trim((string) $c), 'UTF-8');
                    if ($k !== '') {
                        $detectedBag[$k] = trim((string) $c);
                    }
                }
            }
        }

        $revisionSummary = null;
        if ($showRevision && $revisionPlan instanceof RevisionPlan) {
            $revisionSummary = [
                'summary' => $revisionPlan->summary,
                'weak_topics' => $revisionPlan->weak_topics ?? [],
                'recommended_actions' => array_slice($revisionPlan->recommended_actions ?? [], 0, 12),
                'revision_notes' => array_slice($revisionPlan->revision_notes ?? [], 0, 15),
            ];
        }

        return [
            'released' => true,
            'flags' => [
                'show_ai_feedback' => $showAi,
                'show_expected_answers' => $showExpected,
                'show_revision_suggestions' => $showRevision,
            ],
            'score_summary' => [
                'raw_score' => round($raw, 2),
                'proctoring_penalty_total' => $penalty !== null ? round($penalty, 2) : 0.0,
                'final_score' => round($final, 2),
                'total_marks' => $totalMarks,
            ],
            'revision_plan_summary' => $revisionSummary,
            'questions' => $questions,
            'aggregated_missing_concepts' => array_values($missingBag),
            'aggregated_detected_concepts' => array_values($detectedBag),
            'learning_themes' => StudentFeedbackPresenter::aggregateLearningThemes(
                array_values($missingBag),
                array_values($detectedBag),
            ),
        ];
    }

    /**
     * @return Collection<int, Answer>
     */
    private function orderedAnswers(Attempt $attempt): Collection
    {
        /** @var Collection<int, Answer> $byId */
        $byId = $attempt->answers->keyBy('question_id');
        $order = is_array($attempt->question_order) ? $attempt->question_order : [];
        $out = collect();
        $seen = [];

        foreach ($order as $qid) {
            $id = (int) $qid;
            if ($id > 0 && $byId->has($id)) {
                $out->push($byId->get($id));
                $seen[$id] = true;
            }
        }

        foreach ($byId as $ans) {
            if (! isset($seen[(int) $ans->question_id])) {
                $out->push($ans);
            }
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeStudentQuestionRow(Answer $answer, Question $question, bool $showAi, bool $showExpected): array
    {
        $type = AnswerScoring::qType($question);
        $max = (float) ($question->marks ?? 0);
        $contribution = AnswerScoring::contribution($answer, $question);

        $base = [
            'question_id' => (int) $question->id,
            'question_text' => (string) $question->question_text,
            'q_type' => $type,
            'max_marks' => $max,
            'marks_counted' => round($contribution, 2),
            'marks_recorded' => $answer->marks_awarded !== null ? round((float) $answer->marks_awarded, 2) : null,
        ];

        if ($type === 'mcq') {
            $sel = $answer->selected_option ? strtoupper(substr((string) $answer->selected_option, 0, 1)) : null;
            $corr = $question->correct_option ? strtoupper(substr((string) $question->correct_option, 0, 1)) : null;
            $base += [
                'student_answer' => null,
                'selected_option' => $sel,
                'selected_option_text' => $this->mcqOptionText($question, $sel),
                'correct_option' => $showExpected ? $corr : null,
                'correct_option_text' => $showExpected ? $this->mcqOptionText($question, $corr) : null,
                'is_correct' => (bool) $answer->is_correct,
                'normalized_student_fill_in' => null,
                'accepted_fill_in' => null,
                'fill_in_match' => null,
                'expected_answer' => null,
                'file_upload_name' => null,
            ];
        } elseif ($type === 'fill_in') {
            $text = (string) ($answer->theory_answer ?? '');
            $expected = (string) ($question->correct_option ?? '');
            $base += [
                'student_answer' => $text !== '' ? $text : null,
                'selected_option' => null,
                'selected_option_text' => null,
                'correct_option' => null,
                'correct_option_text' => null,
                'is_correct' => (bool) $answer->is_correct,
                'normalized_student_fill_in' => AnswerTextNormalizer::normalize($text),
                'accepted_fill_in' => $showExpected ? ($expected !== '' ? $expected : null) : null,
                'fill_in_match' => AnswerTextNormalizer::fillInMatches($text, $expected ?: null),
                'expected_answer' => $showExpected
                    ? ($answer->expected_answer_snapshot ?? ($expected !== '' ? $expected : null))
                    : null,
                'file_upload_name' => null,
            ];
        } elseif ($type === 'file') {
            $path = $answer->file_upload;
            $base += [
                'student_answer' => null,
                'selected_option' => null,
                'selected_option_text' => null,
                'correct_option' => null,
                'correct_option_text' => null,
                'is_correct' => null,
                'normalized_student_fill_in' => null,
                'accepted_fill_in' => null,
                'fill_in_match' => null,
                'expected_answer' => $showExpected ? ($answer->expected_answer_snapshot ?: null) : null,
                'file_upload_name' => is_string($path) && $path !== '' ? basename($path) : null,
            ];
        } else {
            $text = (string) ($answer->theory_answer ?? '');
            $base += [
                'student_answer' => $text !== '' ? $text : null,
                'selected_option' => null,
                'selected_option_text' => null,
                'correct_option' => null,
                'correct_option_text' => null,
                'is_correct' => null,
                'normalized_student_fill_in' => null,
                'accepted_fill_in' => null,
                'fill_in_match' => null,
                'expected_answer' => $showExpected
                    ? ($answer->expected_answer_snapshot ?? ($question->correct_option !== null && $question->correct_option !== '' ? (string) $question->correct_option : null))
                    : null,
                'file_upload_name' => null,
            ];
        }

        $status = $this->studentDisplayStatus($type, $answer, $contribution, $max);

        $base['display_status'] = $status;

        if ($showAi) {
            $studentText = in_array($type, ['theory', 'fill_in'], true)
                ? (string) ($base['student_answer'] ?? '')
                : '';
            $detected = StudentFeedbackPresenter::uniqueConcepts($answer->detected_concepts ?? []);
            $missing = StudentFeedbackPresenter::uniqueConcepts($answer->missing_concepts ?? []);
            $rubric = StudentFeedbackPresenter::uniqueConcepts($answer->rubric_concepts ?? []);
            $marksRecorded = $answer->marks_awarded !== null ? (float) $answer->marks_awarded : $contribution;
            $mergedFeedback = StudentFeedbackPresenter::mergeFeedbackTexts(
                $answer->evaluation_reason,
                $answer->ai_evaluation,
            );
            $conceptThemes = StudentFeedbackPresenter::groupConceptsIntoThemes($missing);

            $base += [
                'evaluation_status' => $answer->evaluation_status,
                'understanding_level' => StudentFeedbackPresenter::understandingLevel(
                    $answer->evaluation_status,
                    $studentText,
                    $detected,
                    $missing,
                    $answer->ai_concept_coverage !== null ? (float) $answer->ai_concept_coverage : null,
                    $marksRecorded,
                    $max,
                ),
                'concise_feedback' => $mergedFeedback,
                'missing_concept_tags' => $missing,
                'concept_themes' => $conceptThemes,
                'revision_focus' => StudentFeedbackPresenter::revisionFocus($missing, $conceptThemes, $mergedFeedback),
                'ai_confidence' => $answer->ai_confidence !== null ? round((float) $answer->ai_confidence, 4) : null,
                'ai_concept_coverage' =>
                    $answer->ai_concept_coverage !== null
                        ? round((float) $answer->ai_concept_coverage, 4)
                        : null,
                'detected_concepts' => $detected,
                'rubric_concepts' => $rubric,
            ];
        } else {
            $base += [
                'evaluation_status' => null,
                'understanding_level' => null,
                'concise_feedback' => null,
                'missing_concept_tags' => [],
                'concept_themes' => [],
                'revision_focus' => null,
                'ai_confidence' => null,
                'ai_concept_coverage' => null,
                'detected_concepts' => [],
                'rubric_concepts' => [],
            ];
        }

        return $base;
    }

    private function studentDisplayStatus(string $type, Answer $answer, float $contribution, float $max): string
    {
        if ($max <= 0) {
            return 'ungraded';
        }
        if ($type === 'mcq') {
            return $answer->is_correct ? 'correct' : 'incorrect';
        }
        if ($contribution >= $max - 0.001) {
            return 'correct';
        }
        if ($contribution > 0) {
            return 'partial';
        }

        return 'incorrect';
    }

    private function mcqOptionText(Question $question, ?string $letter): ?string
    {
        if ($letter === null || $letter === '') {
            return null;
        }
        $map = [
            'A' => 'option_a',
            'B' => 'option_b',
            'C' => 'option_c',
            'D' => 'option_d',
            'E' => 'option_e',
        ];
        $L = strtoupper(substr($letter, 0, 1));
        $prop = $map[$L] ?? null;
        if ($prop === null) {
            return null;
        }
        $v = $question->{$prop} ?? null;

        return $v !== null && (string) $v !== '' ? (string) $v : null;
    }
}
