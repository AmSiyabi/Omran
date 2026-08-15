<?php

namespace App\Listeners;

use Illuminate\Support\Collection;
use Spatie\Permission\Contracts\Role;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * Spec §7.5 + Phase 1 acceptance: every role change appears in the activity log.
 */
class LogRoleChange
{
    public function handle(RoleAttachedEvent|RoleDetachedEvent $event): void
    {
        $action = $event instanceof RoleAttachedEvent ? 'role_attached' : 'role_detached';

        activity('roles')
            ->performedOn($event->model)
            ->causedBy(auth()->user())
            ->withProperties(['roles' => $this->roleNames($event->rolesOrIds)])
            ->event($action)
            ->log($action);
    }

    /**
     * @return array<int, string>
     */
    private function roleNames(mixed $rolesOrIds): array
    {
        return Collection::wrap($rolesOrIds)
            ->map(function (mixed $role): string {
                if ($role instanceof Role) {
                    return $role->name;
                }

                return (string) (RoleModel::query()->find($role)->name ?? $role);
            })
            ->values()
            ->all();
    }
}
