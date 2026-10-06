<x-app-layout>
    <x-slot name="header">Quartiers</x-slot>

    @if (session('status') === 'neighborhood-created')
        <div class="alert alert-success">Quartier ajouté.</div>
    @elseif (session('status') === 'neighborhood-updated')
        <div class="alert alert-success">Quartier mis à jour.</div>
    @elseif (session('status') === 'neighborhood-deleted')
        <div class="alert alert-success">Quartier supprimé.</div>
    @endif
    @error('delete') <div class="alert alert-danger">{{ $message }}</div> @enderror

    @php
        $sort = request('sort', 'name');
        $dir = request('dir') === 'desc' ? 'desc' : 'asc';

        // URL de tri : garde les filtres, inverse la direction si on reclique sur la même colonne
        $sortUrl = fn ($col) => request()->fullUrlWithQuery([
            'sort' => $col,
            'dir' => ($sort === $col && $dir === 'asc') ? 'desc' : 'asc',
            'page' => null,
        ]);

        $sortIcon = fn ($col) => $sort === $col
            ? ($dir === 'asc' ? 'fa-sort-up' : 'fa-sort-down')
            : 'fa-sort text-muted';

        $hasFilters = collect(['search', 'city', 'users', 'outages', 'map'])
            ->contains(fn ($key) => request()->filled($key));
    @endphp

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('neighborhoods.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Ajouter un quartier
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">

            {{-- Recherche + filtres --}}
            <form method="GET" action="{{ route('neighborhoods.index') }}" class="row g-2 mb-4">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="dir" value="{{ $dir }}">

                <div class="col-md-3 mb-2">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Rechercher un quartier ou une ville">
                </div>

                <div class="col-md-2 mb-2">
                    <select name="city" class="form-control form-select">
                        <option value="">Toutes les villes</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city }}" @selected(request('city') === $city)>{{ $city }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <select name="users" class="form-control form-select">
                        <option value="">Résidents : tous</option>
                        <option value="with" @selected(request('users') === 'with')>Avec résidents</option>
                        <option value="without" @selected(request('users') === 'without')>Sans résidents</option>
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <select name="outages" class="form-control form-select">
                        <option value="">Coupures : toutes</option>
                        <option value="with" @selected(request('outages') === 'with')>Avec coupures</option>
                        <option value="without" @selected(request('outages') === 'without')>Sans coupures</option>
                    </select>
                </div>

                <div class="col-md-2 mb-2">
                    <select name="map" class="form-control form-select">
                        <option value="">Position : toutes</option>
                        <option value="with" @selected(request('map') === 'with')>Avec position</option>
                        <option value="without" @selected(request('map') === 'without')>Sans position</option>
                    </select>
                </div>

                <div class="col-md-1 mb-2 d-flex" style="gap: .25rem;">
                    <button type="submit" class="btn btn-primary" title="Filtrer"><i class="fas fa-search"></i></button>
                    @if ($hasFilters)
                        <a href="{{ route('neighborhoods.index') }}" class="btn btn-secondary" title="Réinitialiser">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><a href="{{ $sortUrl('name') }}" class="text-dark text-decoration-none">Nom <i class="fas {{ $sortIcon('name') }}"></i></a></th>
                            <th><a href="{{ $sortUrl('city') }}" class="text-dark text-decoration-none">Ville <i class="fas {{ $sortIcon('city') }}"></i></a></th>
                            <th><a href="{{ $sortUrl('users_count') }}" class="text-dark text-decoration-none">Résidents <i class="fas {{ $sortIcon('users_count') }}"></i></a></th>
                            <th><a href="{{ $sortUrl('power_outages_count') }}" class="text-dark text-decoration-none">Coupures <i class="fas {{ $sortIcon('power_outages_count') }}"></i></a></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($neighborhoods as $neighborhood)
                            <tr>
                                <td>
                                    {{ $neighborhood->name }}
                                    @if ($neighborhood->latitude !== null && $neighborhood->longitude !== null)
                                        <i class="fas fa-map-marker-alt text-success" title="Position définie sur la carte"></i>
                                    @endif
                                </td>
                                <td>{{ $neighborhood->city }}</td>
                                <td>{{ $neighborhood->users_count }}</td>
                                <td>{{ $neighborhood->power_outages_count }}</td>
                                <td class="text-nowrap">
                                    <a href="{{ route('neighborhoods.show', $neighborhood) }}" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('neighborhoods.edit', $neighborhood) }}" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                    <form method="POST" action="{{ route('neighborhoods.destroy', $neighborhood) }}" class="d-inline"
                                          onsubmit="return confirm('Supprimer ce quartier ?');">
                                        @csrf @method('delete')
                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    {{ $hasFilters ? 'Aucun quartier ne correspond à ta recherche.' : 'Aucun quartier.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <small class="text-muted">
                    {{ $neighborhoods->total() }} quartier(s)
                    @if ($neighborhoods->total() > 0)
                        — affichage {{ $neighborhoods->firstItem() }} à {{ $neighborhoods->lastItem() }}
                    @endif
                </small>
                {{ $neighborhoods->links() }}
            </div>
        </div>
    </div>
</x-app-layout>