<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Équipements
        </h2>
        <a href="{{ route('admin.equipements.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Ajouter un équipement
        </a>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg p-6">

                @if ($equipements->isEmpty())
                    <p class="text-gray-500">Aucun équipement pour le moment.</p>
                @else
                    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(380px,1fr)); gap:16px;">
    @foreach ($equipements as $equipement)
        <div style="display:flex; flex-direction:row; min-height:150px; border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; box-shadow:0 1px 2px rgba(0,0,0,.08);">

            {{-- Image à gauche --}}
            <div style="width:150px; min-height:150px; flex-shrink:0; background:#e5e7eb;">
                @if ($equipement->image)
                    <img src="{{ asset('storage/' . $equipement->image) }}"
                         alt="{{ $equipement->nom }}"
                         style="width:100%; height:100%; object-fit:cover;">
                @else
                    <div style="height:100%; display:flex; align-items:center; justify-content:center; color:#9ca3af; font-size:12px;">
                        Pas d'image
                    </div>
                @endif
            </div>

            {{-- Infos à droite --}}
            <div style="flex:1; min-width:0; padding:12px; display:flex; flex-direction:column; justify-content:space-between; gap:8px;">
                <div>
                    <h4 style="margin:0; font-size:16px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $equipement->nom }}
                    </h4>
                    <p style="margin:0; font-size:14px; color:#6b7280; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ $equipement->type_equipement ?? 'Non défini' }}
                    </p>
                </div>
                <div>
                    <p style="margin:0; font-size:16px; font-weight:700; color:#4f46e5;">
                        {{ number_format($equipement->prix_louer, 2) }} DT
                    </p>
                    <p style="margin:0; font-size:12px; color:#9ca3af;">
                        Ajouté le {{ \Carbon\Carbon::parse($equipement->date_ajout)->format('d/m/Y') }}
                    </p>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <a href="{{ route('admin.equipements.edit', $equipement) }}"
                       style="padding:6px 10px; border-radius:4px; background:#2563eb; color:white; font-size:12px; text-decoration:none;">
                        Modifier
                    </a>
                    <a href="{{ route('admin.equipements.reservations.index', $equipement) }}"
                       style="padding:6px 10px; border-radius:4px; background:#4f46e5; color:white; font-size:12px; text-decoration:none;">
                        Voir les demandes
                    </a>
                    <form action="{{ route('admin.equipements.destroy', $equipement) }}" method="POST"
                          onsubmit="return confirm('Supprimer cet équipement ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="padding:6px 10px; border:0; border-radius:4px; background:#dc2626; color:white; font-size:12px; cursor:pointer;">
                            Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>