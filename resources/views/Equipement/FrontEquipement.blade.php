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

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h3 class="h5 card-title">Que me conseillez-vous&nbsp;?</h3>
                <p class="text-muted">Décrivez votre situation pour recevoir des recommandations adaptées à la météo et aux équipements disponibles.</p>
                <form id="equipement-advisor-form"
                      action="{{ route('front.equipements.recommander') }}"
                      method="POST"
                      data-url="{{ route('front.equipements.recommander') }}">
                    @csrf
                    <label for="equipement-besoin" class="form-label">Votre besoin</label>
                    <textarea id="equipement-besoin"
                              name="besoin"
                              class="form-control"
                              rows="3"
                              minlength="5"
                              maxlength="500"
                              required
                              placeholder="Ex. famille de 5 personnes, coupure d’eau demain, 42 °C"></textarea>
                    <button id="equipement-advisor-submit" type="submit" class="btn btn-primary mt-3">
                        Obtenir une recommandation
                    </button>
                </form>
                <div id="equipement-advisor-error" class="alert alert-danger mt-3 mb-0" role="alert" hidden></div>
                <div id="equipement-advisor-results" class="mt-4" aria-live="polite"></div>
            </div>
        </div>

        @forelse ($equipements as $equipement)
            @if ($loop->first)
                <div class="row g-4">
            @endif

            <div class="col-md-6 col-lg-4">
                <article id="equipement-card-{{ $equipement->id }}" class="card h-100 shadow-sm">
                    @if ($equipement->image)
                        <a href="{{ asset('storage/' . $equipement->image) }}"
                           target="_blank"
                           rel="noopener"
                           aria-label="Voir l’image de {{ $equipement->nom }} en grand">
                            <img
                                src="{{ asset('storage/' . $equipement->image) }}"
                                class="card-img-top"
                                alt="{{ $equipement->nom }}"
                                title="Cliquer pour voir l’image en grand"
                                style="height: 220px; object-fit: cover;"
                            >
                            <span class="d-block text-center small py-1">Voir l’image en grand</span>
                        </a>
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
                        <details id="reservation-form-{{ $equipement->id }}" class="card-body pt-0" @if ($errors->any()) open @endif>
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

const advisorForm = document.getElementById('equipement-advisor-form');

advisorForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const submitButton = document.getElementById('equipement-advisor-submit');
    const errorBox = document.getElementById('equipement-advisor-error');
    const results = document.getElementById('equipement-advisor-results');
    const originalLabel = 'Obtenir une recommandation';

    errorBox.hidden = true;
    errorBox.textContent = '';
    results.replaceChildren();
    submitButton.disabled = true;
    submitButton.textContent = 'Analyse en cours…';

    try {
        const formData = new FormData(advisorForm);
        const response = await fetch(advisorForm.dataset.url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': formData.get('_token'),
            },
            body: formData,
        });
        const data = await response.json();

        if (!response.ok) {
            const firstValidationError = data.errors
                ? Object.values(data.errors)[0]?.[0]
                : null;
            throw new Error(firstValidationError || data.message || 'Impossible d’obtenir une recommandation.');
        }

        const heading = document.createElement('h4');
        heading.className = 'h6';
        heading.textContent = data.source === 'ia'
            ? 'Recommandations de l’assistant IA'
            : 'Recommandations par règles de prévention';
        results.appendChild(heading);

        if (data.conseil) {
            const advice = document.createElement('p');
            advice.className = 'text-muted';
            advice.textContent = data.conseil;
            results.appendChild(advice);
        }

        if (!data.recommandations || data.recommandations.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'mb-0';
            empty.textContent = 'Aucun équipement disponible ne correspond à ce besoin pour le moment.';
            results.appendChild(empty);
            return;
        }

        const row = document.createElement('div');
        row.className = 'row g-3';

        data.recommandations.forEach((item) => {
            const column = document.createElement('div');
            column.className = 'col-md-6 col-lg-4';

            const card = document.createElement('article');
            card.className = 'card h-100';

            if (item.image) {
                const image = document.createElement('img');
                image.src = item.image;
                image.alt = item.nom;
                image.className = 'card-img-top';
                image.style.height = '180px';
                image.style.objectFit = 'cover';
                card.appendChild(image);
            }

            const body = document.createElement('div');
            body.className = 'card-body';

            const name = document.createElement('h5');
            name.className = 'card-title h6';
            name.textContent = item.nom;

            const type = document.createElement('p');
            type.className = 'card-text text-muted mb-1';
            type.textContent = item.type_equipement;

            const price = document.createElement('p');
            price.className = 'fw-bold';
            price.textContent = `${Number(item.prix_louer).toFixed(2)} DT`;

            const reason = document.createElement('p');
            reason.className = 'card-text';
            reason.textContent = item.raison;

            const reserve = document.createElement('a');
            reserve.className = 'btn btn-primary';
            reserve.href = item.url;
            reserve.textContent = 'Réserver';
            reserve.dataset.equipementCard = `equipement-card-${item.id}`;

            body.append(name, type, price, reason, reserve);
            card.appendChild(body);
            column.appendChild(card);
            row.appendChild(column);
        });

        results.appendChild(row);
    } catch (error) {
        errorBox.textContent = error.message || 'Une erreur est survenue. Veuillez réessayer.';
        errorBox.hidden = false;
    } finally {
        submitButton.disabled = false;
        submitButton.textContent = originalLabel;
    }
});

document.getElementById('equipement-advisor-results').addEventListener('click', (event) => {
    const link = event.target.closest('[data-equipement-card]');

    if (!link) {
        return;
    }

    const target = document.getElementById(link.dataset.equipementCard);

    if (target) {
        event.preventDefault();
        const reservationForm = target.querySelector('details');
        if (reservationForm) {
            reservationForm.open = true;
            reservationForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        window.history.replaceState(null, '', link.href);
    }
});
</script>
@endsection
