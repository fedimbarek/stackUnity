<x-app-layout>
    <x-slot name="header">Modifier le rapport</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('reports.update', $report) }}">
                @csrf @method('put')
                @include('reports.partials.form', ['report' => $report])
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('reports.index') }}" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</x-app-layout>