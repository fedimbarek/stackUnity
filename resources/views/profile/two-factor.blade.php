<x-app-layout>
    <x-slot name="header">Authentification à deux facteurs</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            @if ($user->two_factor_confirmed_at)
                <p class="text-success font-weight-bold">La 2FA est activée.</p>

                <h6 class="font-weight-bold">Codes de récupération</h6>
                <p class="small text-muted">Garde-les en lieu sûr. Chaque code ne fonctionne qu'une seule fois.</p>
                <pre class="bg-light p-3">@foreach ($user->two_factor_recovery_codes ?? [] as $code){{ $code }}
@endforeach</pre>

                <form method="POST" action="{{ route('two-factor.recovery') }}" class="mb-4">
                    @csrf
                    <button class="btn btn-outline-secondary btn-sm">Régénérer les codes</button>
                </form>

                <hr>
                <form method="POST" action="{{ route('two-factor.disable') }}">
                    @csrf
                    @method('DELETE')
                    <div class="form-group">
                        <label for="password">Mot de passe (pour désactiver)</label>
                        <input id="password" name="password" type="password"
                               class="form-control @error('password') is-invalid @enderror" required>
                        @error('password') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                    </div>
                    <button class="btn btn-danger">Désactiver la 2FA</button>
                </form>

            @elseif ($qrSvg)
                <p>1. Scanne ce QR code avec Google Authenticator (ou Authy).</p>
                <div class="mb-3">{!! $qrSvg !!}</div>
                <p class="small">Ou saisis la clé manuellement : <code>{{ $user->two_factor_secret }}</code></p>

                <p>2. Saisis le code à 6 chiffres pour confirmer.</p>
                <form method="POST" action="{{ route('two-factor.confirm') }}">
                    @csrf
                    <div class="form-group">
                        <input name="code" type="text" inputmode="numeric" maxlength="6"
                               class="form-control @error('code') is-invalid @enderror" required autofocus>
                        @error('code') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                    </div>
                    <button class="btn btn-primary">Confirmer et activer</button>
                </form>

            @else
                <p>La 2FA ajoute une sécurité en plus de ton mot de passe.</p>
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <button class="btn btn-primary">Activer la 2FA</button>
                </form>
            @endif

        </div>
    </div>
</x-app-layout>
