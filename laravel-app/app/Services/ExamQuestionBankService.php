<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Search and filter question bank items for attaching to a lecturer exam.
 * Attachment/copy logic lives in {@see QuestionBankService::attachBankQuestionsToExam()}.
 */
class ExamQuestionBankService
{
    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, int>, existing_bank_ids: list<int>}
     */
    public function searchForExam(Request $request, Exam $exam, User $user): array
    {
        $perPage = min(50, max(5, (int) $request->input('per_page', 20)));

        $existingBankIds = $exam->questions()
            ->whereNotNull('question_bank_id')
            ->pluck('question_bank_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $q = QuestionBank::query()
            ->visibleToLecturer($user)
            ->whereIn('status', ['approved', 'official'])
            ->whereNotIn('status', ['archived', 'draft', 'rejected', 'pending_official_approval'])
            ->with(['course:id,name,code']);

        $search = trim((string) $request->input('q', $request->input('search', '')));
        if ($search !== '') {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $search).'%';
            $q->where(function ($sub) use ($term): void {
                $sub->where('question_text', 'ilike', $term)
                    ->orWhere('topic', 'ilike', $term);
            });
        }

        $courseId = $request->input('course_id');
        if ($courseId !== null && $courseId !== '') {
            $q->where('course_id', (int) $courseId);
        }

        $topic = trim((string) $request->input('topic', ''));
        if ($topic !== '') {
            $q->where('topic', 'ilike', '%'.str_replace(['%', '_'], ['\\%', '\\_'], $topic).'%');
        }

        $type = trim((string) $request->input('question_type', ''));
        if ($type !== '') {
            $q->where('question_type', $type);
        }

        $difficulty = trim((string) $request->input('difficulty', ''));
        if ($difficulty !== '') {
            $q->where('difficulty', $difficulty);
        }

        $source = trim((string) $request->input('source', ''));
        if ($source === 'official') {
            $q->where('status', 'official');
        } elseif ($source === 'mine') {
            $q->where('lecturer_id', $user->id);
        }

        $statusFilter = trim((string) $request->input('status', ''));
        if ($statusFilter === 'approved') {
            $q->where('status', 'approved');
        } elseif ($statusFilter === 'official') {
            $q->where('status', 'official');
        }

        /** @var LengthAwarePaginator<int, QuestionBank> $page */
        $page = $q->orderByDesc('id')->paginate($perPage)->withQueryString();

        $data = $page->getCollection()->map(function (QuestionBank $row) use ($user, $existingBankIds) {
            $onExam = in_array((int) $row->id, $existingBankIds, true);
            $sourceLabel = $row->status === 'official'
                ? 'Official'
                : ((int) $row->lecturer_id === (int) $user->id ? 'My bank' : 'Shared');

            return [
                'id' => $row->id,
                'preview' => Str::limit(strip_tags((string) $row->question_text), 220),
                'topic' => $row->topic,
                'question_type' => $row->question_type,
                'difficulty' => $row->difficulty,
                'suggested_marks' => $row->suggested_marks,
                'status' => $row->status,
                'source_label' => $sourceLabel,
                'course' => $row->course ? [
                    'id' => $row->course->id,
                    'name' => $row->course->name,
                    'code' => $row->course->code,
                ] : null,
                'already_on_exam' => $onExam,
            ];
        })->values()->all();

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
            'existing_bank_ids' => $existingBankIds,
        ];
    }
}
