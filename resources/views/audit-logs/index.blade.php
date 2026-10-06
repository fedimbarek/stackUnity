<x-app-layout>
    <x-slot name="header">Journal d'audit</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            <table class="table table-bordered table-sm" width="100%" cellspacing="0">
                <thead><tr><th>Admin</th><th>Action</th><th>Entité</th><th>Date</th></tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->admin->name }}</td>
                            <td>
                                <span class="badge badge-{{ $log->action === 'deleted' ? 'danger' : ($log->action === 'created' ? 'success' : 'warning') }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td>{{ $log->entity_type }} #{{ $log->entity_id }}</td>
                            <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">Aucune activité.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>