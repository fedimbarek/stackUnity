<x-auth-split-layout>
    <style>
        .tf-wrapper {
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
            background: linear-gradient(135deg, #4b2e83 0%, #7b2ff7 55%, #b345f1 100%);
        }
        .tf-card {
            width: 100%; max-width: 380px; background: #fff; border-radius: 30px; padding: 40px 34px;
            box-shadow: 0 25px 60px rgba(30,10,60,0.35);
        }
        .tf-card h2 { font-weight: 800; font-size: 1.2rem; color: #2d2140; letter-spacing: 1px; margin-bottom: 6px; }
        .tf-underline { width: 42px; height: 4px; border-radius: 4px; background: linear-gradient(90deg, #7b2ff7, #f637ec); margin-bottom: 20px; }
        .tf-card p.hint { font-size: 0.82rem; color: #6b6280; margin-bottom: 18px; }
        .tf-card label { font-size: 0.75rem; color: #6b6280; margin-bottom: 2px; }
        .tf-card input {
            width: 100%; border: none; border-bottom: 1px solid #e3ddf0; padding: 8px 4px;
            font-size: 0.95rem; outline: none; margin-bottom: 14px; color: #2d2140;
        }
        .tf-card input:focus { border-bottom-color: #7b2ff7; }
        .tf-error { display: block; font-size: 0.75rem; color: #e3342f; margin: -8px 0 12px; }
        .tf-or { text-align: center; font-size: 0.75rem; color: #8a80a0; margin: 4px 0 10px; }
        .tf-btn {
            display: block; width: 100%; border: none; cursor: pointer; padding: 11px; border-radius: 30px;
            color: #fff; font-weight: 700; letter-spacing: 1px; font-size: 0.8rem;
            background: linear-gradient(90deg, #6a3ad1, #b03aef); box-shadow: 0 10px 25px rgba(123,47,247,0.35);
        }
        .tf-back { display: block; text-align: center; margin-top: 16px; font-size: 0.75rem; color: #7b2ff7; text-decoration: none; }
    </style>

    <div class="tf-wrapper">
        <div class="tf-card">
            <h2>VÉRIFICATION EN 2 ÉTAPES</h2>
            <div class="tf-underline"></div>
            <p class="hint">Saisis le code à 6 chiffres de ton application d'authentification, ou un code de récupération.</p>

            <form method="POST" action="{{ url('two-factor-challenge') }}">
                @csrf

                <label for="code">Code de l'application</label>
                <input id="code" name="code" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" autofocus>
                @error('code') <span class="tf-error">{{ $message }}</span> @enderror

                <div class="tf-or">ou</div>

                <label for="recovery_code">Code de récupération</label>
                <input id="recovery_code" name="recovery_code" type="text" autocomplete="off">

                <button type="submit" class="tf-btn">VALIDER</button>
            </form>

            <a href="{{ route('login') }}" class="tf-back">Retour à la connexion</a>
        </div>
    </div>
</x-auth-split-layout>
