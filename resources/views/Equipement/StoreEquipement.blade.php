<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Équipements
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">

                @if ($equipements->isEmpty())
                    <p class="text-gray-500">Aucun équipement pour le moment.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($equipements as $equipement)
                            <div class="border rounded-lg overflow-hidden shadow-sm">
                                @if ($equipement->image)
                                    <img src="{{ asset('storage/' . $equipement->image) }}"
                                         alt="{{ $equipement->nom }}"
                                         class="w-full h-48 object-cover">
                                @else
                                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center text-gray-400">
                                        Pas d'image
                                    </div>
                                @endif

                                <div class="p-4">
                                    <h4 class="text-lg font-semibold text-gray-900">{{ $equipement->nom }}</h4>
                                    <p class="text-sm text-gray-600">{{ $equipement->type_equipement ?? 'Non défini' }}</p>
                                    <p class="mt-2 font-bold text-indigo-600">
                                        {{ number_format($equipement->prix_louer, 2) }} DT
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Ajouté le {{ \Carbon\Carbon::parse($equipement->date_ajout)->format('d/m/Y') }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>