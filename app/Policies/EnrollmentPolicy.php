<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('enrollments.view');
    }

    public function view(User $user, Enrollment $enrollment): bool
    {
        return $user->can('enrollments.view');
    }

    public function manage(User $user): bool
    {
        return $user->can('enrollments.manage');
    }

    public function export(User $user): bool
    {
        return $user->can('enrollments.export');
    }
}
