<?php

namespace App\Models;

use Database\Factories\AvenueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['neighborhood_id', 'code', 'name', 'description', 'status'])]
class Avenue extends Model
{
    /** @use HasFactory<AvenueFactory> */
    use HasFactory;

    /** @return BelongsTo<Neighborhood, $this> */
    public function neighborhood(): BelongsTo
    {
        return $this->belongsTo(Neighborhood::class);
    }

    /** @return HasMany<Plot, $this> */
    public function plots(): HasMany
    {
        return $this->hasMany(Plot::class);
    }
}
