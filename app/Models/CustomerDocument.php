<?php

namespace App\Models;

use Database\Factories\CustomerDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['customer_id', 'document_type', 'name', 'file_path', 'uploaded_by'])]
class CustomerDocument extends Model
{
    /** @use HasFactory<CustomerDocumentFactory> */
    use HasFactory;

    public const UPDATED_AT = null;
}
