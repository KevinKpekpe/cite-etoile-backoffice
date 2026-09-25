<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNeighborhoodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('plots.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50', Rule::unique('neighborhoods')->ignore($this->route('neighborhood'))],
            'name' => ['required', 'string', 'max:150', Rule::unique('neighborhoods')->ignore($this->route('neighborhood'))],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:planned,active,commercializable,completed,suspended'],
        ];
    }
}
