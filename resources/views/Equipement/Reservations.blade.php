<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Réservations — {{ $equipement->nom }}
            </h2>
            <a href="{{ route('admin.equipements.index') }}" class="btn btn-secondary">Retour aux équipements</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        @foreach ($errors->all() as $error)
                            <p class="mb-0">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                @if ($reservations->isEmpty())
                    <p class="text-gray-600">Aucune demande de réservation pour cet équipement.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left">Client</th>
                                    <th class="px-4 py-3 text-left">Contact</th>
                                    <th class="px-4 py-3 text-left">Période</th>
                                    <th class="px-4 py-3 text-left">Statut</th>
                                    <th class="px-4 py-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($reservations as $reservation)
                                    <tr>
                                        <td class="px-4 py-3">{{ $reservation->prenom }} {{ $reservation->nom }}</td>
                                        <td class="px-4 py-3">
                                            <div>{{ $reservation->email }}</div>
                                            <div>{{ $reservation->numero }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $reservation->date_debut->format('d/m/Y') }}
                                            – {{ $reservation->date_fin->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $reservation->confirmee_at ? 'Confirmée' : 'En attente' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if (! $reservation->confirmee_at && $equipement->etat !== 'reserve')
                                                <form
                                                    action="{{ route('admin.equipements.reservations.confirm', [$equipement, $reservation]) }}"
                                                    method="POST"
                                                    class="inline"
                                                >
                                                    @csrf
                                                    @method('PATCH')
                                                    <button class="btn btn-sm btn-success" type="submit">Confirmer</button>
                                                </form>
                                            @endif
                                            <form
                                                action="{{ route('admin.equipements.reservations.destroy', [$equipement, $reservation]) }}"
                                                method="POST"
                                                class="inline"
                                                onsubmit="return confirm('Supprimer cette demande ?')"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" type="submit">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $reservations->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
