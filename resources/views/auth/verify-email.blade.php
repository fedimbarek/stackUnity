<x-guest-layout>
    <div class="card o-hidden border-0 shadow-lg my-5">
        <div class="card-body p-5">
            <div class="mb-4 small text-gray-600">
                Merci de ton inscription ! Vérifie ton adresse email en cliquant sur le lien qu'on vient de t'envoyer.
                Si tu ne l'as pas reçu, on peut t'en renvoyer un.
            </div>

            @if (session('status') == 'verification-link-sent')
                <div class="alert alert-success">Un nouveau lien de vérification a été envoyé.</div>
            @endif

            <div class="d-flex align-items-center justify-content-between">
                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-user">Renvoyer l'email</button>
                </form>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-link small text-gray-600">Déconnexion</button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>