<?php

namespace App\Policies;

use App\Models\Instructor;
use App\Models\User;

/**
 * Catalog support entity — follows courses.* permissions (D-021).
 */
class InstructorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('courses.view');
    }

    public function view(User $user, Instructor $instructor): bool
    {
        return $user->can('courses.view');
    }

    public function create(User $user): bool
    {
        return $user->can('courses.create');
    }

    public function update(User $user, Instructor $instructor): bool
    {
        return $user->can('courses.update');
    }

    public function delete(User $user, Instructor $instructor): bool
    {
        return $user->can('courses.delete');
    }
}
