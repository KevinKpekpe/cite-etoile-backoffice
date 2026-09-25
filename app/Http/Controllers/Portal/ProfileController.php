<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UpdateProfilePasswordRequest;
use App\Http\Requests\Portal\UpdateProfileRequest;
use App\Models\AuditLog;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('portal.profile.edit', ['customer' => $request->user()->customer()->firstOrFail()]);
    }

    public function updatePassword(UpdateProfilePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update(['password' => Hash::make($request->validated('password'))]);

        AuditLog::query()->create([
            'user_id' => $user->id,
            'action' => 'portal.profile.password_changed',
            'entity_type' => $user::class,
            'entity_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('status', __('Mot de passe mis à jour.'));
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $customer = $request->user()->customer()->firstOrFail();
        $user = $request->user();
        $values = $request->safe()->except(['avatar', 'remove_avatar']);
        $oldValues = $customer->only(array_keys($values));

        if ($request->boolean('remove_avatar')) {
            if ($customer->avatar_path) {
                Storage::disk('public')->delete($customer->avatar_path);
            }
            if ($user->avatar_path && $user->avatar_path !== $customer->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $values['avatar_path'] = null;
        } elseif ($request->hasFile('avatar')) {
            if ($customer->avatar_path) {
                Storage::disk('public')->delete($customer->avatar_path);
            }
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $values['avatar_path'] = $avatarPath;
        }

        DB::transaction(function () use ($request, $customer, $user, $values, $oldValues): void {
            $customer->update($values);

            $userUpdateData = [
                'first_name' => $values['first_name'],
                'last_name' => $values['last_name'],
                'phone' => $values['phone'],
                'email' => $values['email'] ?? $user->email,
            ];

            if (array_key_exists('avatar_path', $values)) {
                $userUpdateData['avatar_path'] = $values['avatar_path'];
            }

            $user->update($userUpdateData);

            AuditLog::query()->create([
                'user_id' => $user->id,
                'action' => 'portal.profile.updated',
                'entity_type' => Customer::class,
                'entity_id' => $customer->id,
                'old_values' => $oldValues,
                'new_values' => $customer->fresh()->only(array_keys($values)),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return back()->with('status', __('Informations personnelles mises à jour.'));
    }
}
