<x-layouts.guest title="Sécurité du compte">
    @if (session('recovery_codes'))
        <p class="auth-form-copy">Conservez ces codes de récupération dans un lieu sûr.</p>
        <ul class="auth-recovery-codes">
            @foreach (session('recovery_codes') as $code)<li>{{ $code }}</li>@endforeach
        </ul>
    @elseif (auth()->user()->hasTwoFactorAuthenticationEnabled())
        <div class="auth-notice auth-notice--success" role="status">La double authentification est active.</div>
        <form method="POST" action="{{ route('two-factor.disable') }}" class="auth-form">
            @csrf
            @method('DELETE')
            <x-auth-input name="password" label="Mot de passe actuel" type="password" autocomplete="current-password" required />
            <button type="submit" class="btn btn-danger auth-submit">Désactiver la 2FA</button>
        </form>
    @else
        <p class="auth-form-copy">Ajoutez cette clé dans votre application d’authentification.</p>
        <code class="auth-secret">{{ $secret }}</code>
        <form method="POST" action="{{ route('two-factor.enable') }}" class="auth-form">
            @csrf
            <x-auth-input name="code" label="Code de confirmation" autocomplete="one-time-code" inputmode="numeric" required />
            <button type="submit" class="btn btn-primary auth-submit">Activer la 2FA</button>
        </form>
    @endif
</x-layouts.guest>
