<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

/**
 * Categories/instructors/clients are catalog support entities — they follow
 * the courses.* permissions (D-021).
 */
class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('courses.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('courses.view');
    }

    public function create(User $user): bool
    {
        return $user->can('courses.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('courses.update');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('courses.delete');
    }
}
