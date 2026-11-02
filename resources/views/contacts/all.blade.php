@extends('layouts.front')

@section('title', 'Tous les contacts d\'urgence')

@section('content')
<style>
    .all-contacts-page { padding-top: 130px; padding-bottom: 60px; }
    .cat-header { border-left: 6px solid; padding: 6px 14px; border-radius: 6px; background: #f8f9fc; }
    .contact-row:hover { background: #fff7f2; }
    .num-big { font-size: 1.05rem; font-weight: 800; color: var(--heat-red); white-space: nowrap; }
    .quick-chip { border: 2px solid var(--heat-orange); border-radius: 14px; padding: 10px 14px; text-align: center; color: inherit; }
    .quick-chip:hover { background: #fff3ec; }
</style>

<div class="container all-contacts-page">

    {{-- EN-TÊTE + ACTIONS --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1" style="color: var(--heat-navy)">
                <i class="bi bi-journal-text"></i> Annuaire complet des urgences
            </h1>
            <p class="text-muted mb-0">{{ $total }} contact(s) actif(s) · {{ $categories->count() }} catégorie(s)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('contacts.index') }}" class="btn btn-outline-secondary rounded-pill">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <a href="{{ route('contacts.pdf') }}" class="btn btn-heat">
                <i class="bi bi-file-earmark-pdf"></i> Télécharger en PDF
            </a>
        </div>
    </div>

    {{-- FILTRE INSTANTANÉ (côté navigateur) --}}
    <input type="text" id="contactFilter" class="form-control form-control-lg mb-4"
           placeholder="Filtrer instantanément par nom, ville ou numéro…">

    {{-- NUMÉROS NATIONAUX --}}
    @if ($priorityContacts->isNotEmpty())
        <h2 class="h6 fw-bold text-uppercase text-muted mb-2">Numéros nationaux — appel rapide</h2>
        <div class="row g-2 mb-4">
            @foreach ($priorityContacts as $p)
                <div class="col-6 col-md-3">
                    <a href="tel:{{ $p->phone_link }}" class="d-block text-decoration-none quick-chip">
                        <div class="fs-4 fw-bold" style="color: var(--heat-red)">{{ $p->phone }}</div>
                        <div class="small text-dark">{{ $p->name }}</div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- CONTACTS PAR CATÉGORIE --}}
    @forelse ($categories as $category)
        <section class="mb-4 category-block">
            <h2 class="h5 fw-bold cat-header mb-2" style="border-color: {{ $category->color }}">
                <i class="bi {{ $category->icon }}" style="color: {{ $category->color }}"></i>
                {{ $category->name }}
                <span class="badge rounded-pill ms-1" style="background: {{ $category->color }}">{{ $category->contacts->count() }}</span>
            </h2>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light small text-uppercase">
                        <tr>
                            <th>Nom</th>
                            <th>Téléphone</th>
                            <th class="d-none d-md-table-cell">Ville</th>
                            <th class="d-none d-lg-table-cell">Adresse</th>
                            <th>Dispo.</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($category->contacts as $contact)
                            <tr class="contact-row"
                                data-search="{{ Str::lower($contact->name . ' ' . $contact->city . ' ' . $contact->phone . ' ' . $category->name) }}">
                                <td>
                                    <a href="{{ route('contacts.show', $contact) }}" class="fw-semibold text-dark text-decoration-none">
                                        {{ $contact->name }}
                                    </a>
                                    @if ($contact->is_priority)
                                        <span class="badge text-bg-danger ms-1">National</span>
                                    @endif
                                </td>
                                <td class="num-big">{{ $contact->phone }}</td>
                                <td class="d-none d-md-table-cell">{{ $contact->city ?? '—' }}</td>
                                <td class="d-none d-lg-table-cell small text-muted">{{ $contact->address ?? '—' }}</td>
                                <td>
                                    @if ($contact->is_24h)
                                        <span class="badge text-bg-success rounded-pill">24h/24</span>
                                    @else
                                        <span class="badge text-bg-secondary rounded-pill">Bureau</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="tel:{{ $contact->phone_link }}" class="btn btn-heat btn-sm">
                                        <i class="bi bi-telephone-outbound"></i> Appeler
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @empty
        <div class="alert alert-warning">Aucun contact d'urgence n'est disponible pour le moment.</div>
    @endforelse

    <div id="noFilterResult" class="alert alert-warning d-none">Aucun contact ne correspond à ce filtre.</div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('contactFilter')?.addEventListener('input', function () {
        const term = this.value.trim().toLowerCase();
        let visibleTotal = 0;

        document.querySelectorAll('.category-block').forEach(block => {
            let visible = 0;
            block.querySelectorAll('.contact-row').forEach(row => {
                const match = row.dataset.search.includes(term);
                row.classList.toggle('d-none', !match);
                if (match) visible++;
            });
            block.classList.toggle('d-none', visible === 0);
            visibleTotal += visible;
        });

        document.getElementById('noFilterResult').classList.toggle('d-none', visibleTotal > 0);
    });
</script>
@endpush
