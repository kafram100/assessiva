<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Models\User;
use App\Models\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionBankCatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isAdmin()) {
            abort(403, 'Access denied. Admin only.');
        }

        $base = $this->scopedBankQuery($user);

        $topicOptions = (clone $base)
            ->whereNotNull('topic')
            ->whereRaw("TRIM(topic) <> ''")
            ->select('topic')
            ->distinct()
            ->orderBy('topic')
            ->pluck('topic')
            ->map(fn (?string $t): string => trim((string) $t))
            ->unique()
            ->values()
            ->all();

        $query = (clone $base)
            ->with([
                'lecturer:id,name,username',
                'department:id,name',
                'course:id,code,name',
                'options',
            ]);

        if ($request->filled('search')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $request->input('search'))).'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('question_text', 'like', $term)
                    ->orWhere('topic', 'like', $term)
                    ->orWhereHas('course', function (Builder $cq) use ($term) {
                        $cq->where('name', 'like', $term)
                           ->orWhere('code', 'like', $term);
                    });
            });
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->input('course_id'));
        }

        if ($request->filled('is_active')) {
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_active', $isActive);
        }

        if ($request->filled('question_type')) {
            $query->where('question_type', (string) $request->input('question_type'));
        }

        if (!$request->filled('search') && !$request->filled('course_id')) {
            $query->whereRaw('1 = 0');
        }

        $query->select('question_bank.*')
            ->selectRaw('ROW_NUMBER() OVER(PARTITION BY course_id ORDER BY id ASC) as question_number')
            ->orderByRaw('(SELECT name FROM courses WHERE courses.id = question_bank.course_id)')
            ->orderByDesc('updated_at');

        $questions = $query
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Admin/QuestionBankCatalog/Index', [
            'questions' => $questions,
            'topicOptions' => $topicOptions,
            'filters' => $request->only(['search', 'course_id', 'is_active', 'question_type']),
            'scope' => 'admin',
            'courses' => \App\Models\Course::query()->orderBy('code')->get(['id', 'code', 'name']),
        ]);
    }

    /**
     * @return Builder<QuestionBank>
     */
    private function scopedBankQuery(User $user): Builder
    {
        $query = QuestionBank::query();



        return $query;
    }

    public function downloadTemplate()
    {
        $csvHeader = ['Question Type', 'Question Text', 'Option A', 'Option B', 'Option C', 'Option D', 'Option E', 'Correct Answer', 'Suggested Marks'];
        $exampleRow1 = ['mcq', 'Which service is used for email?', 'Gmail', 'Excel', 'Paint', 'Calculator', '', 'A', '1'];
        $exampleRow2 = ['theory', 'Explain the process of photosynthesis.', '', '', '', '', '', 'Plants convert sunlight into energy...', '5'];

        $callback = function () use ($csvHeader, $exampleRow1, $exampleRow2) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $csvHeader);
            fputcsv($file, $exampleRow1);
            fputcsv($file, $exampleRow2);
            fclose($file);
        };

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="question_import_template.csv"',
        ];

        return response()->stream($callback, 200, $headers);
    }

    public function importForm(Request $request): Response
    {
        $courses = \App\Models\Course::query()
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/QuestionBankCatalog/Import', [
            'courses' => $courses,
        ]);
    }

    public function importStore(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'course_name' => 'required|string|max:255',
            'level' => ['required', 'string', 'in:JHS 1,JHS 2,JHS 3,SHS 1,SHS 2,SHS 3,Level 100,Level 200,Level 300,Level 400,Graduate,Year 1,Year 2,Year 3,Year 4'],
            'topic' => 'nullable|string|max:255',
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $fileContent = file_get_contents($file->getRealPath());
        $delimiter = ',';
        if (substr_count($fileContent, "\t") > substr_count($fileContent, ',')) {
            $delimiter = "\t";
        } elseif (substr_count($fileContent, ';') > substr_count($fileContent, ',')) {
            $delimiter = ';';
        }

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return back()->withErrors(['csv_file' => 'Could not read the uploaded file.'])->withInput();
        }

        fgetcsv($handle, 0, $delimiter);
        $imported = 0;
        $skipped = 0;
        $user = $request->user();

        $courseName = trim((string) $request->input('course_name'));
        $course = \App\Models\Course::firstOrCreate(
            ['name' => $courseName],
            [
                'code' => strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $courseName), 0, 3)) . rand(100, 999),
                'user_id' => $user->id,
                'department_id' => $user->department_id,
            ]
        );

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) < 3) {
                \Illuminate\Support\Facades\Log::warning('CSV Import Skipped: Row has less than 3 columns', ['row' => $row]);
                continue;
            }
            [
                $qType,
                $questionText,
                $optA,
                $optB,
                $optC,
                $optD,
                $optE,
                $correctAns,
                $marks,
            ] = array_pad($row, 9, null);

            $qType = strtolower(trim((string) ($qType ?? 'mcq')));
            $qType = str_replace([' ', '-'], '_', $qType);
            if (in_array($qType, ['multiple_choice', 'multiple_choices', 'objective'], true)) {
                $qType = 'mcq';
            }
            if (! in_array($qType, \App\Enums\QuestionType::values(), true)) {
                \Illuminate\Support\Facades\Log::warning('CSV Import Skipped: Invalid question type', ['qType' => $qType, 'row' => $row]);
                continue;
            }
            if (trim((string) ($questionText ?? '')) === '') {
                \Illuminate\Support\Facades\Log::warning('CSV Import Skipped: Empty question text', ['row' => $row]);
                continue;
            }

            $normalizedCorrect = trim((string) ($correctAns ?? ''));
            if ($qType === \App\Enums\QuestionType::Mcq->value) {
                $normalizedCorrect = strtoupper(substr($normalizedCorrect, 0, 1));
            }
            $normalizedCorrect = $normalizedCorrect !== '' ? $normalizedCorrect : null;

            // Deduplication check
            $existing = QuestionBank::where('course_id', $course->id)
                ->where('question_text', trim((string) $questionText))
                ->first();

            if ($existing) {
                \Illuminate\Support\Facades\Log::info('CSV Import Skipped: Duplicate question', ['question' => $questionText]);
                $skipped++;
                continue;
            }

            $question = QuestionBank::create([
                'course_id' => $course->id,
                'level' => $request->input('level'),
                'topic' => $request->filled('topic') ? trim((string) $request->input('topic')) : null,
                'lecturer_id' => $user->id,
                'department_id' => $user->department_id,
                'question_text' => trim((string) $questionText),
                'question_type' => $qType,
                'is_active' => true,
                'difficulty' => 'medium',
                'created_by' => $user->id,
                'correct_answer' => $normalizedCorrect,
                'suggested_marks' => max(1, (int) ($marks ?? 1)),
            ]);

            if ($qType === \App\Enums\QuestionType::Mcq->value) {
                $options = [
                    'A' => $optA,
                    'B' => $optB,
                    'C' => $optC,
                    'D' => $optD,
                    'E' => $optE,
                ];
                foreach ($options as $key => $val) {
                    $valTrimmed = trim((string) ($val ?? ''));
                    if ($valTrimmed !== '') {
                        \App\Models\QuestionOption::create([
                            'question_bank_id' => $question->id,
                            'option_key' => $key,
                            'option_text' => $valTrimmed,
                            'is_correct' => $key === $normalizedCorrect,
                        ]);
                    }
                }
            }

            $imported++;
        }

        fclose($handle);

        $msg = "{$imported} question(s) imported to the Question Bank successfully.";
        if ($skipped > 0) {
            $msg .= " {$skipped} duplicate(s) were skipped.";
        }

        return redirect()->route('admin.question-bank-catalog.index')
            ->with('success', $msg);
    }

    public function aiGenerateForm(Request $request): Response
    {
        $courses = \App\Models\Course::query()
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/QuestionBankCatalog/AiGenerate', [
            'courses' => $courses,
        ]);
    }

    public function aiGenerateStore(Request $request, \App\Services\AiQuestionGenerationService $aiService): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'course_name' => 'required|string|max:150',
            'educational_level' => 'nullable|in:JHS,SHS,Tertiary',
            'level' => ['required', 'string', 'in:JHS 1,JHS 2,JHS 3,SHS 1,SHS 2,SHS 3,Level 100,Level 200,Level 300,Level 400,Graduate,Year 1,Year 2,Year 3,Year 4'],
            'topic' => 'nullable|string|max:150',
            'lecture_content' => 'nullable|string|max:500000',
            'input_file' => 'nullable|file|mimes:txt,pdf,doc,docx,ppt,pptx|max:8192',
            'question_count' => 'required|integer|min:1|max:50',
            'question_types' => 'required|array|min:1',
            'question_types.*' => 'required|in:mcq,theory,fill_in,true_false,short_answer',
        ]);

        $sourceContent = trim((string) ($request->input('lecture_content') ?? ''));
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

        $sourceContent = $aiService->sanitizeLectureContent($sourceContent);
        $user = $request->user();

        $courseName = trim((string) $request->input('course_name'));
        $course = \App\Models\Course::firstOrCreate(
            ['name' => $courseName],
            [
                'code' => strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $courseName), 0, 3)) . rand(100, 999),
                'user_id' => $user->id,
                'department_id' => $user->department_id,
            ]
        );

        $aiRequest = \App\Models\AiGenerationRequest::create([
            'lecturer_id' => $user->id,
            'source_text' => mb_substr($sourceContent, 0, 50000),
            'input_type' => $inputType,
            'source_file_path' => $sourceFilePath,
            'source_content' => $sourceContent,
            'requested_question_count' => (int) $request->input('question_count'),
            'question_types' => $request->input('question_types'),
            'difficulty' => 'mixed',
            'total_marks' => (int) $request->input('question_count'),
            'parameters' => array_merge($request->only(['topic', 'level']), ['course_id' => $course->id]),
            'status' => 'pending',
        ]);

        try {
            $generationResult = $aiService->generateQuestionsWithMetadata(
                $sourceContent,
                (int) $request->input('question_count'),
                (array) $request->input('question_types'),
                'mixed',
                (int) $request->input('question_count'),
                $request->input('topic') ?? null,
                'General Knowledge',
                null
            );

            if (!empty($generationResult['error'])) {
                $aiRequest->update([
                    'status' => 'failed',
                    'error_message' => $generationResult['error'],
                ]);
                return back()->withErrors(['ai' => $generationResult['error']]);
            }

            $questions = $generationResult['questions'];

            $aiRequest->update([
                'status' => 'completed',
                'result_payload' => $questions,
                'completed_at' => now(),
            ]);

            $generatedCount = 0;
            $skippedCount = 0;

            foreach ($questions as $q) {
                $qText = trim((string) $q['question_text']);

                // Deduplication check
                $existing = QuestionBank::where('course_id', $course->id)
                    ->where('question_text', $qText)
                    ->first();

                if ($existing) {
                    $skippedCount++;
                    continue;
                }

                $questionBank = QuestionBank::create([
                    'course_id' => $course->id,
                    'level' => $request->input('level'),
                    'topic' => $request->input('topic') ?: ($q['topic'] ?? null),
                    'lecturer_id' => $user->id,
                    'department_id' => $user->department_id,
                    'question_text' => $q['question_text'],
                    'question_type' => $q['question_type'] ?? 'mcq',
                    'difficulty' => $q['difficulty'] ?? 'medium',
                    'correct_answer' => $q['correct_answer'] ?? null,
                    'model_answer' => $q['model_answer'] ?? null,
                    'suggested_marks' => $q['suggested_marks'] ?? 1,
                    'generated_by_ai' => true,
                    'ai_confidence' => $q['ai_confidence'] ?? null,
                    'is_active' => true,
                    'created_by' => $user->id,
                    'ai_generation_request_id' => $aiRequest->id,
                ]);

                if (($q['question_type'] ?? 'mcq') === 'mcq' && ! empty($q['options'])) {
                    foreach ($q['options'] as $opt) {
                        \App\Models\QuestionOption::create([
                            'question_bank_id' => $questionBank->id,
                            'option_key' => strtoupper(substr((string) $opt['key'], 0, 1)),
                            'option_text' => $opt['text'],
                            'is_correct' => (bool) ($opt['is_correct'] ?? false),
                        ]);
                    }
                }
                
                $generatedCount++;
            }

            $msg = "{$generatedCount} question(s) generated and added to the official catalog.";
            if ($skippedCount > 0) {
                $msg .= " {$skippedCount} duplicate(s) were skipped.";
            }

            return redirect()->route('admin.question-bank-catalog.index')
                ->with('success', $msg);

        } catch (\Exception $e) {
            $aiRequest->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return back()->withErrors(['ai' => 'Failed to generate questions: '.$e->getMessage()]);
        }
    }

    public function edit(Request $request, QuestionBank $question)
    {
        $user = $request->user();

        $question->load('options', 'course');
        
        $courses = Course::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return Inertia::render('Admin/QuestionBankCatalog/Edit', [
            'question' => $question,
            'courses' => $courses,
        ]);
    }

    public function update(Request $request, QuestionBank $question)
    {
        $user = $request->user();

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'topic' => 'nullable|string',
            'level' => 'nullable|string',
            'question_text' => 'required|string',
            'question_type' => 'required|in:mcq,theory,short_answer,fill_in',
            'difficulty' => 'required|in:easy,medium,hard',
            'correct_answer' => 'nullable|string',
            'model_answer' => 'nullable|string',
            'options' => 'nullable|array',
            'options.*.key' => 'required|string|max:1',
            'options.*.text' => 'required|string',
            'options.*.is_correct' => 'boolean',
        ]);

        $question->update([
            'course_id' => $validated['course_id'],
            'topic' => $validated['topic'] ?? null,
            'level' => $validated['level'] ?? null,
            'question_text' => $validated['question_text'],
            'question_type' => $validated['question_type'],
            'difficulty' => $validated['difficulty'],
            'correct_answer' => $validated['correct_answer'] ?? null,
            'model_answer' => $validated['model_answer'] ?? null,
        ]);

        if ($validated['question_type'] === 'mcq') {
            $question->options()->delete();
            if (!empty($validated['options'])) {
                foreach ($validated['options'] as $opt) {
                    $question->options()->create([
                        'option_key' => strtoupper(substr((string)$opt['key'], 0, 1)),
                        'option_text' => $opt['text'],
                        'is_correct' => (bool) ($opt['is_correct'] ?? false),
                    ]);
                }
            }
        }

        return redirect()->route('admin.question-bank-catalog.index')->with('success', 'Question updated successfully.');
    }

    public function destroy(Request $request, QuestionBank $question)
    {
        $user = $request->user();

        $question->update(['is_active' => false]);

        return back()->with('success', 'Question archived successfully.');
    }
}
