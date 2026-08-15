<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

/**
 * Catalog support entity — follows courses.* permissions (D-021).
 */
class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('courses.view');
    }

    public function view(User $user, Client $client): bool
    {
        return $user->can('courses.view');
    }

    public function create(User $user): bool
    {
        return $user->can('courses.create');
    }

    public function update(User $user, Client $client): bool
    {
        return $user->can('courses.update');
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->can('courses.delete');
    }
}
