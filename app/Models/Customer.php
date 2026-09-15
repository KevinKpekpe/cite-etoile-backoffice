<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['customer_number', 'user_id', 'first_name', 'last_name', 'middle_name', 'gender', 'birth_date', 'phone', 'whatsapp', 'email', 'address', 'city', 'country', 'nationality', 'status', 'created_by'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['birth_date' => 'date'];
    }
}
