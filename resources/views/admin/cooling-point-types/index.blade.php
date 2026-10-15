<x-app-layout>
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-tags text-primary"></i> Types de points</h1>
            <a href="{{ route('admin.cooling-point-types.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="fas fa-plus"></i> Nouveau type
            </a>
        </div>

        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="card shadow mb-4">
            <div class="card-body">
                <table class="table table-bordered">
                    <thead class="thead-light">
                    <tr><th>#</th><th>Nom</th><th>Icône</th><th>Couleur</th><th>Points</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    @forelse($types as $type)
                        <tr>
                            <td>{{ $type->id }}</td>
                            <td>{{ $type->name }}</td>
                            <td><i class="fas {{ $type->icon }}"></i> {{ $type->icon }}</td>
                            <td><span class="badge" style="background:{{ $type->color }}">&nbsp;&nbsp;&nbsp;</span></td>
                            <td><span class="badge badge-info">{{ $type->cooling_points_count }}</span></td>
                            <td>
                                <a href="{{ route('admin.cooling-point-types.show', $type) }}" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('admin.cooling-point-types.edit', $type) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.cooling-point-types.destroy', $type) }}" method="POST" class="d-inline" onsubmit="return confirm('Confirmer la suppression ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">Aucun type</td></tr>
                    @endforelse
                    </tbody>
                </table>
                {{ $types->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
