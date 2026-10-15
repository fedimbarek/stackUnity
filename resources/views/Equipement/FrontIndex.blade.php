@extends('layouts.front')

@section('title', 'Équipements')

@section('content')
    <section class="section-light" style="padding-top: 130px;">
        <div class="container">
            <h1 class="mb-5">Équipements disponibles</h1>

            @if (session('success'))
                <div class="alert alert-success" role="alert">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($equipements->isEmpty())
                <p class="text-muted">Aucun équipement n'est disponible pour le moment.</p>
            @else
                <div class="row g-4">
                    @foreach ($equipements as $equipement)
                        <div class="col-sm-6 col-lg-4">
                            <article class="h-100 overflow-hidden rounded border bg-white shadow-sm">
                                @if ($equipement->image)
                                    <img
                                        src="{{ asset('storage/' . $equipement->image) }}"
                                        alt="{{ $equipement->nom }}"
                                        class="w-100"
                                        style="height: 220px; object-fit: cover;"
                                    >
                                @endif
                                <div class="p-4">
                                    <h2 class="h5">{{ $equipement->nom }}</h2>
                                    <p class="text-muted">{{ $equipement->type_equipement }}</p>
                                    <p class="mb-0 fw-bold">{{ number_format($equipement->prix_louer, 2) }} DT</p>
                                    @if ($equipement->etat === 'reserve')
                                        <p class="mt-3 mb-0 text-danger">Déjà réservé</p>
                                    @else
                                        <form
                                            action="{{ route('front.equipements.reservations.store', $equipement) }}"
                                            method="POST"
                                            class="mt-4"
                                        >
                                            @csrf
                                            <div class="mb-2">
                                                <label for="nom-{{ $equipement->id }}" class="form-label">Nom</label>
                                                <input id="nom-{{ $equipement->id }}" name="nom" class="form-control" value="{{ old('nom') }}" required maxlength="255">
                                            </div>
                                            <div class="mb-2">
                                                <label for="prenom-{{ $equipement->id }}" class="form-label">Prénom</label>
                                                <input id="prenom-{{ $equipement->id }}" name="prenom" class="form-control" value="{{ old('prenom') }}" required maxlength="255">
                                            </div>
                                            <div class="mb-2">
                                                <label for="email-{{ $equipement->id }}" class="form-label">E-mail</label>
                                                <input id="email-{{ $equipement->id }}" name="email" type="email" class="form-control" value="{{ old('email') }}" required maxlength="255">
                                            </div>
                                            <div class="mb-2">
                                                <label for="numero-{{ $equipement->id }}" class="form-label">Téléphone</label>
                                                <input id="numero-{{ $equipement->id }}" name="numero" type="tel" class="form-control" value="{{ old('numero') }}" required maxlength="30">
                                            </div>
                                            <div class="mb-2">
                                                <label for="date-debut-{{ $equipement->id }}" class="form-label">Début</label>
                                                <input id="date-debut-{{ $equipement->id }}" name="date_debut" type="date" class="form-control" value="{{ old('date_debut') }}" min="{{ now()->toDateString() }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="date-fin-{{ $equipement->id }}" class="form-label">Fin</label>
                                                <input id="date-fin-{{ $equipement->id }}" name="date_fin" type="date" class="form-control" value="{{ old('date_fin') }}" min="{{ now()->toDateString() }}" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Demander la réservation</button>
                                        </form>
                                    @endif
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
