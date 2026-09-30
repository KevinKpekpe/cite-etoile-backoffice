<?php

namespace App\Http\Requests;

use App\Http\Middleware\SetLocale;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $paymentMethods = ['cash', 'bank_transfer', 'mobile_money', 'card', 'other'];

        return [
            // Company & project
            'company.name' => ['required', 'string', 'max:150'],
            'company.logo' => ['nullable', 'url', 'max:500'],
            'company.phone' => ['nullable', 'string', 'max:50'],
            'company.email' => ['nullable', 'email', 'max:190'],
            'company.address' => ['nullable', 'string', 'max:500'],
            'project.name' => ['required', 'string', 'max:150'],

            // Finance
            'finance.currency' => ['required', 'string', 'size:3', 'uppercase'],
            'finance.payment_methods' => ['required', 'array', 'min:1'],
            'finance.payment_methods.*' => [Rule::in($paymentMethods)],

            // Reference prefixes
            'customer.prefix' => ['required', 'regex:/^[A-Z0-9]{2,10}$/', 'different:payment.prefix,receipt.prefix,contract.prefix'],
            'payment.prefix' => ['required', 'regex:/^[A-Z0-9]{2,10}$/', 'different:customer.prefix,receipt.prefix,contract.prefix'],
            'receipt.prefix' => ['required', 'regex:/^[A-Z0-9]{2,10}$/', 'different:customer.prefix,payment.prefix,contract.prefix'],
            'contract.prefix' => ['required', 'regex:/^[A-Z0-9]{2,10}$/', 'different:customer.prefix,payment.prefix,receipt.prefix'],

            // Subscription payment rules
            'subscription.allow_partial_payment' => ['required', 'boolean'],
            'subscription.allow_advance_payment' => ['required', 'boolean'],

            // Platform
            'platform.locale' => ['required', 'string', Rule::in(SetLocale::SUPPORTED)],

            // Client portal
            'portal.site_title' => ['required', 'string', 'max:200'],
            'portal.welcome_message' => ['nullable', 'string', 'max:1000'],
            'portal.support_email' => ['nullable', 'email', 'max:190'],
            'portal.support_phone' => ['nullable', 'string', 'max:50'],
            'portal.allow_profile_edit' => ['required', 'boolean'],
            'portal.show_payment_history' => ['required', 'boolean'],
        ];
    }
}
