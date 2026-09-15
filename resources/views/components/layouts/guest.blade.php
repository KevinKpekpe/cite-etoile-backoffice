<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Cité Étoile du Monde' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-900 antialiased">
    <main class="flex min-h-screen items-center justify-center px-4 py-10">
        <section class="w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl shadow-black/30">
            <div class="flex flex-col gap-2 pb-6 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.3em] text-amber-600">MJIC Immobilier SARL</p>
                <h1 class="text-2xl font-bold text-slate-950">Cité Étoile du Monde</h1>
            </div>
            @if (session('status'))
                <p class="mb-5 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</p>
            @endif
            {{ $slot }}
        </section>
    </main>
</body>
</html>
