<?php

namespace App\Models;

use Database\Factories\AncillaryFeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['subscription_id', 'fee_type', 'fee_label', 'installment_number', 'due_date', 'amount_due', 'amount_paid', 'paid_at', 'realized_at', 'status'])]
class AncillaryFee extends Model
{
    /** @use HasFactory<AncillaryFeeFactory> */
    use HasFactory;

    public const TYPES = [
        'survey' => 'Bornage',
        'cadastral_number' => 'Numéro cadastral',
        'occupancy_certificate' => 'Certificat d’occupation',
        'registration_certificate' => 'Certificat d’enregistrement',
        'development' => 'Aménagement',
    ];

    /** @return BelongsTo<AncillaryFeeType, $this> */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AncillaryFeeType::class, 'fee_type', 'code');
    }

    /** @return BelongsTo<Subscription, $this> */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function label(): string
    {
        return $this->fee_label ?: (self::TYPES[$this->fee_type] ?? $this->fee_type);
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance' => 'decimal:2',
            'paid_at' => 'datetime',
            'realized_at' => 'datetime',
        ];
    }
}
