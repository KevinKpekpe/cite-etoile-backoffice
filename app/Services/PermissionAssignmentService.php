<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PermissionAssignmentService
{
    public function __construct(private AuditService $auditService) {}

    /** @param array<int, int> $permissionIds */
    public function sync(Role $role, User $actor, array $permissionIds): Role
    {
        return DB::transaction(function () use ($role, $actor, $permissionIds): Role {
            $oldValues = ['permissions' => $role->permissions()->orderBy('name')->pluck('name')->all()];
            $role->permissions()->sync($permissionIds);
            $newValues = ['permissions' => Permission::query()->whereIn('id', $permissionIds)->orderBy('name')->pluck('name')->all()];
            $this->auditService->record($actor, 'role.permissions_updated', $role, $oldValues, $newValues);

            return $role->load('permissions');
        });
    }
}
