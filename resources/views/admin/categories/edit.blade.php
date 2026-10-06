<x-app-layout>
    <x-slot name="header">Modifier la catégorie</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="{{ route('admin.contact-categories.update', $category) }}" method="POST">
                @csrf
                @method('PUT')
                @include('admin.categories._form')
                <button class="btn btn-primary">Mettre à jour</button>
                <a href="{{ route('admin.contact-categories.index') }}" class="btn btn-secondary">Annuler</a>
            </form>
        </div>
    </div>
</x-app-layout>