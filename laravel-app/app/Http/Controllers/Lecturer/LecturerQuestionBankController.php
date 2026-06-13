<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\AiGenerationRequest;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\AiQuestionGenerationService;
use App\Services\QuestionBankService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LecturerQuestionBankController extends Controller
{
    public function __construct(
        private readonly AiQuestionGenerationService $aiService,
        private readonly QuestionBankService $questionBankService
    ) {}

    public function generateForm(): Response
    {
        $lecturer = $this->lecturer();

        $courses = Exam::query()
            ->where('user_id', $lecturer->id)
            ->select('id', 'course_name', 'course_code')
            ->distinct()
            ->orderBy('course_name')
            ->get();

        return Inertia::render('Lecturer/QuestionBank/AiGenerate', [
            'courses' => $courses,
            'requireHodApprovalForAiQuestions' => $lecturer->requiresHodAiQuestionReview(),
        ]);
    }

    public function generateStore(Request $request): RedirectResponse
    {
        $lecturer = $this->lecturer();
        $validated = $request->validate([
            'course_id' => 'nullable|integer',
            'topic' => 'nullable|string|max:150',
            'lecture_content' => 'nullable|string|max:500000',
            'input_file' => 'nullable|file|mimes:txt,pdf,doc,docx,ppt,pptx|max:8192',
            'number_of_questions' => 'required|integer|min:1|max:100',
            'question_types' => 'required|array|min:1',
            'question_types.*' => 'required|in:mcq,fill_in,short_answer,theory',
            'difficulty' => 'required|in:easy,medium,hard',
            'total_marks' => 'required|integer|min:1|max:500',
            'learning_outcome' => 'nullable|string|max:2000',
        ], [
            'lecture_content.max' => 'Lecture content is too long. Maximum allowed length is 500,000 characters.',
            'input_file.max' => 'Uploaded file is too large. Maximum allowed file size is 8 MB.',
            'input_file.mimes' => 'Unsupported file type. Allowed: txt, pdf, doc, docx, ppt, pptx.',
        ]);

        $sourceContent = trim((string) ($validated['lecture_content'] ?? ''));
        $sourceFilePath = null;
        $inputType = 'text';

        if ($request->hasFile('input_file')) {
            $file = $request->file('input_file');
            $sourceFilePath = $file->store('ai-lecture-sources');
            $inputType = strtolower((string) $file->getClientOriginalExtension());

            if (strtolower((string) $file->getClientOriginalExtension()) === 'txt') {
                $sourceContent = trim((string) file_get_contents($file->getRealPath()));
            }
        }

        if ($sourceContent === '') {
            return back()
                ->withErrors(['lecture_content' => 'Provide lecture text (or upload a text file) before generating questions.'])
                ->withInput();
        }

        $sourceContent = $this->aiService->sanitizeLectureContent($sourceContent);
        $promptInjectionDetected = $this->aiService->detectPromptInjection($sourceContent);

        $courseLabel = 'General course';
        if (! empty($validated['course_id'])) {
            $exam = Exam::query()
                ->where('id', (int) $validated['course_id'])
                ->where('user_id', $lecturer->id)
                ->first();
            if ($exam !== null) {
                $courseLabel = trim($exam->course_name.(($exam->course_code ?? '') !== '' ? ' ('.$exam->course_code.')' : ''));
            }
        }

        $generationRequest = AiGenerationRequest::create([
            'lecturer_id' => $lecturer->id,
            'course_id' => $validated['course_id'] ?? null,
            'topic' => $validated['topic'] ?? null,
            'input_type' => $inputType,
            'source_file_path' => $sourceFilePath,
            'source_content' => $sourceContent,
            'requested_question_count' => (int) $validated['number_of_questions'],
            'question_types' => $validated['question_types'],
            'difficulty' => $validated['difficulty'],
            'total_marks' => (int) $validated['total_marks'],
            'learning_outcome' => $validated['learning_outcome'] ?? null,
            'prompt_injection_detected' => $promptInjectionDetected,
            'status' => 'processing',
            'request_payload' => [
                'question_types' => $validated['question_types'],
                'difficulty' => $validated['difficulty'],
                'total_marks' => (int) $validated['total_marks'],
            ],
        ]);

        $syncLimit = max(60, (int) config('services.ai.sync_max_execution', 600));
        if (function_exists('set_time_limit')) {
            @set_time_limit($syncLimit);
        }

        $generationResult = $this->aiService->generateQuestionsWithMetadata(
            $sourceContent,
            (int) $validated['number_of_questions'],
            (array) $validated['question_types'],
            (string) $validated['difficulty'],
            (int) $validated['total_marks'],
            $validated['topic'] ?? null,
            $courseLabel,
            $validated['learning_outcome'] ?? null,
        );
        $generatedQuestions = $generationResult['questions'];

        if ($generatedQuestions === []) {
            $generationRequest->update([
                'generated_count' => 0,
                'status' => 'failed',
                'ai_provider' => $generationResult['provider'] ?? 'unknown',
                'ai_model' => $generationResult['model'] ?? 'n/a',
                'response_payload' => [
                    'created_question_count' => 0,
                    'used_fallback' => (bool) ($generationResult['used_fallback'] ?? false),
                ],
                'error_message' => $generationResult['error'] ?? 'Cannot use AI now. Please try again shortly.',
            ]);

            return back()
                ->withErrors([
                    'ai' => $generationResult['error'] ?? 'Cannot use AI now. Please try again shortly.',
                ])
                ->withInput();
        }

        foreach ($generatedQuestions as $item) {
            $this->questionBankService->createDraftQuestion($lecturer, [
                'course_id' => $validated['course_id'] ?? null,
                'topic' => $validated['topic'] ?? null,
                'question_text' => $item['question_text'],
                'question_type' => $item['question_type'],
                'difficulty' => $item['difficulty'],
                'correct_answer' => $item['correct_answer'],
                'model_answer' => $item['model_answer'],
                'marking_scheme' => $item['marking_scheme'],
                'suggested_marks' => $item['suggested_marks'],
                'generated_by_ai' => true,
                'ai_confidence' => $item['ai_confidence'],
                'source_reference' => $item['source_reference'],
                'status' => 'draft',
                'visibility' => 'personal',
                'options' => $item['options'],
            ], $generationRequest);
        }

        $generationRequest->update([
            'generated_count' => count($generatedQuestions),
            'status' => 'completed',
            'ai_provider' => $generationResult['provider'] ?? 'unknown',
            'ai_model' => $generationResult['model'] ?? 'unknown',
            'response_payload' => [
                'created_question_count' => count($generatedQuestions),
                'used_fallback' => (bool) ($generationResult['used_fallback'] ?? false),
            ],
            'error_message' => $generationResult['error'] ?? null,
        ]);

        $this->questionBankService->audit('ai_generation_request_created', null, $lecturer, [
            'request_id' => $generationRequest->id,
            'generated_count' => count($generatedQuestions),
            'prompt_injection_detected' => $promptInjectionDetected,
        ], $request);

        $reviewHint = $lecturer->requiresHodAiQuestionReview()
            ? 'Review your drafts, then submit the batch for head-of-department approval before they appear in your bank.'
            : 'Review and approve before use.';

        return redirect()
            ->route('lecturer.question-bank.review', $generationRequest->id)
            ->with(
                'success',
                'AI draft questions generated. Provider: '.strtoupper((string) ($generationResult['provider'] ?? 'unknown')).'. '.$reviewHint,
            );
    }

    public function reviewRequest(int $requestId): Response
    {
        $lecturer = $this->lecturer();
        $generationRequest = AiGenerationRequest::query()
            ->where('id', $requestId)
            ->where('lecturer_id', $lecturer->id)
            ->firstOrFail();

        $questions = QuestionBank::query()
            ->where('ai_generation_request_id', $generationRequest->id)
            ->where('lecturer_id', $lecturer->id)
            ->with('options')
            ->orderBy('id')
            ->get();

        $approvableCount = QuestionBank::query()
            ->where('ai_generation_request_id', $generationRequest->id)
            ->where('lecturer_id', $lecturer->id)
            ->whereIn('status', ['draft', 'rejected'])
            ->count();

        $submittableForHodCount = QuestionBank::query()
            ->where('ai_generation_request_id', $generationRequest->id)
            ->where('lecturer_id', $lecturer->id)
            ->where('generated_by_ai', true)
            ->where('submitted_for_admin_ai_review', false)
            ->whereIn('status', ['draft', 'rejected'])
            ->count();

        $awaitingHodCount = QuestionBank::query()
            ->where('ai_generation_request_id', $generationRequest->id)
            ->where('lecturer_id', $lecturer->id)
            ->where('generated_by_ai', true)
            ->where('submitted_for_admin_ai_review', true)
            ->where('status', 'draft')
            ->count();

        return Inertia::render('Lecturer/QuestionBank/AiReview', [
            'generationRequest' => $generationRequest,
            'questions' => $questions,
            'approvableCount' => $approvableCount,
            'requireHodApprovalForAiQuestions' => $lecturer->requiresHodAiQuestionReview(),
            'submittableForHodCount' => $submittableForHodCount,
            'awaitingHodCount' => $awaitingHodCount,
        ]);
    }

    public function approveAllInAiRequest(Request $request, int $id): RedirectResponse
    {
        $lecturer = $this->lecturer();
        $generationRequest = AiGenerationRequest::query()
            ->where('id', $id)
            ->where('lecturer_id', $lecturer->id)
            ->firstOrFail();

        $toApprove = QuestionBank::query()
            ->where('ai_generation_request_id', $generationRequest->id)
            ->where('lecturer_id', $lecturer->id)
            ->whereIn('status', ['draft', 'rejected'])
            ->orderBy('id')
            ->get();

        if ($lecturer->requiresHodAiQuestionReview() && $toApprove->where('generated_by_ai', true)->isNotEmpty()) {
            return back()->withErrors([
                'approve' => 'Your department requires a head of department to approve AI-generated questions. Use “Submit for HoD review” instead of approving them yourself.',
            ]);
        }

        if ($toApprove->isEmpty()) {
            return back()->with('success', 'Nothing to approve: all questions in this run are already approved or are not in draft/rejected state.');
        }

        DB::transaction(function () use ($toApprove, $lecturer, $request): void {
            foreach ($toApprove as $question) {
                $this->questionBankService->approveQuestion($question, $lecturer);
                $this->questionBankService->audit('question_approved_web', $question->fresh(), $lecturer, [], $request);
            }
        });

        $n = $toApprove->count();

        return back()->with('success', "Approved {$n} question(s) from this AI generation request.");
    }

    public function submitAiBatchForHodReview(Request $request, int $id): RedirectResponse
    {
        $lecturer = $this->lecturer();
        $generationRequest = AiGenerationRequest::query()
            ->where('id', $id)
            ->where('lecturer_id', $lecturer->id)
            ->firstOrFail();

        $batch = QuestionBank::query()
            ->where('ai_generation_request_id', $generationRequest->id)
            ->where('lecturer_id', $lecturer->id)
            ->where('generated_by_ai', true)
            ->whereIn('status', ['draft', 'rejected'])
            ->orderBy('id')
            ->get();

        try {
            $count = $this->questionBankService->submitAiQuestionsForHodReview($lecturer, $batch);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        if ($count === 0) {
            return back()->with(
                'error',
                'Nothing to submit: add or fix AI drafts first, or questions may already be with your HoD.',
            );
        }

        $this->questionBankService->audit('ai_question_batch_submitted_for_hod_review', null, $lecturer, [
            'request_id' => $generationRequest->id,
            'submitted_count' => $count,
        ], $request);

        return back()->with(
            'success',
            "Submitted {$count} AI question(s) for head-of-department review. You will see them in your bank once your HoD approves them.",
        );
    }

    public function index(Request $request): Response
    {
        $lecturer = $this->lecturer();
        $query = QuestionBank::query()
            ->visibleToLecturer($lecturer)
            ->with('options', 'usageStats')
            ->orderByDesc('updated_at');

        if ($request->filled('search')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $request->input('search'))).'%';
            $query->where('question_text', 'like', $term);
        }
        if ($request->filled('course_id')) {
            $query->where('course_id', (int) $request->input('course_id'));
        }
        if ($request->filled('topic')) {
            $query->where('topic', trim((string) $request->input('topic')));
        }
        if ($request->filled('question_type')) {
            $query->where('question_type', (string) $request->input('question_type'));
        }
        if ($request->filled('difficulty')) {
            $query->where('difficulty', (string) $request->input('difficulty'));
        }
        if ($request->filled('source')) {
            if ($request->input('source') === 'ai') {
                $query->where('generated_by_ai', true);
            } elseif ($request->input('source') === 'manual') {
                $query->where('generated_by_ai', false);
            }
        }

        $questions = $query->paginate(20)->withQueryString();
        $courses = Exam::query()
            ->where('user_id', $lecturer->id)
            ->select('id', 'course_name', 'course_code')
            ->distinct()
            ->orderBy('course_name')
            ->get();
        $topics = QuestionBank::query()
            ->where('lecturer_id', $lecturer->id)
            ->whereNotNull('topic')
            ->distinct()
            ->pluck('topic');
        $deletableBankCount = QuestionBank::query()
            ->where('lecturer_id', $lecturer->id)
            ->where('status', '!=', 'official')
            ->count();

        return Inertia::render('Lecturer/QuestionBank/Index', [
            'questions' => $questions,
            'courses' => $courses,
            'topics' => $topics,
            'deletableBankCount' => $deletableBankCount,
            'requireHodApprovalForAiQuestions' => $lecturer->requiresHodAiQuestionReview(),
            'filters' => $request->only([
                'search', 'course_id', 'topic', 'question_type', 'difficulty', 'source',
            ]),
        ]);
    }

    public function destroyAllOwned(Request $request): RedirectResponse
    {
        $lecturer = $this->lecturer();
        $request->validate([
            'confirm_delete_all' => 'required|accepted',
        ], [
            'confirm_delete_all.accepted' => 'You must confirm that you understand this action is permanent.',
        ]);

        $ids = QuestionBank::query()
            ->where('lecturer_id', $lecturer->id)
            ->where('status', '!=', 'official')
            ->pluck('id');
        $count = $ids->count();

        if ($count === 0) {
            return redirect()->route('lecturer.question-bank.index')
                ->with('success', 'There were no personal questions to delete. Official bank items are never removed here.');
        }

        DB::transaction(function () use ($ids, $lecturer, $request, $count): void {
            Question::query()->whereIn('question_bank_id', $ids)->update(['question_bank_id' => null]);
            QuestionBank::query()->whereIn('id', $ids)->delete();
            $this->questionBankService->audit('question_bank_bulk_deleted', null, $lecturer, [
                'deleted_count' => $count,
            ], $request);
        });

        return redirect()->route('lecturer.question-bank.index')
            ->with('success', "Permanently deleted {$count} question(s) from your personal bank. Official department questions were not removed.");
    }

    public function destroyBulk(Request $request): RedirectResponse
    {
        $lecturer = $this->lecturer();
        $validated = $request->validate([
            'ids' => 'required|array|min:1|max:200',
            'ids.*' => 'integer|distinct',
            'confirm_bulk_delete' => 'required|accepted',
        ], [
            'ids.required' => 'Select at least one question to delete.',
            'confirm_bulk_delete.accepted' => 'Confirm that you understand this action is permanent.',
        ]);

        $requested = collect($validated['ids'])->unique()->values();
        $deletableIds = QuestionBank::query()
            ->whereIn('id', $requested)
            ->where('lecturer_id', $lecturer->id)
            ->where('status', '!=', 'official')
            ->pluck('id');

        if ($deletableIds->isEmpty()) {
            return back()->withErrors([
                'ids' => 'None of the selected questions can be deleted. You can only remove your own items that are not in the official bank.',
            ]);
        }

        $skipped = $requested->count() - $deletableIds->count();

        DB::transaction(function () use ($deletableIds, $lecturer, $request): void {
            Question::query()->whereIn('question_bank_id', $deletableIds)->update(['question_bank_id' => null]);
            QuestionBank::query()->whereIn('id', $deletableIds)->delete();
            $this->questionBankService->audit('question_bank_bulk_deleted', null, $lecturer, [
                'deleted_count' => $deletableIds->count(),
                'mode' => 'selected_ids',
            ], $request);
        });

        $message = "Permanently deleted {$deletableIds->count()} question(s) from your bank.";
        if ($skipped > 0) {
            $message .= " {$skipped} selected item(s) were skipped (official bank items or not owned by you).";
        }

        return redirect()->route('lecturer.question-bank.index')->with('success', $message);
    }

    public function generateQuizForm(): Response
    {
        $lecturer = $this->lecturer();
        $courses = Exam::query()
            ->where('user_id', $lecturer->id)
            ->select('id', 'course_name', 'course_code')
            ->distinct()
            ->orderBy('course_name')
            ->get();
        $topics = QuestionBank::query()
            ->where('lecturer_id', $lecturer->id)
            ->whereNotNull('topic')
            ->distinct()
            ->pluck('topic');

        return Inertia::render('Lecturer/QuestionBank/GenerateQuiz', [
            'courses' => $courses,
            'topics' => $topics,
        ]);
    }

    public function generateQuizStore(Request $request): RedirectResponse
    {
        $lecturer = $this->lecturer();
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'course_id' => 'nullable|integer',
            'course_label' => 'required|string|max:100',
            'topics' => 'nullable|array',
            'topics.*' => 'nullable|string|max:150',
            'number_of_questions' => 'required|integer|min:1|max:100',
            'difficulty_mix' => 'nullable|array',
            'question_type_mix' => 'nullable|array',
            'total_marks' => 'required|integer|min:1|max:500',
            'time_limit' => 'required|integer|min:5|max:360',
        ]);

        try {
            $result = $this->questionBankService->generateQuizFromBank($validated, $lecturer);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['number_of_questions' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('lecturer.exams.show', $result['exam']->id)
            ->with('success', 'Quiz draft generated from question bank. Review and publish when ready.');
    }

    public function analytics(): Response
    {
        $lecturer = $this->lecturer();
        $questions = QuestionBank::query()
            ->where('lecturer_id', $lecturer->id)
            ->with('usageStats')
            ->whereNotIn('status', ['archived'])
            ->get();

        foreach ($questions as $question) {
            $this->questionBankService->refreshQuestionUsageStats($question->id);
        }
        $questions->load('usageStats');

        return Inertia::render('Lecturer/QuestionBank/Analytics', [
            'questions' => $questions,
        ]);
    }

    public function updateQuestion(Request $request, int $id): RedirectResponse
    {
        $lecturer = $this->lecturer();
        $question = QuestionBank::query()
            ->where('id', $id)
            ->where(function ($q) use ($lecturer): void {
                $q->where('lecturer_id', $lecturer->id)
                    ->orWhere('status', 'official');
            })
            ->with('options')
            ->firstOrFail();

        $data = $request->validate([
            'course_id' => 'nullable|integer',
            'topic' => 'nullable|string|max:150',
            'question_text' => 'required|string|max:65000',
            'question_type' => 'required|in:mcq,fill_in,short_answer,theory',
            'difficulty' => 'required|in:easy,medium,hard',
            'correct_answer' => 'nullable|string|max:65000',
            'model_answer' => 'nullable|string|max:65000',
            'marking_scheme' => 'nullable|string|max:65000',
            'suggested_marks' => 'required|integer|min:1|max:100',
            'options' => 'nullable|array',
            'options.*.key' => 'required_with:options|string|size:1',
            'options.*.text' => 'required_with:options|string|max:65000',
            'options.*.is_correct' => 'nullable|boolean',
        ]);

        $updated = $this->questionBankService->updateQuestion($question, $lecturer, $data);
        $this->questionBankService->audit('question_edited_web', $updated, $lecturer, [], $request);

        return back()->with('success', 'Question updated successfully.');
    }

    public function approveQuestion(Request $request, int $id): RedirectResponse
    {
        $lecturer = $this->lecturer();
        $question = QuestionBank::query()->where('id', $id)->where('lecturer_id', $lecturer->id)->firstOrFail();
        $this->questionBankService->approveQuestion($question, $lecturer);
        $this->questionBankService->audit('question_approved_web', $question, $lecturer, [], $request);

        return back()->with('success', 'Question approved.');
    }

    public function rejectQuestion(Request $request, int $id): RedirectResponse
    {
        $lecturer = $this->lecturer();
        $data = $request->validate(['reason' => 'nullable|string|max:2000']);
        $question = QuestionBank::query()->where('id', $id)->where('lecturer_id', $lecturer->id)->firstOrFail();
        $this->questionBankService->rejectQuestion($question, $lecturer, $data['reason'] ?? null);
        $this->questionBankService->audit('question_rejected_web', $question, $lecturer, $data, $request);

        return back()->with('success', 'Question rejected.');
    }

    public function archiveQuestion(Request $request, int $id): RedirectResponse
    {
        $lecturer = $this->lecturer();
        $question = QuestionBank::query()->where('id', $id)->where('lecturer_id', $lecturer->id)->firstOrFail();
        $this->questionBankService->archiveQuestion($question, $lecturer);
        $this->questionBankService->audit('question_archived_web', $question, $lecturer, [], $request);

        return back()->with('success', 'Question archived.');
    }

    public function submitOfficial(Request $request, int $id): RedirectResponse
    {
        $lecturer = $this->lecturer();
        $question = QuestionBank::query()->where('id', $id)->where('lecturer_id', $lecturer->id)->firstOrFail();
        $this->questionBankService->submitForOfficialReview($question, $lecturer);
        $this->questionBankService->audit('question_submitted_official_web', $question, $lecturer, [], $request);

        return back()->with('success', 'Question submitted for official department review.');
    }

    public function duplicateQuestion(Request $request, int $id): RedirectResponse
    {
        $lecturer = $this->lecturer();
        $question = QuestionBank::query()->where('id', $id)->with('options')->firstOrFail();
        if ($question->lecturer_id !== $lecturer->id && $question->status !== 'official') {
            abort(403, 'Access denied.');
        }

        $payload = [
            'course_id' => $question->course_id,
            'topic' => $question->topic,
            'question_text' => $question->question_text,
            'question_type' => $question->question_type,
            'difficulty' => $question->difficulty,
            'correct_answer' => $question->correct_answer,
            'model_answer' => $question->model_answer,
            'marking_scheme' => $question->marking_scheme,
            'suggested_marks' => $question->suggested_marks,
            'generated_by_ai' => $question->generated_by_ai,
            'ai_confidence' => $question->ai_confidence,
            'source_reference' => $question->source_reference,
            'status' => 'draft',
            'visibility' => 'personal',
            'options' => $question->options->map(fn ($opt): array => [
                'key' => $opt->option_key,
                'text' => $opt->option_text,
                'is_correct' => (bool) $opt->is_correct,
            ])->all(),
        ];

        $copy = $this->questionBankService->createDraftQuestion($lecturer, $payload);
        $copy->update([
            'parent_question_id' => $question->id,
            'version' => ((int) $question->version) + 1,
        ]);
        $this->questionBankService->audit('question_duplicated_web', $copy, $lecturer, ['source_question_id' => $question->id], $request);

        return back()->with('success', 'Question duplicated into your personal draft bank.');
    }

    private function lecturer(): User
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        if ($user->isAdmin() || $user->isApprovedLecturer() || $user->isApprovedHod()) {
            return $user;
        }

        abort(403, 'Access denied. Approved lecturers, heads of department, or administrators only.');
    }
}
