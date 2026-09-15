<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAvenueRequest extends FormRequest
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
        $avenue = $this->route('avenue');
        $neighborhoodId = $this->integer('neighborhood_id');

        return [
            'neighborhood_id' => ['required', 'integer', 'exists:neighborhoods,id'],
            'code' => ['required', 'string', 'max:50', Rule::unique('avenues')->where('neighborhood_id', $neighborhoodId)->ignore($avenue)],
            'name' => ['required', 'string', 'max:150', Rule::unique('avenues')->where('neighborhood_id', $neighborhoodId)->ignore($avenue)],
            'description' => ['nullable', 'string', 'max:2000'], 'status' => ['required', 'in:planned,active,suspended'],
        ];
    }
}
