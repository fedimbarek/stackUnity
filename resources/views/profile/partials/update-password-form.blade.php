<section>
    <h6 class="m-0 font-weight-bold text-primary mb-3">Mettre à jour le mot de passe</h6>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="form-group">
            <label>Mot de passe actuel</label>
            <input name="current_password" type="password"
                   class="form-control @error('current_password', 'updatePassword') is-invalid @enderror">
            @error('current_password', 'updatePassword') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Nouveau mot de passe</label>
            <input name="password" type="password"
                   class="form-control @error('password', 'updatePassword') is-invalid @enderror">
            @error('password', 'updatePassword') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label>Confirmer le mot de passe</label>
            <input name="password_confirmation" type="password" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        @if (session('status') === 'password-updated')
            <span class="text-success small ml-2">Enregistré.</span>
        @endif
    </form>
</section>