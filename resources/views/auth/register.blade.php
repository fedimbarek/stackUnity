<x-auth-split-layout>
    <style>
        .auth-wrapper {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #4b2e83 0%, #7b2ff7 55%, #b345f1 100%);
            position: relative; overflow: hidden; padding: 24px;
        }
        .auth-wrapper::before, .auth-wrapper::after {
            content: ""; position: absolute; border-radius: 50%; background: rgba(255,255,255,0.08);
        }
        .auth-wrapper::before { width: 260px; height: 260px; top: -60px; left: 10%; }
        .auth-wrapper::after  { width: 170px; height: 170px; bottom: 8%; left: 4%; background: rgba(255,255,255,0.12); }

        .auth-card { display: flex; width: 100%; max-width: 960px; min-height: 560px; position: relative; z-index: 2; }

        .auth-left {
            flex: 0 0 48%; background: #fff; display: flex; align-items: center; justify-content: center;
            border-radius: 200px 40px 200px 40px / 140px 40px 140px 40px;
            box-shadow: 0 25px 60px rgba(30,10,60,0.35); padding: 40px;
        }
        .auth-form-box { width: 100%; max-width: 320px; }
        .auth-form-box h2 { font-weight: 800; font-size: 1.4rem; color: #2d2140; letter-spacing: 1px; margin-bottom: 6px; }
        .auth-underline { width: 42px; height: 4px; border-radius: 4px; background: linear-gradient(90deg, #7b2ff7, #f637ec); margin-bottom: 22px; }

        .auth-field { position: relative; margin-bottom: 16px; }
        .auth-field i { position: absolute; left: 0; top: 10px; color: #b9a9d6; font-size: 0.9rem; }
        .auth-field input, .auth-field select {
            width: 100%; border: none; border-bottom: 1px solid #e3ddf0; appearance: none;
            padding: 8px 8px 8px 26px; font-size: 0.88rem; outline: none; background: transparent; color: #2d2140;
        }
        .auth-field input::placeholder { color: #b3a9c4; }
        .auth-field input:focus, .auth-field select:focus { border-bottom-color: #7b2ff7; }
        .auth-field .invalid-feedback { display: block; font-size: 0.72rem; color: #e3342f; margin-top: 4px; }
        .auth-row { display: flex; gap: 14px; }
        .auth-row .auth-field { flex: 1; }

        .auth-btn-gradient {
            display: block; width: 100%; text-align: center; border: none; cursor: pointer;
            padding: 11px; border-radius: 30px; color: #fff !important; font-weight: 700; letter-spacing: 1px;
            background: linear-gradient(90deg, #6a3ad1, #b03aef); box-shadow: 0 10px 25px rgba(123,47,247,0.35);
            font-size: 0.8rem; text-decoration: none; margin-top: 8px;
        }
        .auth-btn-gradient:hover { opacity: 0.9; color: #fff; }

        .auth-right {
            flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center; color: #fff; padding: 40px;
        }
        .auth-logo-shape {
            width: 44px; height: 58px; margin-bottom: 14px;
            border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
            background: linear-gradient(160deg, #b03aef, #ff6ec7);
        }
        .auth-right h3 { font-weight: 800; font-size: 1rem; letter-spacing: 3px; margin-bottom: 22px; }
        .auth-right h1 { font-weight: 800; font-size: 1.7rem; margin-bottom: 14px; }
        .auth-right p { max-width: 320px; font-size: 0.82rem; line-height: 1.6; color: #e4d9f7; margin-bottom: 24px; }

        @media (max-width: 860px) {
            .auth-card { flex-direction: column; }
            .auth-left { border-radius: 40px; }
            .auth-right { display: none; }
        }
    </style>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-right">
                <div class="auth-logo-shape"></div>
                <h3>HEATALERT</h3>
                <h1>Rejoins-nous !</h1>
                <p>Crée ton compte en moins d'une minute et reste informé des alertes canicule et des coupures de courant dans ton quartier.</p>
                <a href="{{ route('login') }}" class="auth-btn-gradient" style="max-width: 220px;">DÉJÀ INSCRIT ? CONNEXION</a>
            </div>

            <div class="auth-left">
                <div class="auth-form-box">
                    <h2>INSCRIPTION</h2>
                    <div class="auth-underline"></div>

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="auth-field">
                            <i class="fas fa-user"></i>
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Nom complet" required autofocus>
                            @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="auth-field">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="Adresse email" required>
                            @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="auth-field">
                            <i class="fas fa-map-marker-alt"></i>
                            <select name="neighborhood_id" required>
                                <option value="">-- Choisir ton quartier --</option>
                                @foreach ($neighborhoods as $n)
                                    <option value="{{ $n->id }}" @selected(old('neighborhood_id') == $n->id)>{{ $n->name }} ({{ $n->city }})</option>
                                @endforeach
                            </select>
                            @error('neighborhood_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="auth-field">
                            <i class="fas fa-phone"></i>
                            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Téléphone (optionnel)">
                            @error('phone') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>

                        <div class="auth-row">
                            <div class="auth-field">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password" placeholder="Mot de passe" required>
                                @error('password') <span class="invalid-feedback">{{ $message }}</span> @enderror
                            </div>
                            <div class="auth-field">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="password_confirmation" placeholder="Confirmer" required>
                            </div>
                        </div>

                        <button type="submit" class="auth-btn-gradient">CRÉER MON COMPTE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-auth-split-layout>