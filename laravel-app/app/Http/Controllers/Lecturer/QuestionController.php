<?php

namespace App\Http\Controllers\Lecturer;

use App\Enums\QuestionType;
use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuestionController extends Controller
{
    public function create(Exam $exam): InertiaResponse
    {
        $this->authorize('update', $exam);

        return Inertia::render('Lecturer/Questions/Create', [
            'exam' => $exam,
        ]);
    }

    public function store(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $request->validate([
            'q_type' => ['required', 'in:'.implode(',', QuestionType::values())],
            'question_text' => 'required|string',
            'marks' => 'required|integer|min:1',
            'correct_option' => 'nullable|string|max:65000',
            'option_a' => 'nullable|string|max:65000',
            'option_b' => 'nullable|string|max:65000',
            'option_c' => 'nullable|string|max:65000',
            'option_d' => 'nullable|string|max:65000',
            'option_e' => 'nullable|string|max:65000',
        ]);

        $qType = $request->string('q_type')->toString();
        $correctOption = $this->normalizeCorrectOption($qType, $request->input('correct_option'));
        $options = $this->normalizedOptionsForType($qType, $request);

        Question::create([
            'exam_id' => $exam->id,
            'question_text' => $request->string('question_text')->toString(),
            'q_type' => $qType,
            'option_a' => $options['a'],
            'option_b' => $options['b'],
            'option_c' => $options['c'],
            'option_d' => $options['d'],
            'option_e' => $options['e'],
            'correct_option' => $correctOption,
            'marks' => (int) $request->input('marks'),
        ]);

        return $this->redirectAfterManagingQuestions($exam)
            ->with('success', 'Question added.');
    }

    public function edit(Question $question): InertiaResponse
    {
        $this->authorize('update', $question);

        $question->load('exam');

        return Inertia::render('Lecturer/Questions/Edit', [
            'exam' => $question->exam,
            'question' => $question,
        ]);
    }

    public function update(Request $request, Question $question): RedirectResponse
    {
        $this->authorize('update', $question);

        $request->validate([
            'q_type' => ['required', 'in:'.implode(',', QuestionType::values())],
            'question_text' => 'required|string',
            'marks' => 'required|integer|min:1',
            'correct_option' => 'nullable|string|max:65000',
            'option_a' => 'nullable|string|max:65000',
            'option_b' => 'nullable|string|max:65000',
            'option_c' => 'nullable|string|max:65000',
            'option_d' => 'nullable|string|max:65000',
            'option_e' => 'nullable|string|max:65000',
        ]);

        $qType = $request->string('q_type')->toString();
        $correctOption = $this->normalizeCorrectOption($qType, $request->input('correct_option'));
        $options = $this->normalizedOptionsForType($qType, $request);

        $question->update([
            'question_text' => $request->string('question_text')->toString(),
            'q_type' => $qType,
            'option_a' => $options['a'],
            'option_b' => $options['b'],
            'option_c' => $options['c'],
            'option_d' => $options['d'],
            'option_e' => $options['e'],
            'correct_option' => $correctOption,
            'marks' => (int) $request->input('marks'),
        ]);

        return $this->redirectAfterManagingQuestions($question->exam)
            ->with('success', 'Question updated.');
    }

    public function destroy(Question $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        $exam = $question->exam;
        $question->delete();

        return $this->redirectAfterManagingQuestions($exam)
            ->with('success', 'Question deleted.');
    }

    public function importForm(Exam $exam): InertiaResponse
    {
        $this->authorize('update', $exam);

        return Inertia::render('Lecturer/Questions/Import', [
            'exam' => $exam,
        ]);
    }

    public function import(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorize('update', $exam);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return back()->withErrors(['csv_file' => 'Could not read the uploaded file.'])->withInput();
        }

        fgetcsv($handle);
        $imported = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) {
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
            if (! in_array($qType, QuestionType::values(), true)) {
                continue;
            }
            if (trim((string) ($questionText ?? '')) === '') {
                continue;
            }

            $normalizedCorrect = trim((string) ($correctAns ?? ''));
            if ($qType === QuestionType::Mcq->value) {
                $normalizedCorrect = strtoupper(substr($normalizedCorrect, 0, 1));
            }
            $normalizedCorrect = $normalizedCorrect !== '' ? $normalizedCorrect : null;

            Question::create([
                'exam_id' => $exam->id,
                'question_text' => trim((string) $questionText),
                'q_type' => $qType,
                'option_a' => trim((string) ($optA ?? '')) ?: null,
                'option_b' => trim((string) ($optB ?? '')) ?: null,
                'option_c' => trim((string) ($optC ?? '')) ?: null,
                'option_d' => trim((string) ($optD ?? '')) ?: null,
                'option_e' => trim((string) ($optE ?? '')) ?: null,
                'correct_option' => $normalizedCorrect,
                'marks' => max(1, (int) ($marks ?? 1)),
            ]);
            $imported++;
        }

        fclose($handle);

        return $this->redirectAfterManagingQuestions($exam)
            ->with('success', "{$imported} question(s) imported.");
    }

    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="question_template.csv"',
        ];

        $rows = [
            ['question_type', 'question_text', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'correct_answer', 'marks'],
            ['mcq', 'What is 2+2?', '3', '4', '5', '6', '', 'B', '1'],
            ['theory', 'Explain photosynthesis.', '', '', '', '', '', '', '5'],
            ['fill_in', 'The capital of France is ___', '', '', '', '', '', 'Paris', '2'],
        ];

        $callback = function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function redirectAfterManagingQuestions(Exam $exam): RedirectResponse
    {
        if ($exam->practice_mode) {
            return redirect()->route('lecturer.practice-quizzes.edit', $exam);
        }

        return redirect()->route('lecturer.exams.show', $exam);
    }

    private function normalizeCorrectOption(string $qType, mixed $raw): ?string
    {
        if ($qType === QuestionType::Mcq->value) {
            $letter = strtoupper(substr(trim((string) $raw), 0, 1));

            return $letter !== '' ? $letter : null;
        }

        $s = trim((string) $raw);

        return $s !== '' ? $s : null;
    }

    /**
     * @return array{a: ?string, b: ?string, c: ?string, d: ?string, e: ?string}
     */
    private function normalizedOptionsForType(string $qType, Request $request): array
    {
        if ($qType !== QuestionType::Mcq->value) {
            return ['a' => null, 'b' => null, 'c' => null, 'd' => null, 'e' => null];
        }

        return [
            'a' => $this->trimNullable($request->input('option_a')),
            'b' => $this->trimNullable($request->input('option_b')),
            'c' => $this->trimNullable($request->input('option_c')),
            'd' => $this->trimNullable($request->input('option_d')),
            'e' => $this->trimNullable($request->input('option_e')),
        ];
    }

    private function trimNullable(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);

        return $s !== '' ? $s : null;
    }
}
