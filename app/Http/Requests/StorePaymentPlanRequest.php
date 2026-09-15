<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('payment_plans.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('payment_plans')->ignore($this->route('payment_plan'))],
            'name' => ['required', 'string', 'max:150'], 'total_price' => ['required', 'numeric', 'min:0'],
            'monthly_amount' => ['nullable', 'required_if:frequency,monthly', 'numeric', 'min:0'],
            'duration_months' => ['required', 'integer', 'min:0'], 'frequency' => ['required', 'in:once,monthly'],
            'active' => ['sometimes', 'boolean'], 'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'], 'description' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
