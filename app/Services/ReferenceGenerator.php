<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReferenceGenerator
{
    public function __construct(private SettingService $settings) {}

    /** @param class-string<Model> $model */
    public function generate(string $model, string $column, string $group, string $defaultPrefix): string
    {
        $prefix = $this->settings->value($group, 'prefix', $defaultPrefix);

        do {
            $reference = $prefix.'-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while ($model::query()->where($column, $reference)->exists());

        return $reference;
    }
}
