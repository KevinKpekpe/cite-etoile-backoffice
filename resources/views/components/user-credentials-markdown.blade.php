@if(session('user_credentials_markdown'))
    @php
        $lines = explode("\n", session('user_credentials_markdown'));
        $email = '';
        $password = '';
        $name = '';
        $role = '';
        foreach ($lines as $line) {
            if (str_starts_with($line, 'Nom complet')) { $name = trim(explode(':', $line, 2)[1] ?? ''); }
            if (str_starts_with($line, 'Email')) { $email = trim(explode(':', $line, 2)[1] ?? ''); }
            if (str_starts_with($line, 'Mot de passe')) { $password = trim(explode(':', $line, 2)[1] ?? ''); }
            if (str_starts_with($line, 'Role')) { $role = trim(explode(':', $line, 2)[1] ?? ''); }
        }
    @endphp

    <section class="credentials-panel" data-credentials-panel>
        <div class="credentials-panel__header">
            <div class="credentials-panel__title">
                <span class="credentials-panel__icon" aria-hidden="true"><i class="bi bi-shield-lock-fill"></i></span>
                <div>
                    <h2>Identifiants générés</h2>
                    <p>À transmettre au client. Copiez ou téléchargez la fiche.</p>
                </div>
            </div>
            <div class="credentials-panel__actions">
                <button type="button" class="btn btn-sm btn-app-primary" data-copy-credentials>
                    <i class="bi bi-clipboard" aria-hidden="true"></i><span>{{ __("Copier") }}</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-download-credentials data-filename="acces-{{ now()->format('YmdHis') }}.txt">
                    <i class="bi bi-download" aria-hidden="true"></i><span>{{ __("Télécharger") }}</span>
                </button>
            </div>
        </div>

        <div class="credentials-panel__body">
            <dl class="credentials-panel__grid">
                @if($name)
                    <div><dt>{{ __("Nom") }}</dt><dd>{{ $name }}</dd></div>
                @endif
                @if($email)
                    <div><dt>Identifiant</dt><dd><code>{{ $email }}</code></dd></div>
                @endif
                @if($password)
                    <div><dt>{{ __("Mot de passe") }}</dt><dd><code class="credentials-panel__password">{{ $password }}</code></dd></div>
                @endif
                @if($role)
                    <div><dt>{{ __("Rôle") }}</dt><dd>{{ $role }}</dd></div>
                @endif
            </dl>
        </div>

        {{-- Hidden content used by copy/download JS --}}
        <pre data-credentials-content style="display:none;">{{ session('user_credentials_markdown') }}</pre>
    </section>
@endif
