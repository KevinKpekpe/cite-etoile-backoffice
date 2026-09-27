<?php

namespace App\Http\Requests;

use App\Services\SettingService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAncillaryFeePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payments.create') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $settings = app(SettingService::class);

        return [
            'idempotency_key' => ['required', 'uuid'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency' => ['required', Rule::in([$settings->value('finance', 'currency', 'USD')])],
            'payment_method' => ['required', Rule::in($settings->stringList('finance', 'payment_methods', ['cash', 'bank_transfer', 'mobile_money', 'card', 'other']))],
            'transaction_reference' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'extensions:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
