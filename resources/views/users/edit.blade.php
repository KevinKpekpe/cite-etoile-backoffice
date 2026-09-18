<x-layouts.app :title="'Modifier · '.$user->first_name.' '.$user->last_name">
<div class="mx-auto max-w-2xl">
    <h1 class="mb-6 text-3xl font-bold">Modifier l'utilisateur</h1>
    <form method="POST" action="{{ route('users.update', $user) }}" class="rounded-xl bg-white p-6 shadow-sm">
        @csrf @method('PUT')
        <x-user-form :roles="$roles" :user="$user" />
        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('users.show', $user) }}" class="rounded-lg border px-5 py-3">Annuler</a>
            <button class="rounded-lg bg-slate-950 px-5 py-3 font-semibold text-white">Enregistrer</button>
        </div>
    </form>
</div>
</x-layouts.app>
