<x-app-layout>
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">Modifier le type</h1>
        <div class="card shadow"><div class="card-body">
                <form action="{{ route('admin.cooling-point-types.update', $coolingPointType) }}" method="POST">
                    @csrf @method('PUT')
                    @include('admin.cooling-point-types._form')
                    <button class="btn btn-primary">Mettre à jour</button>
                    <a href="{{ route('admin.cooling-point-types.index') }}" class="btn btn-secondary">Annuler</a>
                </form>
            </div></div>
    </div>
</x-app-layout>
