<?php

namespace App\Http\Requests;

use App\Services\SettingService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('payments.create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $settings = app(SettingService::class);

        return [
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'], 'idempotency_key' => ['required', 'uuid'],
            'payment_date' => ['required', 'date'], 'amount' => ['required', 'numeric', 'gt:0'], 'currency' => ['required', Rule::in([$settings->value('finance', 'currency', 'USD')])],
            'payment_method' => ['required', Rule::in($settings->stringList('finance', 'payment_methods', ['cash', 'bank_transfer', 'mobile_money', 'card', 'other']))],
            'transaction_reference' => ['nullable', 'string', 'max:150'], 'notes' => ['nullable', 'string', 'max:3000'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'extensions:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
