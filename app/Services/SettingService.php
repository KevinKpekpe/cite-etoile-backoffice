<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SettingService
{
    public function __construct(private AuditService $auditService) {}

    public function update(Setting $setting, User $actor, string $value): Setting
    {
        return DB::transaction(function () use ($setting, $actor, $value): Setting {
            $oldValues = $setting->only(['value', 'value_type', 'is_public']);
            $setting->update(['value' => $value, 'updated_by' => $actor->id]);
            $this->auditService->record($actor, 'setting.updated', $setting, $oldValues, $setting->only(['value', 'value_type', 'is_public']));

            return $setting->refresh();
        });
    }
}
