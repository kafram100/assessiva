<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isApprovedLecturer() || $user->isApprovedHod();
    }

    public function view(User $user, Course $course): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isHod() && $user->department_id && (int) $course->department_id === (int) $user->department_id) {
            return true;
        }

        return (int) $course->user_id === (int) $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isApprovedLecturer() || $user->isApprovedHod();
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
