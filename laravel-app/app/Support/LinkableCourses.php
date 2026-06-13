<?php

namespace App\Support;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Lists courses a user may attach to exams / practice quizzes.
 * Mirrors {@see \App\Policies\CoursePolicy::view} without per-row policy calls.
 */
final class LinkableCourses
{
    /**
     * Query scope: courses visible for roster linking / labels (same rules as {@see CoursePolicy::view}).
     *
     * @return Builder<Course>
     */
    public static function courseQueryForUser(User $user): Builder
    {
        $q = Course::query()->orderBy('code')->orderBy('name');

        if ($user->isAdmin()) {
            return $q;
        }

        if ($user->isHod() && $user->department_id) {
            $deptId = (int) $user->department_id;
            $uid = (int) $user->id;

            return $q->where(function ($w) use ($deptId, $uid): void {
                $w->where('department_id', $deptId)
                    ->orWhere('user_id', $uid);
            });
        }

        if ($user->isApprovedLecturer()) {
            return $q->where('user_id', (int) $user->id);
        }

        return $q->whereRaw('1 = 0');
    }

    /**
     * @return Collection<int, array{id: int, code: string|null, name: string}>
     */
    public static function forUser(User $user): Collection
    {
        return static::courseQueryForUser($user)
            ->get(['id', 'code', 'name'])
            ->map(fn (Course $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
            ])
            ->values();
    }
}
