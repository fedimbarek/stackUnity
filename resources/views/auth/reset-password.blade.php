<x-guest-layout>
    <div class="card o-hidden border-0 shadow-lg my-5">
        <div class="card-body p-5">
            <div class="text-center mb-4"><h1 class="h4 text-gray-900">Réinitialiser le mot de passe</h1></div>

            <form method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="form-group">
                    <input type="email" name="email" value="{{ old('email', $request->email) }}"
                           class="form-control form-control-user @error('email') is-invalid @enderror"
                           placeholder="Adresse email" required autofocus>
                    @error('email') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <input type="password" name="password"
                           class="form-control form-control-user @error('password') is-invalid @enderror"
                           placeholder="Nouveau mot de passe" required>
                    @error('password') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <input type="password" name="password_confirmation" class="form-control form-control-user" placeholder="Confirmer le mot de passe" required>
                </div>
                <button type="submit" class="btn btn-primary btn-user btn-block">Réinitialiser</button>
            </form>
        </div>
    </div>
</x-guest-layout>