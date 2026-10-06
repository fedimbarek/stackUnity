@extends('layouts.front')

@section('title', 'Contacts d\'urgence')

@section('content')

{{-- HERO + RECHERCHE --}}
<section class="page-hero">
    <div class="container">
        <h1 class="display-6 fw-bold"><i class="bi bi-telephone-fill"></i> Contacts d'urgence</h1>
        <p class="lead mb-4">Canicule, coupure de courant… retrouvez rapidement les bons numéros.</p>

        <form method="GET" action="{{ route('contacts.index') }}" class="row g-2">
            <div class="col-md-6">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-lg"
                       placeholder="Rechercher par nom, ville ou numéro…">
            </div>
            <div class="col-md-4">
                <select name="category" class="form-select form-select-lg">
                    <option value="">Toutes les catégories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category') == $category->id)>
                            {{ $category->name }} ({{ $category->contacts_count }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-heat btn-lg"><i class="bi bi-search"></i> Chercher</button>
            </div>
        </form>
    </div>
</section>

<div class="container mt-5 mb-5">

    {{-- APPEL RAPIDE --}}
    @if ($priorityContacts->isNotEmpty() && ! request()->hasAny(['q', 'category']))
        <h2 class="h5 fw-bold mb-3" style="color: var(--heat-navy)">
            <i class="bi bi-lightning-charge-fill text-heat"></i> Appel rapide
        </h2>
        <div class="row g-3 mb-5">
            @foreach ($priorityContacts as $p)
                <div class="col-6 col-md-3">
                    <a href="tel:{{ $p->phone_link }}" class="text-decoration-none">
                        <div class="quick-call shadow-sm p-3 h-100 text-center">
                            <div class="fs-3 fw-bold text-heat">{{ $p->phone }}</div>
                            <div class="text-dark small">{{ $p->name }}</div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
<<! --  dddd-- >>
    {{-- PILLS CATÉGORIES --}}
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('contacts.index') }}"
           class="btn btn-sm rounded-pill {{ request('category') ? 'btn-outline-secondary' : 'btn-navy' }}">Tous</a>
        @foreach ($categories as $category)
            <a href="{{ route('contacts.index', ['category' => $category->id]) }}"
               class="btn btn-sm {{ request('category') == $category->id ? 'btn-heat' : 'btn-outline-heat' }}">
                <i class="bi {{ $category->icon }}"></i> {{ $category->name }}
            </a>
        @endforeach
    </div>

    {{-- LISTE --}}
    <div class="row g-4">
        @forelse ($contacts as $contact)
            <div class="col-md-6 col-lg-4">
                <div class="card card-contact shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <span class="cat-icon me-3" style="background: {{ $contact->category->color }}">
                                <i class="bi {{ $contact->category->icon }}"></i>
                            </span>
                            <div>
                                <h3 class="h6 fw-bold mb-0">{{ $contact->name }}</h3>
                                <small class="text-muted">{{ $contact->category->name }}</small>
                            </div>
                        </div>

                        <p class="mb-1"><i class="bi bi-telephone text-heat"></i> <strong>{{ $contact->phone }}</strong></p>
                        @if ($contact->city)
                            <p class="mb-1 small"><i class="bi bi-geo-alt text-heat"></i> {{ $contact->city }}</p>
                        @endif
                        @if ($contact->is_24h)
                            <span class="badge text-bg-success rounded-pill"><i class="bi bi-clock"></i> 24h/24</span>
                        @endif
                    </div>
                    <div class="card-footer bg-white border-0 d-flex gap-2 pb-3">
                        <a href="tel:{{ $contact->phone_link }}" class="btn btn-heat btn-sm flex-fill">
                            <i class="bi bi-telephone-outbound"></i> Appeler
                        </a>
                        <a href="{{ route('contacts.show', $contact) }}" class="btn btn-outline-secondary btn-sm rounded-pill">Détails</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-warning">Aucun contact trouvé pour votre recherche.</div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $contacts->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
