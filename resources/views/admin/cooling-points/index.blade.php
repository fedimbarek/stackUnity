<x-app-layout>
    <div class="container-fluid">

        {{-- ═══ En-tête ═══ --}}
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-snowflake text-primary"></i> Gérer les points de fraîcheur
            </h1>
            <div class="mt-2 mt-sm-0">
                <a href="{{ route('cooling-points.index') }}" class="btn btn-info btn-sm shadow-sm">
                    <i class="fas fa-map-marked-alt"></i> Voir la carte
                </a>
                <a href="{{ route('admin.cooling-points.create') }}" class="btn btn-primary btn-sm shadow-sm">
                    <i class="fas fa-plus"></i> Nouveau point
                </a>
            </div>
        </div>

        {{-- ═══ Message de succès ═══ --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm">
                <i class="fas fa-check-circle"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        {{-- ═══ Statistiques par type ═══ --}}
        <div class="row mb-3">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total des points</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $points->total() }}</div>
                            </div>
                            <div class="col-auto"><i class="fas fa-map-marked-alt fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            @foreach($typesStats as $type)
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card shadow h-100 py-2" style="border-left: .25rem solid {{ $type->color }} !important;">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-uppercase mb-1" style="color: {{ $type->color }}">
                                        {{ $type->name }}
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $type->points_count }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas {{ $type->icon ?? 'fa-map-marker-alt' }} fa-2x"
                                       style="color: {{ $type->color }}; opacity: .35;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ═══ Barre de filtres ═══ --}}
        <div class="card shadow mb-3">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('admin.cooling-points.index') }}"
                      class="d-flex flex-wrap align-items-center">

                    <div class="input-group input-group-sm mr-2 mb-2" style="max-width: 260px;">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="form-control" placeholder="Rechercher par nom...">
                    </div>

                    <select name="type_id" class="form-control form-control-sm mr-2 mb-2" style="max-width: 220px;">
                        <option value="">Tous les types</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ request('type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="btn btn-primary btn-sm mr-2 mb-2">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>

                    @if(request('search') || request('type_id'))
                        <a href="{{ route('admin.cooling-points.index') }}" class="btn btn-secondary btn-sm mb-2">
                            <i class="fas fa-times"></i> Réinitialiser
                        </a>
                    @endif
                </form>
            </div>
        </div>

        {{-- ═══ Tableau ═══ --}}
        <div class="card shadow">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th width="60">#</th>
                                <th>Nom</th>
                                <th width="180">Type</th>
                                <th width="140">Coordonnées</th>
                                <th>Adresse</th>
                                <th width="120">Horaires</th>
                                <th width="100">Source</th>
                                <th width="150" class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($points as $point)
                                <tr>
                                    <td class="text-muted small">{{ $point->id }}</td>
                                    <td>
                                        <strong>{{ $point->name }}</strong>
                                    </td>
                                    <td>
                                        @if($point->coolingPointType)
                                            <span class="badge" style="background: {{ $point->coolingPointType->color }}; font-size: 12px;">
                                                <i class="fas {{ $point->coolingPointType->icon }}"></i>
                                                {{ $point->coolingPointType->name }}
                                            </span>
                                        @else
                                            <span class="badge badge-secondary">{{ $point->type }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted font-monospace">
                                            {{ number_format($point->latitude, 5) }}<br>
                                            {{ number_format($point->longitude, 5) }}
                                        </small>
                                    </td>
                                    <td>
                                        <small>{{ $point->address ?? '—' }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $point->opening_hours ?? '—' }}</small>
                                    </td>
                                    <td>
                                        @if($point->source === 'manual')
                                            <span class="badge badge-info">Manuel</span>
                                        @elseif($point->source === 'osm')
                                            <span class="badge badge-warning">OSM</span>
                                        @else
                                            <span class="badge badge-secondary">{{ $point->source }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.cooling-points.show', $point) }}"
                                           class="btn btn-sm btn-info" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.cooling-points.edit', $point) }}"
                                           class="btn btn-sm btn-warning" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button"
                                                class="btn btn-sm btn-danger btn-delete"
                                                title="Supprimer"
                                                data-id="{{ $point->id }}"
                                                data-name="{{ $point->name }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="fas fa-snowflake fa-3x d-block mb-3 text-gray-300"></i>
                                        <p class="mb-2">Aucun point de fraîcheur trouvé</p>
                                        <a href="{{ route('admin.cooling-points.create') }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-plus"></i> Créer le premier point
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($points->hasPages())
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            {{ $points->firstItem() }}–{{ $points->lastItem() }} sur {{ $points->total() }} points
                        </small>
                        <div>{{ $points->appends(request()->query())->links() }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══ Modal de confirmation de suppression ═══ --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-warning"></i> Confirmer la suppression
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    Voulez-vous vraiment supprimer le point
                    <strong id="deleteName" class="text-danger">—</strong> ?<br>
                    <small class="text-muted">Cette action est irréversible.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <form id="deleteForm" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Gestion de la modale de suppression
            const deleteModal = document.getElementById('deleteModal');
            const deleteForm  = document.getElementById('deleteForm');
            const deleteName  = document.getElementById('deleteName');
            const baseUrl     = '{{ route("admin.cooling-points.destroy", ":id") }}';

            document.querySelectorAll('.btn-delete').forEach(btn => {
                btn.addEventListener('click', function () {
                    const id   = this.dataset.id;
                    const name = this.dataset.name;
                    deleteName.textContent = name;
                    deleteForm.action = baseUrl.replace(':id', id);
                    $(deleteModal).modal('show');
                });
            });
        });
    </script>
    @endpush

</x-app-layout>