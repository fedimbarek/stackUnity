<x-app-layout>
    <x-slot name="header">{{ $report->title }}</x-slot>

    @if (session('status') === 'report-sent')
        <div class="alert alert-success">Rapport envoyé par email.</div>
    @elseif (session('status') === 'report-send-failed')
        <div class="alert alert-danger">Échec de l'envoi.</div>
    @endif

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Coupures</div>
                    <div class="h5 mb-0 font-weight-bold">{{ $kpis['current']['total_outages'] }}
                        <span class="small {{ $kpis['variation_percent'] >= 0 ? 'text-danger' : 'text-success' }}">
                            ({{ $kpis['variation_percent'] >= 0 ? '+' : '' }}{{ $kpis['variation_percent'] }}%)
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Taux de confirmation</div>
                    <div class="h5 mb-0 font-weight-bold">{{ $kpis['current']['confirmation_rate'] }}%</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Durée moy. résolution</div>
                    <div class="h5 mb-0 font-weight-bold">{{ $kpis['current']['avg_resolution_minutes'] ?? '—' }} min</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Statut</div>
                    <div class="h5 mb-0 font-weight-bold">{{ $report->status }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            @if ($report->file_path)
                <a href="{{ route('reports.download', $report) }}" class="btn btn-success"><i class="fas fa-download"></i> Télécharger (CSV)</a>
            @endif

            <form method="POST" action="{{ route('reports.send', $report) }}" class="d-inline">
                @csrf
                <button class="btn btn-primary" {{ $report->file_path ? '' : 'disabled' }}>
                    <i class="fas fa-paper-plane"></i> Envoyer par email
                </button>
            </form>
        </div>
    </div>

    <a href="{{ route('reports.index') }}" class="btn btn-secondary">← Retour</a>
</x-app-layout>