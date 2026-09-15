<?php

namespace App\Models;

use Database\Factories\AvenueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['neighborhood_id', 'code', 'name', 'description', 'status'])]
class Avenue extends Model
{
    /** @use HasFactory<AvenueFactory> */
    use HasFactory;
}
