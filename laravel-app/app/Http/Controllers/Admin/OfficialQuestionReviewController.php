<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OfficialQuestionReview;
use App\Models\User;
use App\Services\QuestionBankService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OfficialQuestionReviewController extends Controller
{
    public function __construct(
        private readonly QuestionBankService $questionBankService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isAdminOrHod()) {
            abort(403, 'Access denied. Admin or HOD only.');
        }

        if ($user->isHod() && ! $user->department_id) {
            abort(403, 'Your HOD account must be assigned to a department before you can review official questions.');
        }

        $reviews = OfficialQuestionReview::query()
            ->with(['question.lecturer.department.school', 'submitter', 'reviewer'])
            ->join('question_bank', 'question_bank.id', '=', 'official_question_reviews.question_bank_id')
            ->when($user->isHod(), function ($q) use ($user): void {
                $q->where('question_bank.department_id', $user->department_id);
            })
            ->orderByRaw("CASE official_question_reviews.status WHEN 'pending' THEN 0 ELSE 1 END")
            ->orderByRaw('CASE WHEN question_bank.topic IS NULL OR TRIM(question_bank.topic) = \'\' THEN 1 ELSE 0 END')
            ->orderBy('question_bank.topic')
            ->orderByDesc('official_question_reviews.created_at')
            ->select('official_question_reviews.*')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/OfficialQuestionReviews/Index', [
            'reviews' => $reviews,
        ]);
    }

    public function approve(Request $request, OfficialQuestionReview $officialQuestionReview): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isAdminOrHod()) {
            abort(403, 'Access denied. Admin or HOD only.');
        }

        if ($user->isHod() && ! $user->department_id) {
            abort(403, 'Your HOD account must be assigned to a department before you can review official questions.');
        }

        $review = $officialQuestionReview->load('question');

        $this->authorizeOfficialReviewScope($review, $user);
        $this->questionBankService->reviewOfficialQuestion($review, $user, true);
        $this->questionBankService->audit('official_review_approved_web', $review->question, $user, ['review_id' => $review->id], $request);

        return back()->with('success', 'Official question approved.');
    }

    public function reject(Request $request, OfficialQuestionReview $officialQuestionReview): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isAdminOrHod()) {
            abort(403, 'Access denied. Admin or HOD only.');
        }

        if ($user->isHod() && ! $user->department_id) {
            abort(403, 'Your HOD account must be assigned to a department before you can review official questions.');
        }

        $data = $request->validate(['reason' => 'required|string|max:2000']);

        $review = $officialQuestionReview->load('question');

        $this->authorizeOfficialReviewScope($review, $user);
        $this->questionBankService->reviewOfficialQuestion($review, $user, false, $data['reason']);
        $this->questionBankService->audit('official_review_rejected_web', $review->question, $user, [
            'review_id' => $review->id,
            'reason' => $data['reason'],
        ], $request);

        return back()->with('success', 'Official question rejected with reason.');
    }

    private function authorizeOfficialReviewScope(OfficialQuestionReview $review, User $user): void
    {
        if ($user->isAdmin()) {
            return;
        }

        if (! $review->question || (int) $review->question->department_id !== (int) $user->department_id) {
            abort(403, 'You can only review questions from your department.');
        }
    }
}
