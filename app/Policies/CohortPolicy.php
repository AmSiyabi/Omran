<?php

namespace App\Policies;

use App\Models\Cohort;
use App\Models\User;

class CohortPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cohorts.view');
    }

    public function view(User $user, Cohort $cohort): bool
    {
        return $user->can('cohorts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cohorts.create');
    }

    public function update(User $user, Cohort $cohort): bool
    {
        return $user->can('cohorts.update');
    }

    public function delete(User $user, Cohort $cohort): bool
    {
        return $user->can('cohorts.delete');
    }

    /**
     * Status transitions (announce/open/close/deliver/cancel) ride on update.
     * The transition to settled is Phase 5's settlement engine — finance.settle.
     */
    public function transition(User $user, Cohort $cohort): bool
    {
        return $user->can('cohorts.update');
    }
}
