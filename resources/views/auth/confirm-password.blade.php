<x-guest-layout>
    <div class="card o-hidden border-0 shadow-lg my-5">
        <div class="card-body p-5">
            <div class="mb-4 small text-gray-600">Zone sécurisée : confirme ton mot de passe avant de continuer.</div>

            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf
                <div class="form-group">
                    <input type="password" name="password"
                           class="form-control form-control-user @error('password') is-invalid @enderror"
                           placeholder="Mot de passe" required autofocus>
                    @error('password') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="btn btn-primary btn-user btn-block">Confirmer</button>
            </form>
        </div>
    </div>
</x-guest-layout>