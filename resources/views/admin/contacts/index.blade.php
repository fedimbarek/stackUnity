<x-app-layout>
    <x-slot name="header">Contacts d'urgence</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" class="form-inline mb-3">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control mr-2" placeholder="Nom, ville, téléphone">
                <select name="category" class="form-control mr-2">
                    <option value="">Toutes les catégories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-secondary mr-2">Filtrer</button>
                <a href="{{ route('admin.contacts.create') }}" class="btn btn-primary ml-auto">
                    <i class="fas fa-plus"></i> Nouveau contact
                </a>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nom</th><th>Catégorie</th><th>Téléphone</th><th>Ville</th><th>Statut</th><th width="130">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contacts as $contact)
                            <tr>
                                <td>{{ $contact->name }}</td>
                                <td>{{ $contact->category->name ?? '-' }}</td>
                                <td>{{ $contact->phone }}</td>
                                <td>{{ $contact->city ?? '-' }}</td>
                                <td>
                                    @if ($contact->is_24h) <span class="badge badge-info">24h</span> @endif
                                    @if ($contact->is_priority) <span class="badge badge-warning">Priorité</span> @endif
                                    <span class="badge badge-{{ $contact->is_active ? 'success' : 'secondary' }}">
                                        {{ $contact->is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.contacts.edit', $contact) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('admin.contacts.destroy', $contact) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Supprimer ce contact ?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center">Aucun contact.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $contacts->links('pagination::bootstrap-4') }}
        </div>
    </div>
</x-app-layout>