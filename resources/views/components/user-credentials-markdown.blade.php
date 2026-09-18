@if(session('user_credentials_markdown'))
@php
    $markdownContent = session('user_credentials_markdown');
    $tempPassword = session('temporary_password');
@endphp
<div x-data="{
    copied: false,
    copyMarkdown() {
        navigator.clipboard.writeText({{ json_encode($markdownContent) }});
        this.copied = true;
        setTimeout(() => this.copied = false, 2500);
    },
    downloadMarkdown() {
        const blob = new Blob([{{ json_encode($markdownContent) }}], { type: 'text/markdown;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'identifiants-acces-{{ now()->format('YmdHis') }}.md';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
}" class="mb-6 rounded-2xl border border-amber-300 bg-amber-50/80 p-6 text-slate-900 shadow-md">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-amber-200/80 pb-4">
        <div class="flex items-center gap-3">
            <span class="rounded-xl bg-amber-500 p-2 text-xl text-white">🔑</span>
            <div>
                <h3 class="font-bold text-lg text-amber-950">Compte Utilisateur & Identifiants Générés</h3>
                <p class="text-sm text-amber-800">
                    Voici la fiche d'accès au format **Markdown** à transmettre au compte créé.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <button @click="copyMarkdown()" type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-amber-700 transition">
                <span x-text="copied ? '✅ Copié dans le presse-papier !' : '📋 Copier le Markdown'"></span>
            </button>
            <button @click="downloadMarkdown()" type="button"
                    class="inline-flex items-center gap-2 rounded-xl border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-900 shadow-sm hover:bg-amber-100 transition">
                📥 Télécharger (.md)
            </button>
        </div>
    </div>

    <div class="mt-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-amber-900">Aperçu du Document Markdown :</p>
        <pre class="max-h-72 overflow-y-auto rounded-xl bg-slate-900 p-4 font-mono text-sm text-amber-300 whitespace-pre-wrap selection:bg-amber-500 selection:text-slate-900">{{ $markdownContent }}</pre>
    </div>
</div>
@endif
