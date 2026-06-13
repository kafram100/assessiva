<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QuestionBankCatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        if (! $user instanceof User || ! $user->isAdminOrHod()) {
            abort(403, 'Access denied. Admin or HOD only.');
        }

        if ($user->isHod() && ! $user->department_id) {
            abort(403, 'Your HOD account must be assigned to a department before you can browse the question bank.');
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
            ]);

        if ($request->filled('search')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $request->input('search'))).'%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('question_text', 'like', $term)
                    ->orWhere('topic', 'like', $term);
            });
        }

        if ($request->filled('topic')) {
            $topic = (string) $request->input('topic');
            if ($topic === '__uncategorized__') {
                $query->where(function (Builder $q): void {
                    $q->whereNull('topic')->orWhereRaw("TRIM(topic) = ''");
                });
            } else {
                $query->where('topic', $topic);
            }
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($request->filled('question_type')) {
            $query->where('question_type', (string) $request->input('question_type'));
        }

        $query->orderByRaw("CASE WHEN topic IS NULL OR TRIM(topic) = '' THEN 1 ELSE 0 END")
            ->orderBy('topic')
            ->orderByDesc('updated_at');

        $questions = $query
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('Admin/QuestionBankCatalog/Index', [
            'questions' => $questions,
            'topicOptions' => $topicOptions,
            'filters' => $request->only(['search', 'topic', 'status', 'question_type']),
            'scope' => $user->isAdmin() ? 'admin' : 'hod',
        ]);
    }

    /**
     * @return Builder<QuestionBank>
     */
    private function scopedBankQuery(User $user): Builder
    {
        $query = QuestionBank::query();

        if ($user->isHod()) {
            $deptId = (int) $user->department_id;
            $query->where(function (Builder $q) use ($deptId): void {
                $q->where('department_id', $deptId)
                    ->orWhere(function (Builder $q2): void {
                        $q2->whereNull('department_id')->where('status', 'official');
                    });
            });
        }

        return $query;
    }
}
