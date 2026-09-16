<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('reports.view') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'customer_status' => ['nullable', Rule::in(['prospect', 'active', 'settled', 'suspended', 'archived'])],
            'plot_status' => ['nullable', Rule::in(['available', 'reserved', 'subscribed', 'blocked', 'unavailable'])],
            'payment_plan_id' => ['nullable', 'integer', 'exists:payment_plans,id'],
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
