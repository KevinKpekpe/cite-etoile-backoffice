<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlotRequest extends FormRequest
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
        $plot = $this->route('plot');

        return [
            'plot_number' => ['required', 'string', 'max:50', Rule::unique('plots')->where('avenue_id', $this->integer('avenue_id'))->ignore($plot)],
            'reference' => ['nullable', 'string', 'max:100', Rule::unique('plots')->ignore($plot)],
            'avenue_id' => ['required', 'integer', 'exists:avenues,id'],
            'surface_area' => ['nullable', 'numeric', 'gt:0'], 'width' => ['nullable', 'numeric', 'gt:0'],
            'length' => ['nullable', 'numeric', 'gt:0'], 'cadastral_reference' => ['nullable', 'string', 'max:150'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'commercial_status' => ['required', 'in:available,reserved,subscribed,blocked,unavailable'],
            'financial_status' => ['required', 'in:unpaid,partially_paid,paid'],
            'administrative_status' => ['required', 'in:not_started,in_progress,validated,allocated,dispute'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
