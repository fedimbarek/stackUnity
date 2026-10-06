<x-app-layout>
    <x-slot name="header">Tableau de bord</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            Bienvenue, {{ auth()->user()->name }} !<br>
            Ton quartier : <strong>{{ auth()->user()->neighborhood?->name ?? 'non renseigné' }}</strong>
        </div>
    </div>
</x-app-layout>