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
}" class="mb-4 rounded-3 border border-warning bg-amber-50 p-4 text-dark shadow-sm">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom border-warning pb-3">
        <div class="d-flex align-items-center gap-3">
            <span class="rounded-3 bg-warning p-2 text-dark fs-4 d-inline-flex align-items-center justify-content-center">
                <i class="bi bi-key-fill"></i>
            </span>
            <div>
                <h3 class="h6 font-bold mb-1 text-dark">Compte Utilisateur & Identifiants Générés</h3>
                <p class="small text-muted mb-0">
                    Voici la fiche d'accès au format Markdown à transmettre à l'utilisateur.
                </p>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <button @click="copyMarkdown()" type="button" class="btn btn-sm btn-app-primary resource-button">
                <i class="bi bi-clipboard me-1" x-show="!copied"></i>
                <i class="bi bi-check-lg me-1 text-success" x-show="copied"></i>
                <span x-text="copied ? 'Copié !' : 'Copier le Markdown'"></span>
            </button>
            <button @click="downloadMarkdown()" type="button" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-download me-1"></i> Télécharger (.md)
            </button>
        </div>
    </div>

    <div class="mt-3">
        <p class="text-uppercase text-muted font-bold" style="font-size: 0.72rem; letter-spacing: 0.08em;">Aperçu du Document Markdown :</p>
        <pre class="rounded-3 bg-dark p-3 font-monospace text-warning text-wrap mb-0" style="max-height: 240px; overflow-y: auto; font-size: 0.8rem;">{{ $markdownContent }}</pre>
    </div>
</div>
@endif
