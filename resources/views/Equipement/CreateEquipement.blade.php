<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Ajouter un équipement</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <form action="{{ route('admin.equipements.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label for="nom" class="block text-sm font-medium text-gray-700">Nom</label>
                        <input id="nom" name="nom" type="text" value="{{ old('nom') }}" required maxlength="255"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                        @error('nom') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="type_equipement" class="block text-sm font-medium text-gray-700">Type d'équipement</label>
                        <input id="type_equipement" name="type_equipement" type="text" value="{{ old('type_equipement') }}" required maxlength="255"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                        @error('type_equipement') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="date_ajout" class="block text-sm font-medium text-gray-700">Date d'ajout</label>
                        <input id="date_ajout" name="date_ajout" type="date" value="{{ old('date_ajout', now()->toDateString()) }}" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                        @error('date_ajout') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="prix_louer" class="block text-sm font-medium text-gray-700">Prix de location (DT)</label>
                        <input id="prix_louer" name="prix_louer" type="number" value="{{ old('prix_louer') }}" required min="0" step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                        @error('prix_louer') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-6">
                        <label for="image" class="block text-sm font-medium text-gray-700">Image (facultative, 2 Mo maximum)</label>
                        <input id="image" name="image" type="file" accept="image/*"
                            class="mt-1 block w-full text-sm text-gray-700" />
                        @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit" class="btn btn-primary">Enregistrer l'équipement</button>
                        <a href="{{ route('admin.equipements.index') }}" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>