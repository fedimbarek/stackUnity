<x-app-layout>
    <x-slot name="header">Ajouter un quartier</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('neighborhoods.store') }}">
                @csrf
                @include('neighborhoods.partials.form')
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('neighborhoods.index') }}" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</x-app-layout>