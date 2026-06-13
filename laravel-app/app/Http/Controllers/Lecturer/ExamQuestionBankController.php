<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Services\ExamQuestionBankService;
use App\Services\QuestionBankService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExamQuestionBankController extends Controller
{
    public function index(Request $request, Exam $exam, ExamQuestionBankService $examQuestionBankService): JsonResponse
    {
        Gate::authorize('update', $exam);

        $payload = $examQuestionBankService->searchForExam($request, $exam, $request->user());

        return response()->json($payload);
    }

    public function attach(Request $request, Exam $exam, QuestionBankService $questionBankService): RedirectResponse
    {
        Gate::authorize('update', $exam);

        $rawIds = $request->input('question_bank_ids', $request->input('question_ids', []));
        $request->merge([
            'question_bank_ids' => is_array($rawIds) ? $rawIds : [],
        ]);

        $validated = $request->validate([
            'question_bank_ids' => 'required|array|min:1|max:100',
            'question_bank_ids.*' => 'integer|distinct|exists:question_bank,id',
        ]);

        $result = $questionBankService->attachBankQuestionsToExam(
            $exam,
            $request->user(),
            $validated['question_bank_ids'],
        );

        $added = $result['added'];
        $dup = $result['skipped_duplicates'];
        $inv = $result['skipped_invalid'];

        if ($added === 0) {
            $parts = [];
            if ($dup > 0) {
                $parts[] = "{$dup} were already in this exam";
            }
            if ($inv > 0) {
                $parts[] = "{$inv} could not be used (missing, not approved for you, or not visible)";
            }
            $msg = $parts !== []
                ? 'No questions were added. '.implode('; ', $parts).'.'
                : 'No questions were added. Choose approved or official items from your eligible bank.';

            return back()->with('error', $msg);
        }

        $msg = $added === 1
            ? '1 question added from Question Bank successfully.'
            : "{$added} questions added from Question Bank successfully.";

        $tail = [];
        if ($dup > 0) {
            $tail[] = "{$dup} were already in this exam and skipped";
        }
        if ($inv > 0) {
            $tail[] = "{$inv} could not be used and were skipped";
        }
        if ($tail !== []) {
            $msg .= ' '.implode('; ', $tail).'.';
        }

        return back()->with('success', $msg);
    }
}
