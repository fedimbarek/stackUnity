<x-app-layout>
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-snowflake text-primary"></i> Points de fraîcheur</h1>
            <a href="{{ route('admin.cooling-points.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="fas fa-plus"></i> Nouveau point
            </a>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        <div class="card shadow">
            <div class="card-body">
                <table class="table table-bordered">
                    <thead class="thead-light">
                    <tr><th>#</th><th>Nom</th><th>Type</th><th>Lat/Lng</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    @forelse($points as $point)
                        <tr>
                            <td>{{ $point->id }}</td>
                            <td>{{ $point->name }}</td>
                            <td>
                                @if($point->coolingPointType)
                                    <span class="badge" style="background:{{ $point->coolingPointType->color }}">
                                    <i class="fas {{ $point->coolingPointType->icon }}"></i>
                                    {{ $point->coolingPointType->name }}
                                </span>
                                @endif
                            </td>
                            <td>{{ $point->latitude }}, {{ $point->longitude }}</td>
                            <td>
                                <a href="{{ route('admin.cooling-points.show', $point) }}" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('admin.cooling-points.edit', $point) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.cooling-points.destroy', $point) }}" method="POST" class="d-inline" onsubmit="return confirm('Supprimer ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">Aucun point</td></tr>
                    @endforelse
                    </tbody>
                </table>
                {{ $points->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
