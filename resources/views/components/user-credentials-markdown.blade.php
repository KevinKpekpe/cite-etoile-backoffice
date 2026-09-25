@if(session('user_credentials_markdown'))
    <section class="credentials-panel" data-credentials-panel>
        <div class="credentials-panel__header">
            <div class="credentials-panel__title">
                <span class="credentials-panel__icon" aria-hidden="true"><i class="bi bi-key-fill"></i></span>
                <div>
                    <h2>Identifiants générés</h2>
                    <p>Fiche d'accès à transmettre à l'utilisateur.</p>
                </div>
            </div>

            <div class="credentials-panel__actions">
                <button type="button" class="btn btn-sm btn-app-primary" data-copy-credentials>
                    <i class="bi bi-clipboard" aria-hidden="true"></i><span>Copier</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-download-credentials data-filename="identifiants-acces-{{ now()->format('YmdHis') }}.md">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Télécharger</span>
                </button>
            </div>
        </div>

        <div class="credentials-panel__body">
            <span class="credentials-panel__label">Aperçu Markdown</span>
            <pre data-credentials-content>{{ session('user_credentials_markdown') }}</pre>
        </div>
    </section>
@endif
