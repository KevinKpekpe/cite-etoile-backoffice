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

    public function value(string $group, string $key, string $default = ''): string
    {
        return (string) (Setting::query()->where('setting_group', $group)->where('setting_key', $key)->value('value') ?? $default);
    }

    public function boolean(string $group, string $key, bool $default = false): bool
    {
        return filter_var($this->value($group, $key, $default ? 'true' : 'false'), FILTER_VALIDATE_BOOL);
    }

    /**
     * @param  array<int, string>  $default
     * @return array<int, string>
     */
    public function stringList(string $group, string $key, array $default = []): array
    {
        $decoded = json_decode($this->value($group, $key, json_encode($default, JSON_THROW_ON_ERROR)), true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : $default;
    }

    /** @param array<string, string> $values */
    public function updateMany(array $values, User $actor): void
    {
        DB::transaction(function () use ($values, $actor): void {
            foreach ($values as $qualifiedKey => $value) {
                [$group, $key] = explode('.', $qualifiedKey, 2);
                $setting = Setting::query()->where('setting_group', $group)->where('setting_key', $key)->firstOrFail();
                $this->update($setting, $actor, $value);
            }
        });
    }
}
