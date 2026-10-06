<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Réservations — {{ $equipement->nom }}
            </h2>
            <a href="{{ route('admin.equipements.index') }}" class="text-sm text-blue-600 hover:underline">
                Retour aux équipements
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                @if (session('success'))
                    <div class="mb-4 rounded bg-green-100 p-3 text-green-800" role="status">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded bg-red-100 p-3 text-red-800" role="alert">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                @if ($reservations->isEmpty())
                    <p class="text-gray-500">Aucune demande de réservation pour cet équipement.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Nom</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Prénom</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">E-mail</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Numéro</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Date de début</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Date de fin</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">État</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($reservations as $reservation)
                                    <tr>
                                        <td class="px-4 py-3">{{ $reservation->nom }}</td>
                                        <td class="px-4 py-3">{{ $reservation->prenom }}</td>
                                        <td class="px-4 py-3">{{ $reservation->email }}</td>
                                        <td class="px-4 py-3">{{ $reservation->numero }}</td>
                                        <td class="px-4 py-3">{{ $reservation->date_debut->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3">{{ $reservation->date_fin->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3">
                                            @if ($reservation->confirmee_at)
                                                <span class="font-semibold text-green-700">Confirmée</span>
                                            @else
                                                <span class="text-yellow-700">En attente</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
                                                @if (! $reservation->confirmee_at && $equipement->etat === 'disponible')
                                                    <form action="{{ route('admin.equipements.reservations.confirm', [$equipement, $reservation]) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" style="padding:6px 10px; border:0; border-radius:4px; background:#16a34a; color:white; font-size:12px; cursor:pointer;">
                                                            Confirmer
                                                        </button>
                                                    </form>
                                                @elseif (! $reservation->confirmee_at)
                                                    <span style="font-size:12px; color:#6b7280;">Équipement déjà réservé</span>
                                                @endif

                                                <form
                                                    action="{{ route('admin.equipements.reservations.destroy', [$equipement, $reservation]) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Supprimer cette demande de réservation ?')"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" style="padding:6px 10px; border:0; border-radius:4px; background:#dc2626; color:white; font-size:12px; cursor:pointer;">
                                                        Supprimer
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $reservations->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
