<x-app-layout>
    <x-slot name="header">Générer un rapport</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('reports.store') }}">
                @csrf
                @include('reports.partials.form')
                <button type="submit" class="btn btn-primary">Générer</button>
                <a href="{{ route('reports.index') }}" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</x-app-layout>