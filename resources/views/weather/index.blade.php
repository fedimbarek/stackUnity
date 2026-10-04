<x-app-layout>
    <x-slot name="header">Prévisions canicule</x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @foreach ($alerts as $a)
        <div class="alert {{ $a->level === 'canicule' ? 'alert-danger' : 'alert-warning' }}">
            <strong>
                <i class="fas fa-exclamation-triangle"></i>
                {{ $a->level === 'canicule' ? 'Alerte canicule' : 'Alerte forte chaleur' }}
                — {{ $a->neighborhood->name }}
            </strong>
            <span class="small text-muted">({{ $a->created_at->format('d/m/Y H:i') }})</span><br>
            {{ $a->message }}
        </div>
    @endforeach

    <div class="d-flex mb-3">
        <form method="POST" action="{{ route('weather.advice') }}" class="mr-2">
            @csrf
            <button class="btn btn-primary"><i class="fas fa-lightbulb"></i> Conseils IA</button>
        </form>

        @role('admin|gestionnaire')
            <form method="POST" action="{{ route('weather.refresh') }}">
                @csrf
                <button class="btn btn-secondary"><i class="fas fa-sync"></i> Actualiser la météo</button>
            </form>
        @endrole
    </div>

    @if (session('advice'))
        <div class="card border-left-primary shadow mb-4">
            <div class="card-body" style="white-space: pre-line;">{{ session('advice') }}</div>
        </div>
    @endif

    @forelse ($forecasts as $f)
        @php
            $border = match ($f->level) {
                'canicule' => 'border-left-danger',
                'warning' => 'border-left-warning',
                default => 'border-left-success',
            };
        @endphp

        <div class="card {{ $border }} shadow mb-3">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="font-weight-bold">{{ $f->date->format('d/m/Y') }}</div>
                        <div class="text-muted small">{{ $f->neighborhood->name }}, {{ $f->neighborhood->city }}</div>

                        @if ($f->level === 'canicule')
                            <span class="badge badge-danger mt-2">Canicule</span>
                        @elseif ($f->level === 'warning')
                            <span class="badge badge-warning mt-2">Forte chaleur</span>
                        @else
                            <span class="badge badge-success mt-2">Normal</span>
                        @endif
                    </div>
                    <div class="col-auto text-right">
                        <div class="h3 mb-0 font-weight-bold">{{ $f->temp_max }}°C</div>
                        <div class="text-muted small">min {{ $f->temp_min }}°C</div>
                    </div>
                </div>

                @foreach ($f->outageRisks as $risk)
                    <div class="alert alert-warning mt-3 mb-0">
                        <strong>Risque de coupure : {{ $risk->risk_level }}</strong><br>
                        {{ $risk->description }}
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="alert alert-info">Aucune prévision disponible pour le moment.</div>
    @endforelse
</x-app-layout>