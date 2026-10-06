<x-app-layout>
    <x-slot name="header">Catégories de contacts</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="mb-3 text-right">
                <a href="{{ route('admin.contact-categories.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouvelle catégorie
                </a>
            </div>

            <table class="table table-bordered">
                <thead>
                    <tr><th>Icône</th><th>Nom</th><th>Couleur</th><th>Contacts</th><th width="130">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td><i class="bi {{ $category->icon }}" style="color: {{ $category->color }}; font-size: 1.3rem;"></i></td>
                            <td>{{ $category->name }}</td>
                            <td><span class="badge text-white" style="background: {{ $category->color }}">{{ $category->color }}</span></td>
                            <td>{{ $category->contacts_count }}</td>
                            <td>
                                <a href="{{ route('admin.contact-categories.edit', $category) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.contact-categories.destroy', $category) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Supprimer cette catégorie ?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">Aucune catégorie.</td></tr>
                    @endforelse
                </tbody>
            </table>

            {{ $categories->links('pagination::bootstrap-4') }}
        </div>
    </div>
</x-app-layout>