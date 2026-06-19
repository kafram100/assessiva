<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isApprovedLecturer();
    }

    public function view(User $user, Exam $exam): bool
    {
        return $user->canManageExam($exam);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isApprovedLecturer();
    }

    public function update(User $user, Exam $exam): bool
    {
        return $user->canManageExam($exam);
    }

    public function delete(User $user, Exam $exam): bool
    {
        return $user->canManageExam($exam);
    }

    public function publish(User $user, Exam $exam): bool
    {
        return $user->canManageExam($exam);
    }
}
