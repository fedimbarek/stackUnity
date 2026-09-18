<x-guest-layout>
    <div class="card o-hidden border-0 shadow-lg my-5">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <h1 class="h4 text-gray-900">Mot de passe oublié ?</h1>
                <p class="small text-gray-600">Indique ton email, on t'envoie un lien de réinitialisation.</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="form-group">
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="form-control form-control-user @error('email') is-invalid @enderror"
                           placeholder="Adresse email" required autofocus>
                    @error('email') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="btn btn-primary btn-user btn-block">Envoyer le lien</button>
            </form>
            <hr>
            <div class="text-center"><a class="small" href="{{ route('login') }}">Retour à la connexion</a></div>
        </div>
    </div>
</x-guest-layout>