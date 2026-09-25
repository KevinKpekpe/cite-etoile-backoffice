<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        abort_if($request->user()->hasRole('customer'), 403);

        return view('profile.edit', ['user' => $request->user()->load('roles')]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $values = $request->safe()->except(['avatar', 'remove_avatar']);
        $oldValues = $user->only(array_keys($values));

        if ($request->boolean('remove_avatar') && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $values['avatar_path'] = null;
        } elseif ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $values['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        DB::transaction(function () use ($request, $user, $values, $oldValues): void {
            $user->update($values);

            AuditLog::query()->create([
                'user_id' => $user->id,
                'action' => 'profile.updated',
                'entity_type' => User::class,
                'entity_id' => $user->id,
                'old_values' => $oldValues,
                'new_values' => $user->fresh()->only(array_keys($values)),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return back()->with('status', __('Profil mis à jour.'));
    }
}
