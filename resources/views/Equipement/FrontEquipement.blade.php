@extends('layouts.front')
@section('title', 'Équipements')

@section('content')
<header class="masthead" style="min-height: 40vh;">
    <div class="container">
        <h1 style="font-size: 2.2rem;">Équipements</h1>
        <p>Découvrez les équipements disponibles pour les résidents.</p>
    </div>
</header>

<section class="section-light">
    <div class="container">
        <h2>Équipements disponibles</h2>

        @if (session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <p class="mb-1">Vérifiez les informations de réservation :</p>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @forelse ($equipements as $equipement)
            @if ($loop->first)
                <div class="row g-4">
            @endif

            <div class="col-md-6 col-lg-4">
                <article class="card h-100 shadow-sm">
                    @if ($equipement->image)
                        <img
                            src="{{ asset('storage/' . $equipement->image) }}"
                            class="card-img-top"
                            alt="{{ $equipement->nom }}"
                            style="height: 220px; object-fit: cover;"
                        >
                    @else
                        <div class="d-flex align-items-center justify-content-center bg-light text-muted" style="height: 220px;">
                            Aucune image disponible
                        </div>
                    @endif

                   <div class="card-body" data-translate-card>
    <h3 class="card-title h5" data-field="nom">{{ $equipement->nom }}</h3>
    <p class="card-text text-muted" data-field="type">{{ $equipement->type_equipement }}</p>
    <p class="mb-1 fw-bold">{{ number_format($equipement->prix_louer, 2) }} DT</p>
    <small class="text-muted d-block">
        Ajouté le {{ \Illuminate\Support\Carbon::parse($equipement->date_ajout)->format('d/m/Y') }}
    </small>

    <button type="button"
            class="btn btn-outline-secondary btn-sm mt-2 js-translate"
            data-url="{{ route('api.equipements.translate', $equipement) }}">
        Traduire en anglais
    </button>
</div>
                    @if ($equipement->etat === 'reserve')
                        <div class="card-body pt-0">
                            <button type="button" class="btn btn-secondary" disabled aria-disabled="true">
                                Déjà réservé
                            </button>
                        </div>
                    @else
                        <details class="card-body pt-0" @if ($errors->any()) open @endif>
                            <summary class="btn btn-primary">Réserver</summary>
                            <form action="{{ route('front.equipements.reservations.store', $equipement) }}" method="POST" class="mt-3">
                                @csrf

                                <div class="mb-2">
                                    <label for="nom-{{ $equipement->id }}" class="form-label">Nom</label>
                                    <input id="nom-{{ $equipement->id }}" name="nom" type="text" class="form-control" value="{{ old('nom') }}" maxlength="255" required>
                                </div>
                                <div class="mb-2">
                                    <label for="prenom-{{ $equipement->id }}" class="form-label">Prénom</label>
                                    <input id="prenom-{{ $equipement->id }}" name="prenom" type="text" class="form-control" value="{{ old('prenom') }}" maxlength="255" required>
                                </div>
                                <div class="mb-2">
                                    <label for="email-{{ $equipement->id }}" class="form-label">E-mail</label>
                                    <input id="email-{{ $equipement->id }}" name="email" type="email" class="form-control" value="{{ old('email') }}" maxlength="255" required>
                                </div>
                                <div class="mb-2">
                                    <label for="numero-{{ $equipement->id }}" class="form-label">Numéro de téléphone</label>
                                    <input id="numero-{{ $equipement->id }}" name="numero" type="tel" class="form-control" value="{{ old('numero') }}" maxlength="30" required>
                                </div>
                                <div class="mb-2">
                                    <label for="date-debut-{{ $equipement->id }}" class="form-label">Date de début</label>
                                    <input id="date-debut-{{ $equipement->id }}" name="date_debut" type="date" class="form-control" value="{{ old('date_debut') }}" min="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="date-fin-{{ $equipement->id }}" class="form-label">Date de fin</label>
                                    <input id="date-fin-{{ $equipement->id }}" name="date_fin" type="date" class="form-control" value="{{ old('date_fin') }}" min="{{ old('date_debut', now()->toDateString()) }}" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Envoyer la demande</button>
                            </form>
                        </details>
                    @endif
                </article>
            </div>

            @if ($loop->last)
                </div>
            @endif
        @empty
            <p class="text-center text-muted mb-0">Aucun équipement n’est disponible pour le moment.</p>
        @endforelse
    </div>
</section>

<script>
document.querySelectorAll('.js-translate').forEach((btn) => {
    const card = btn.closest('[data-translate-card]');
    const nom  = card.querySelector('[data-field="nom"]');
    const type = card.querySelector('[data-field="type"]');
    const original = { nom: nom.textContent, type: type.textContent };
    const labelTranslate = 'Traduire en anglais';
    const labelOriginal  = "Voir l'original";

    let translated = null;
    let showingTranslation = false;

    btn.addEventListener('click', async () => {
        if (showingTranslation) {
            nom.textContent  = original.nom;
            type.textContent = original.type;
            btn.textContent  = labelTranslate;
            showingTranslation = false;
            return;
        }

        if (!translated) {
            btn.disabled = true;
            btn.textContent = 'Traduction…';
            try {
                const res = await fetch(btn.dataset.url + '?lang=en', {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                translated = await res.json();
            } catch (e) {
                btn.textContent = 'Échec, réessayer';
                btn.disabled = false;
                return;
            }
            btn.disabled = false;
        }

        nom.textContent  = translated.nom;
        type.textContent = translated.type_equipement;
        btn.textContent  = labelOriginal;
        showingTranslation = true;
    });
});
</script>
@endsection
