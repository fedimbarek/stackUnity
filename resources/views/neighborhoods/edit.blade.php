<x-app-layout>
    <x-slot name="header">Modifier le quartier</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('neighborhoods.update', $neighborhood) }}">
                @csrf @method('put')
                @include('neighborhoods.partials.form', ['neighborhood' => $neighborhood])
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('neighborhoods.index') }}" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</x-app-layout>