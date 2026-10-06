<x-app-layout>
    <x-slot name="header">Nouveau contact d'urgence</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('admin.contacts.store') }}" method="POST">
                @csrf
                @include('admin.contacts._form')
                <button class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('admin.contacts.index') }}" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</x-app-layout>