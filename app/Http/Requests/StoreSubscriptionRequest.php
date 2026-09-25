<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('subscriptions.create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'], 'plot_id' => ['required', 'integer', 'exists:plots,id'],
            'payment_plan_id' => ['required', 'integer', 'exists:payment_plans,id'], 'subscription_date' => ['required', 'date'],
            'start_date' => ['required', 'date'],
            'deposit' => ['nullable', 'numeric', 'min:1'],
            'deposit_method' => ['nullable', 'in:cash,bank_transfer,mobile_money,card,other'],
        ];
    }
}
