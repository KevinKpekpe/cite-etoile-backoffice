<x-layouts.app title="Nouvel utilisateur">
<div class="mx-auto max-w-2xl">
    <h1 class="mb-6 text-3xl font-bold">Nouvel utilisateur</h1>
    <form method="POST" action="{{ route('users.store') }}" class="rounded-xl bg-white p-6 shadow-sm">
        @csrf
        <x-user-form :roles="$roles" />
        <div class="mt-6 flex justify-end">
            <button class="rounded-lg bg-slate-950 px-5 py-3 font-semibold text-white">Créer l'utilisateur</button>
        </div>
    </form>
</div>
</x-layouts.app>
