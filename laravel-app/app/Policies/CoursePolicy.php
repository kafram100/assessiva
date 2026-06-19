<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isApprovedLecturer();
    }

    public function view(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return (int) $course->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isApprovedLecturer();
    }

    public function update(User $user, Course $course): bool
    {
        return $this->view($user, $course);
    }

    public function delete(User $user, Course $course): bool
    {
        return $this->view($user, $course);
    }
}
