<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
{
    public function view(User $user, Question $question): bool
    {
        return $user->canManageExam($question->exam);
    }

    public function update(User $user, Question $question): bool
    {
        return $user->canManageExam($question->exam);
    }

    public function delete(User $user, Question $question): bool
    {
        return $user->canManageExam($question->exam);
    }
}
