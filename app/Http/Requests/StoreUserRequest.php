<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('users.manage') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:50'],
            'role_id' => [
                'required', 'integer', 'exists:roles,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $role = Role::query()->find((int) $value);
                    if ($role?->name === 'super_admin' && ! $this->user()?->hasRole('super_admin')) {
                        $fail('Vous n\'êtes pas autorisé à attribuer le rôle super_admin.');
                    }
                },
            ],
        ];
    }
}
