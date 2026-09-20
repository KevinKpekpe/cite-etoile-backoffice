<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! ($this->user()?->can('users.manage') ?? false)) {
            return false;
        }

        /** @var User $subject */
        $subject = $this->route('user');

        // Non-super_admin cannot edit a super_admin account.
        if ($subject->hasRole('super_admin') && ! $this->user()?->hasRole('super_admin')) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User $subject */
        $subject = $this->route('user');

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($subject->id)],
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
            'password' => ['nullable', 'string', 'min:10', 'confirmed'],
            'status' => ['required', 'in:active,suspended'],
        ];
    }
}
