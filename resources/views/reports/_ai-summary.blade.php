{{-- resources/views/reports/_ai-summary.blade.php --}}
<div class="card shadow mb-4 border-left-primary">
    <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">
            <i class="fas fa-robot mr-2"></i>Analyse IA du rapport
        </h6>

        <form method="POST"
              action="{{ route('reports.insight', $report) }}"
              onsubmit="var b=this.querySelector('button');b.disabled=true;b.querySelector('.spinner-border').classList.remove('d-none');">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary">
                <span class="spinner-border spinner-border-sm mr-1 d-none" role="status" aria-hidden="true"></span>
                <i class="fas fa-magic mr-1"></i>
                {{ $report->ai_summary ? 'Régénérer' : 'Générer le résumé' }}
            </button>
        </form>
    </div>

    <div class="card-body">
        @if ($report->ai_summary)
            <p class="mb-0 text-gray-800" style="white-space: pre-line; max-width: 75ch;">{{ $report->ai_summary }}</p>
        @else
            <div class="text-center text-gray-600 py-3">
                <i class="fas fa-file-alt fa-2x mb-2 text-gray-400"></i>
                <p class="mb-0">
                    Aucun résumé pour l'instant. Clique sur « Générer le résumé » pour obtenir
                    une analyse des chiffres de ce rapport.
                </p>
            </div>
        @endif
    </div>

    @if ($report->ai_summary && $report->ai_generated_at)
        <div class="card-footer small text-muted">
            <i class="far fa-clock mr-1"></i>Généré le {{ $report->ai_generated_at->format('d/m/Y à H:i') }}
        </div>
    @endif
</div>