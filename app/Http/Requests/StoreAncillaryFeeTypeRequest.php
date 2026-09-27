<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAncillaryFeeTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('payments.create') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'code' => [$this->isMethod('POST') ? 'required' : 'prohibited', 'string', 'alpha_dash', 'max:64', Rule::unique('ancillary_fee_types', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'default_amount' => ['nullable', 'numeric', 'gt:0', 'max:9999999999.99'],
            'pricing_options' => [$this->route('ancillary_fee_type')?->code === 'development' ? 'required' : 'prohibited', 'array'],
            'pricing_options.cash.total' => ['required_with:pricing_options', 'numeric', 'gt:0', 'max:9999999999.99'],
            'pricing_options.cash.monthly' => ['required_with:pricing_options', 'numeric', 'gt:0', 'max:9999999999.99'],
            'pricing_options.one_year.total' => ['required_with:pricing_options', 'numeric', 'gt:0', 'max:9999999999.99'],
            'pricing_options.one_year.monthly' => ['required_with:pricing_options', 'numeric', 'gt:0', 'max:9999999999.99'],
            'pricing_options.three_years.total' => ['required_with:pricing_options', 'numeric', 'gt:0', 'max:9999999999.99'],
            'pricing_options.three_years.monthly' => ['required_with:pricing_options', 'numeric', 'gt:0', 'max:9999999999.99'],
            'pricing_options.five_years.total' => ['required_with:pricing_options', 'numeric', 'gt:0', 'max:9999999999.99'],
            'pricing_options.five_years.monthly' => ['required_with:pricing_options', 'numeric', 'gt:0', 'max:9999999999.99'],
            'pricing_options.ten_years.total' => ['required_with:pricing_options', 'numeric', 'gt:0', 'max:9999999999.99'],
            'pricing_options.ten_years.monthly' => ['required_with:pricing_options', 'numeric', 'gt:0', 'max:9999999999.99'],
        ];
    }
}
