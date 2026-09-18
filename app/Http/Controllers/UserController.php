<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of back-office staff members.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'status' => ['nullable', 'in:active,suspended'],
        ]);
        $search = trim((string) ($filters['search'] ?? ''));

        $users = User::query()
            ->with('roles')
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'customer'))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when(isset($filters['role']), fn ($q) => $q->whereHas('roles', fn ($q) => $q->where('name', $filters['role'])))
            ->when(isset($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $roles = Role::query()->whereNotIn('name', ['customer'])->orderBy('name')->get();

        return view('users.index', compact('users', 'filters', 'roles'));
    }

    /**
     * Show the form for creating a new staff user.
     */
    public function create(): View
    {
        $roles = Role::query()->whereNotIn('name', ['customer'])->orderBy('name')->get();

        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created staff user.
     *
     * Generates a random initial password that the admin must communicate
     * to the new user, who should enable 2FA on first login.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $temporaryPassword = Str::password(16);

            $user = User::query()->create([
                'first_name' => $request->validated('first_name'),
                'last_name' => $request->validated('last_name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'password' => Hash::make($temporaryPassword),
                'status' => 'active',
            ]);

            $role = Role::query()->findOrFail($request->validated('role_id'));
            $user->roles()->attach($role);

            $this->audit($request, 'user.created', $user, null, [
                'email' => $user->email,
                'role' => $role->name,
            ]);

            // Store the temporary password in the session so the admin can
            // communicate it to the new user. It is never persisted anywhere else.
            session()->flash('temporary_password', $temporaryPassword);

            return $user;
        });

        return redirect()->route('users.show', $user)->with('status', __('Utilisateur créé.'));
    }

    /**
     * Display the specified staff user profile.
     */
    public function show(User $user): View
    {
        $user->load('roles');
        $auditEntries = AuditLog::query()
            ->where('entity_type', User::class)
            ->where('entity_id', $user->id)
            ->latest()
            ->take(20)
            ->get();

        return view('users.show', compact('user', 'auditEntries'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $user->load('roles');
        $roles = Role::query()->whereNotIn('name', ['customer'])->orderBy('name')->get();

        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update profile information and role assignment.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user): void {
            $oldValues = $user->only(['first_name', 'last_name', 'email', 'phone', 'status']);
            $oldRole = $user->roles()->whereNotIn('name', ['customer'])->first()?->name;

            $user->update($request->safe()->except(['role_id', 'password']));

            if ($request->filled('password')) {
                $user->update(['password' => Hash::make($request->validated('password'))]);
            }

            $role = Role::query()->findOrFail($request->validated('role_id'));
            // Detach back-office roles only; preserve portal customer role if present.
            $backOfficeRoleIds = Role::query()->whereNotIn('name', ['customer'])->pluck('id');
            $user->roles()->detach($backOfficeRoleIds);
            $user->roles()->attach($role);

            $this->audit($request, 'user.updated', $user, array_merge($oldValues, ['role' => $oldRole]), array_merge(
                $user->only(['first_name', 'last_name', 'email', 'phone', 'status']),
                ['role' => $role->name],
            ));
        });

        return redirect()->route('users.show', $user)->with('status', __('Utilisateur mis à jour.'));
    }

    /**
     * Toggle the user's active / suspended status.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        abort_if($user->hasRole('super_admin') && ! $request->user()?->hasRole('super_admin'), 403);

        $old = $user->status;
        $new = $old === 'active' ? 'suspended' : 'active';

        DB::transaction(function () use ($request, $user, $old, $new): void {
            $user->update(['status' => $new]);
            $this->audit($request, 'user.status_changed', $user, ['status' => $old], ['status' => $new]);
        });

        $label = $new === 'suspended' ? 'Utilisateur suspendu.' : 'Utilisateur réactivé.';

        return redirect()->route('users.show', $user)->with('status', __($label));
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function audit(Request $request, string $action, User $subject, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => User::class,
            'entity_id' => $subject->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
