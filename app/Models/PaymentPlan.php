<?php

namespace App\Models;

use Database\Factories\PaymentPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'total_price', 'monthly_amount', 'duration_months', 'frequency', 'active', 'valid_from', 'valid_until', 'description'])]
class PaymentPlan extends Model
{
    /** @use HasFactory<PaymentPlanFactory> */
    use HasFactory;

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    protected function casts(): array
    {
        return [
            'total_price' => 'decimal:2',
            'monthly_amount' => 'decimal:2',
            'active' => 'boolean',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }
}
