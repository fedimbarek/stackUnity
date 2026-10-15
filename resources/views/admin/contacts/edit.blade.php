<x-app-layout>
    <x-slot name="header">Modifier le contact</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('admin.contacts.update', $contact) }}" method="POST">
                @csrf
                @method('PUT')
                @include('admin.contacts._form')
                <button class="btn btn-primary">Mettre à jour</button>
                <a href="{{ route('admin.contacts.index') }}" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</x-app-layout>