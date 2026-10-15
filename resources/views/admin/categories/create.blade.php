<x-app-layout>
    <x-slot name="header">Nouvelle catégorie</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('admin.contact-categories.store') }}" method="POST">
                @csrf
                @include('admin.categories._form')
                <button class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('admin.contact-categories.index') }}" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</x-app-layout>