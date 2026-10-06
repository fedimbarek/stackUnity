<!-- {{-- admin/dashboard.blade.php --}}
<x-app-layout>
    <x-slot name="header">Tableau de bord administrateur</x-slot>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Utilisateurs</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalUsers }}</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a href="{{ route('admin.users.index') }}" class="btn btn-primary">Gérer les utilisateurs</a>
</x-app-layout> -->
<x-app-layout>
    <x-slot name="header">Tableau de bord administrateur</x-slot>

    @if (session('status') === 'report-sent')
        <div class="alert alert-success">Rapport envoyé par email.</div>
    @endif

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Utilisateurs</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalUsers }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Quartiers</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalNeighborhoods }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Coupures (cette semaine)</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                        {{ $kpis['current']['total_outages'] }}
                        <span class="small {{ $kpis['variation_percent'] >= 0 ? 'text-danger' : 'text-success' }}">
                            ({{ $kpis['variation_percent'] >= 0 ? '+' : '' }}{{ $kpis['variation_percent'] }}% vs sem. dernière)
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Taux de confirmation</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $kpis['current']['confirmation_rate'] }}%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Coupures par quartier (cette semaine)</h6></div>
        <div class="card-body">
            <table class="table table-sm table-bordered">
                <thead><tr><th>Quartier</th><th>Coupures</th></tr></thead>
                <tbody>
                    @forelse ($byNeighborhood as $row)
                        <tr><td>{{ $row->neighborhood }}</td><td>{{ $row->total }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center text-muted">Aucune donnée cette semaine.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ route('admin.users.index') }}" class="btn btn-primary">Gérer les utilisateurs</a>
    <a href="{{ route('reports.index') }}" class="btn btn-info">Voir les rapports</a>
    <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-secondary">Journal d'audit</a>
</x-app-layout>