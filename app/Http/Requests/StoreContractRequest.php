<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('subscriptions.update') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'signed_at' => ['nullable', 'date'], 'status' => ['required', 'in:draft,signed,cancelled,archived'],
            'document' => ['nullable', 'file', 'mimes:pdf', 'extensions:pdf', 'max:20480'],
        ];
    }
}
