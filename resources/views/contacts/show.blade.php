@extends('layouts.front')

@section('title', $contact->name)

@section('content')
<div class="container page-content">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('contacts.index') }}">Urgences</a></li>
            <li class="breadcrumb-item">
                <a href="{{ route('contacts.index', ['category' => $contact->category->id]) }}">{{ $contact->category->name }}</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">{{ $contact->name }}</li>
        </ol>
    </nav>

    <div class="card shadow-sm card-contact">
        <div class="card-body p-4">
            <div class="d-flex align-items-center mb-4">
                <span class="cat-icon me-3" style="background: {{ $contact->category->color }}; width:56px; height:56px;">
                    <i class="bi {{ $contact->category->icon }}"></i>
                </span>
                <div>
                    <h1 class="h3 fw-bold mb-1" style="color: var(--heat-navy)">{{ $contact->name }}</h1>
                    <span class="badge rounded-pill" style="background: {{ $contact->category->color }}">{{ $contact->category->name }}</span>
                    @if ($contact->is_24h)
                        <span class="badge text-bg-success rounded-pill"><i class="bi bi-clock"></i> 24h/24</span>
                    @endif
                </div>
            </div>

            <dl class="row">
                <dt class="col-sm-3">Téléphone</dt>
                <dd class="col-sm-9 fs-5 fw-bold text-heat">{{ $contact->phone }}</dd>

                <dt class="col-sm-3">Ville</dt>
                <dd class="col-sm-9">{{ $contact->city ?? '—' }}</dd>

                <dt class="col-sm-3">Adresse</dt>
                <dd class="col-sm-9">{{ $contact->address ?? '—' }}</dd>

                <dt class="col-sm-3">Disponibilité</dt>
                <dd class="col-sm-9">{{ $contact->is_24h ? 'Disponible 24h/24' : 'Horaires de bureau' }}</dd>

                <dt class="col-sm-3">Description</dt>
                <dd class="col-sm-9">{{ $contact->description ?? '—' }}</dd>
            </dl>

            <a href="tel:{{ $contact->phone_link }}" class="btn btn-heat btn-lg">
                <i class="bi bi-telephone-outbound"></i> Appeler maintenant
            </a>
            <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary btn-lg rounded-pill">Retour</a>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <h2 class="h5 fw-bold mt-5 mb-3" style="color: var(--heat-navy)">Dans la même catégorie</h2>
        <div class="row g-3">
            @foreach ($related as $r)
                <div class="col-md-4">
                    <a href="{{ route('contacts.show', $r) }}" class="text-decoration-none">
                        <div class="card card-contact shadow-sm h-100">
                            <div class="card-body">
                                <h3 class="h6 fw-bold text-dark">{{ $r->name }}</h3>
                                <p class="mb-0 text-heat fw-bold">{{ $r->phone }}</p>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection