{{-- resources/views/ai/risk.blade.php --}}
<x-app-layout>
    <x-slot name="header">Risque de coupure par quartier</x-slot>

    @php
        $levels = [
            'critique' => ['color' => 'danger',  'icon' => 'fa-exclamation-circle'],
            'élevé'    => ['color' => 'warning', 'icon' => 'fa-exclamation-triangle'],
            'modéré'   => ['color' => 'info',    'icon' => 'fa-info-circle'],
            'faible'   => ['color' => 'success', 'icon' => 'fa-check-circle'],
        ];
        $counts = collect($ranking)->countBy(fn ($r) => $r['level']);
        $pct = fn ($value, $max) => $max > 0 ? min(100, round($value / $max * 100)) : 0;
    @endphp

    {{-- Résumé par niveau --}}
    <div class="row">
        @foreach ($levels as $name => $cfg)
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-{{ $cfg['color'] }} shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-{{ $cfg['color'] }} text-uppercase mb-1">
                                    {{ ucfirst($name) }}
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ $counts->get($name, 0) }}
                                    <span class="small text-muted font-weight-normal">quartier(s)</span>
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas {{ $cfg['icon'] }} fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Classement --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-chart-line mr-2"></i>Classement des quartiers
            </h6>
            <span class="small text-muted">Du plus risqué au moins risqué</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Quartier</th>
                        <th style="min-width:180px">Score global</th>
                        <th>Niveau</th>
                        <th style="min-width:130px">Historique 30 j</th>
                        <th style="min-width:130px">Chaleur prévue</th>
                        <th style="min-width:130px">Activité en cours</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($ranking as $r)
                    @php
                        $cfg = $levels[$r['level']] ?? $levels['faible'];
                        $f   = $r['factors'];
                    @endphp
                    <tr>
                        <td class="align-middle text-muted">{{ $loop->iteration }}</td>

                        <td class="align-middle font-weight-bold text-gray-800">
                            {{ $r['neighborhood']->name }}
                        </td>

                        <td class="align-middle">
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 mr-2" style="height:10px">
                                    <div class="progress-bar bg-{{ $cfg['color'] }}"
                                         role="progressbar"
                                         style="width: {{ $r['score'] }}%"
                                         aria-valuenow="{{ $r['score'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="small font-weight-bold text-gray-700" style="min-width:48px">
                                    {{ $r['score'] }}<span class="text-muted font-weight-normal">/100</span>
                                </span>
                            </div>
                        </td>

                        <td class="align-middle">
                            <span class="badge badge-{{ $cfg['color'] }} px-2 py-1">
                                <i class="fas {{ $cfg['icon'] }} mr-1"></i>{{ ucfirst($r['level']) }}
                            </span>
                        </td>

                        <td class="align-middle">
                            <div class="small text-gray-700 mb-1">{{ $f['historique_30j'] }}<span class="text-muted">/40</span></div>
                            <div class="progress" style="height:6px">
                                <div class="progress-bar bg-secondary" style="width: {{ $pct($f['historique_30j'], 40) }}%"></div>
                            </div>
                        </td>

                        <td class="align-middle">
                            <div class="small text-gray-700 mb-1">{{ $f['chaleur_prevue'] }}<span class="text-muted">/35</span></div>
                            <div class="progress" style="height:6px">
                                <div class="progress-bar bg-secondary" style="width: {{ $pct($f['chaleur_prevue'], 35) }}%"></div>
                            </div>
                        </td>

                        <td class="align-middle">
                            <div class="small text-gray-700 mb-1">{{ $f['activite_en_cours'] }}<span class="text-muted">/25</span></div>
                            <div class="progress" style="height:6px">
                                <div class="progress-bar bg-secondary" style="width: {{ $pct($f['activite_en_cours'], 25) }}%"></div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="fas fa-map-marked-alt fa-2x mb-2 text-gray-400 d-block"></i>
                            Aucun quartier à évaluer. Ajoute des quartiers pour voir leur niveau de risque.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer small text-muted">
            Le score sur 100 additionne trois facteurs : historique des coupures sur 30 jours (40 points),
            chaleur prévue (35 points) et activité en cours (25 points).
        </div>
    </div>
</x-app-layout>