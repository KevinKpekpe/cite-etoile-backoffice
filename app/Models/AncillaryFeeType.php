<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'default_amount', 'pricing_options', 'is_system'])]
class AncillaryFeeType extends Model
{
    /** @return HasMany<AncillaryFee, $this> */
    public function fees(): HasMany
    {
        return $this->hasMany(AncillaryFee::class, 'fee_type', 'code');
    }

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'is_system' => 'boolean',
            'pricing_options' => 'array',
        ];
    }
}
