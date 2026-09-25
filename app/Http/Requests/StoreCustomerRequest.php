<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('customers.create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'], 'gender' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date', 'before:today'], 'phone' => ['required', 'string', 'max:50'],
            'secondary_phone' => ['nullable', 'string', 'max:50'], 'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'], 'address' => ['nullable', 'string', 'max:1000'],
            'commune' => ['nullable', 'string', 'max:100'], 'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'], 'nationality' => ['nullable', 'string', 'max:100'],
            'internal_notes' => ['nullable', 'string', 'max:5000'], 'status' => ['required', 'in:prospect,active,settled,suspended'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            // Souscription initiale optionnelle
            'plot_id' => ['nullable', 'integer', 'exists:plots,id'],
            'payment_plan_id' => ['nullable', 'integer', 'exists:payment_plans,id', 'required_with:plot_id'],
            'subscription_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'deposit' => ['nullable', 'numeric', 'min:1'],
            'deposit_method' => ['nullable', 'in:cash,bank_transfer,mobile_money,card,other'],
        ];
    }
}
