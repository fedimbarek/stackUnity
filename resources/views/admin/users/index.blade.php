{{-- admin/users/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">Utilisateurs</x-slot>

    @if (session('status') === 'role-updated')
        <div class="alert alert-success">Rôle mis à jour.</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr><th>Nom</th><th>Email</th><th>Quartier</th><th>Rôle</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->neighborhood?->name ?? '—' }}</td>
                                <td>{{ $user->roles->pluck('name')->join(', ') }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.users.updateRole', $user) }}" class="form-inline">
                                        @csrf @method('put')
                                        <select name="role" class="form-control form-control-sm mr-2" {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                            @foreach (['resident', 'gestionnaire', 'admin'] as $role)
                                                <option value="{{ $role }}" @selected($user->hasRole($role))>{{ ucfirst($role) }}</option>
                                            @endforeach
                                        </select>
                                        @unless ($user->id === auth()->id())
                                            <button type="submit" class="btn btn-sm btn-dark">Modifier</button>
                                        @endunless
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>