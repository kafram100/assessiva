<?php

namespace App\Http\Controllers\Hod;

use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\QuestionBankService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AiQuestionReviewController extends Controller
{
    public function __construct(
        private readonly QuestionBankService $questionBankService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isApprovedHod()) {
            abort(403, 'Access denied. Approved heads of department only.');
        }

        $deptId = (int) $user->department_id;

        $pending = QuestionBank::query()
            ->where('generated_by_ai', true)
            ->where('submitted_for_admin_ai_review', true)
            ->where('status', 'draft')
            ->whereHas('lecturer', fn ($q) => $q->where('department_id', $deptId))
            ->with(['lecturer.department.school', 'options'])
            ->orderByDesc('updated_at')
            ->get();

        $byTopic = $pending->groupBy(function (QuestionBank $q): string {
            $t = trim((string) $q->topic);

            return $t === '' ? '__uncategorized__' : $t;
        });

        $categories = $byTopic
            ->map(function ($items, string $topicKey): array {
                $lecturerGroups = $items
                    ->groupBy('lecturer_id')
                    ->map(function ($lItems): array {
                        /** @var QuestionBank $first */
                        $first = $lItems->first();
                        $lecturer = $first->lecturer;

                        return [
                            'lecturer_id' => $lecturer?->id,
                            'lecturer_name' => $lecturer?->name ?? $lecturer?->username ?? 'Unknown',
                            'lecturer_username' => $lecturer?->username,
                            'department' => $lecturer?->department?->name,
                            'school' => $lecturer?->department?->school?->name,
                            'questions' => $lItems->values()->map(fn (QuestionBank $q): array => [
                                'id' => $q->id,
                                'topic' => $q->topic,
                                'question_text' => $q->question_text,
                                'question_type' => $q->question_type,
                                'difficulty' => $q->difficulty,
                                'suggested_marks' => $q->suggested_marks,
                                'updated_at' => $q->updated_at?->toIso8601String(),
                                'options' => $q->options,
                            ])->all(),
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'topic_key' => $topicKey,
                    'topic_label' => $topicKey === '__uncategorized__' ? 'Uncategorized' : $topicKey,
                    'total' => $items->count(),
                    'lecturer_groups' => $lecturerGroups,
                ];
            })
            ->sortBy(function (array $cat): string {
                if ($cat['topic_key'] === '__uncategorized__') {
                    return "\u{10FFFF}";
                }

                return mb_strtolower($cat['topic_label']);
            })
            ->values()
            ->all();

        return Inertia::render('Hod/AiQuestionReviews/Index', [
            'categories' => $categories,
            'pendingTotal' => $pending->count(),
        ]);
    }

    public function bulkApprove(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isApprovedHod()) {
            abort(403);
        }

        $deptId = (int) $user->department_id;

        $data = $request->validate([
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer|distinct',
        ]);

        $questions = QuestionBank::query()
            ->whereIn('id', $data['ids'])
            ->where('generated_by_ai', true)
            ->where('submitted_for_admin_ai_review', true)
            ->where('status', 'draft')
            ->whereHas('lecturer', fn ($q) => $q->where('department_id', $deptId))
            ->get();

        if ($questions->isEmpty()) {
            return back()->withErrors(['ids' => 'No matching questions in your department review queue. Refresh the page and try again.']);
        }

        $approved = 0;
        DB::transaction(function () use ($questions, $user, &$approved): void {
            foreach ($questions as $question) {
                $this->questionBankService->approveQuestion($question, $user);
                $approved++;
            }
        });

        $skipped = count($data['ids']) - $approved;

        $message = "Approved {$approved} question(s) into lecturers' personal banks.";
        if ($skipped > 0) {
            $message .= " {$skipped} id(s) were skipped (not in queue or already processed).";
        }

        return back()->with('success', $message);
    }

    public function bulkReject(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isApprovedHod()) {
            abort(403);
        }

        $deptId = (int) $user->department_id;

        $data = $request->validate([
            'ids' => 'required|array|min:1|max:500',
            'ids.*' => 'integer|distinct',
            'reason' => 'nullable|string|max:2000',
        ]);

        $questions = QuestionBank::query()
            ->whereIn('id', $data['ids'])
            ->where('generated_by_ai', true)
            ->where('submitted_for_admin_ai_review', true)
            ->where('status', 'draft')
            ->whereHas('lecturer', fn ($q) => $q->where('department_id', $deptId))
            ->get();

        if ($questions->isEmpty()) {
            return back()->withErrors(['ids' => 'No matching questions in your department review queue.']);
        }

        $reason = $data['reason'] ?? null;
        $rejected = 0;
        DB::transaction(function () use ($questions, $user, $reason, &$rejected): void {
            foreach ($questions as $question) {
                $this->questionBankService->rejectAiQuestionInHodQueue($question, $user, $reason);
                $rejected++;
            }
        });

        return back()->with('success', "Rejected {$rejected} question(s). Lecturers can edit and submit again.");
    }
}
