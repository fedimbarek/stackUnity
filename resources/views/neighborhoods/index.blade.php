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

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('neighborhoods.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Ajouter un quartier
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr><th>Nom</th><th>Ville</th><th>Résidents</th><th>Coupures</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($neighborhoods as $neighborhood)
                            <tr>
                                <td>{{ $neighborhood->name }}</td>
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
                            <tr><td colspan="5" class="text-center text-muted">Aucun quartier.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $neighborhoods->links() }}
        </div>
    </div>
</x-app-layout>