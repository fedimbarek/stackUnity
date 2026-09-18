<section>
    <h6 class="m-0 font-weight-bold text-primary mb-3">Informations du profil</h6>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="form-group">
            <label for="name">Nom complet</label>
            <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', $user->name) }}" required autofocus>
            @error('name') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email', $user->email) }}" required>
            @error('email') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="small mt-2">
                    Ton adresse email n'est pas vérifiée.
                    <button form="send-verification" class="btn btn-link p-0 small">Renvoyer l'email.</button>
                </p>
                @if (session('status') === 'verification-link-sent')
                    <p class="small text-success">Un nouveau lien a été envoyé.</p>
                @endif
            @endif
        </div>

        <div class="form-group">
            <label for="neighborhood_id">Quartier / Résidence</label>
            <select id="neighborhood_id" name="neighborhood_id" class="form-control @error('neighborhood_id') is-invalid @enderror" required>
                @foreach ($neighborhoods as $n)
                    <option value="{{ $n->id }}" @selected(old('neighborhood_id', $user->neighborhood_id) == $n->id)>{{ $n->name }} ({{ $n->city }})</option>
                @endforeach
            </select>
            @error('neighborhood_id') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
        </div>

        <div class="form-group">
            <label for="phone">Téléphone</label>
            <input id="phone" name="phone" type="text" class="form-control @error('phone') is-invalid @enderror"
                   value="{{ old('phone', $user->phone) }}">
            @error('phone') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
        @if (session('status') === 'profile-updated')
            <span class="text-success small ml-2">Enregistré.</span>
        @endif
    </form>
</section>