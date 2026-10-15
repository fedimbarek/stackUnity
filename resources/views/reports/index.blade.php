<x-app-layout>
    <x-slot name="header">Rapports statistiques</x-slot>

    @if (session('status') === 'report-created')
        <div class="alert alert-success">Rapport généré.</div>
    @elseif (session('status') === 'report-updated')
        <div class="alert alert-success">Rapport mis à jour.</div>
    @elseif (session('status') === 'report-deleted')
        <div class="alert alert-success">Rapport supprimé.</div>
    @endif

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('reports.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Générer un rapport</a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr><th>Titre</th><th>Période</th><th>Quartier</th><th>Statut</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            <td>{{ $report->title }}</td>
                            <td>{{ $report->period_start->format('d/m/Y') }} - {{ $report->period_end->format('d/m/Y') }}</td>
                            <td>{{ $report->neighborhood?->name ?? 'Tous' }}</td>
                            <td><span class="badge badge-{{ $report->status === 'sent' ? 'success' : ($report->status === 'failed' ? 'danger' : 'secondary') }}">{{ $report->status }}</span></td>
                            <td class="text-nowrap">
                                <a href="{{ route('reports.show', $report) }}" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('reports.edit', $report) }}" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="{{ route('reports.destroy', $report) }}" class="d-inline" onsubmit="return confirm('Supprimer ce rapport ?');">
                                    @csrf @method('delete')
                                    <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">Aucun rapport.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $reports->links() }}
        </div>
    </div>
</x-app-layout>