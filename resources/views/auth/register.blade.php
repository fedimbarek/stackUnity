<x-guest-layout>
    <div class="card o-hidden border-0 shadow-lg my-5">
        <div class="card-body p-0">
            <div class="row">
                <div class="col-lg-5 d-none d-lg-block bg-register-image"></div>
                <div class="col-lg-7">
                    <div class="p-5">
                        <div class="text-center"><h1 class="h4 text-gray-900 mb-4">Créer un compte !</h1></div>

                        <form class="user" method="POST" action="{{ route('register') }}">
                            @csrf
                            <div class="form-group">
                                <input type="text" name="name" value="{{ old('name') }}"
                                       class="form-control form-control-user @error('name') is-invalid @enderror"
                                       placeholder="Nom complet" required autofocus>
                                @error('name') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                            <div class="form-group">
                                <input type="email" name="email" value="{{ old('email') }}"
                                       class="form-control form-control-user @error('email') is-invalid @enderror"
                                       placeholder="Adresse email" required>
                                @error('email') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                            <div class="form-group">
                                <select name="neighborhood_id" class="form-control form-control-user @error('neighborhood_id') is-invalid @enderror" required>
                                    <option value="">-- Choisir ton quartier --</option>
                                    @foreach ($neighborhoods as $n)
                                        <option value="{{ $n->id }}" @selected(old('neighborhood_id') == $n->id)>{{ $n->name }} ({{ $n->city }})</option>
                                    @endforeach
                                </select>
                                @error('neighborhood_id') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                            </div>
                            <div class="form-group">
                                <input type="text" name="phone" value="{{ old('phone') }}" class="form-control form-control-user" placeholder="Téléphone (optionnel)">
                            </div>
                            <div class="form-group row">
                                <div class="col-sm-6 mb-3 mb-sm-0">
                                    <input type="password" name="password"
                                           class="form-control form-control-user @error('password') is-invalid @enderror"
                                           placeholder="Mot de passe" required>
                                    @error('password') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6">
                                    <input type="password" name="password_confirmation" class="form-control form-control-user" placeholder="Confirmer le mot de passe" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-user btn-block">S'inscrire</button>
                        </form>
                        <hr>
                        <div class="text-center"><a class="small" href="{{ route('login') }}">Déjà inscrit ? Connecte-toi !</a></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>