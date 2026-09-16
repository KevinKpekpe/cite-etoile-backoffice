<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function record(?User $actor, string $action, Model $entity, ?array $oldValues = null, ?array $newValues = null, ?Request $request = null): AuditLog
    {
        $request ??= request();

        return AuditLog::query()->create([
            'user_id' => $actor?->id, 'action' => $action, 'entity_type' => $entity::class, 'entity_id' => $entity->getKey(),
            'old_values' => $oldValues, 'new_values' => $newValues,
            'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(),
        ]);
    }
}
