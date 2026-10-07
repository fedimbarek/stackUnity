<x-app-layout>
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">Nouveau point de fraîcheur</h1>
        <div class="card shadow"><div class="card-body">
                <form action="{{ route('admin.cooling-points.store') }}" method="POST">
                    @csrf
                    @include('admin.cooling-points._form')
                    <button class="btn btn-primary">Enregistrer</button>
                    <a href="{{ route('admin.cooling-points.index') }}" class="btn btn-secondary">Annuler</a>
                </form>
            </div></div>
    </div>
</x-app-layout>
