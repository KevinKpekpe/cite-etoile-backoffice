<?php

namespace App\Models;

use Database\Factories\PlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['plot_number', 'reference', 'avenue_id', 'surface_area', 'width', 'length', 'cadastral_reference', 'base_price', 'commercial_status', 'financial_status', 'administrative_status', 'notes'])]
class Plot extends Model
{
    /** @use HasFactory<PlotFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'surface_area' => 'decimal:2',
            'width' => 'decimal:2',
            'length' => 'decimal:2',
            'base_price' => 'decimal:2',
        ];
    }
}
