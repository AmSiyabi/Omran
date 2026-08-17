<?php

namespace App\Policies;

use App\Models\RegistrationLink;
use App\Models\User;

class RegistrationLinkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cohorts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('links.create');
    }

    public function revoke(User $user, RegistrationLink $link): bool
    {
        return $user->can('links.revoke');
    }
}
