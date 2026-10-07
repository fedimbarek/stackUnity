<x-app-layout>
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">{{ $coolingPointType->name }}</h1>
        <div class="card shadow mb-4"><div class="card-body">
                <p><strong>Slug :</strong> {{ $coolingPointType->slug }}</p>
                <p><strong>Icône :</strong> <i class="fas {{ $coolingPointType->icon }}"></i> {{ $coolingPointType->icon }}</p>
                <p><strong>Couleur :</strong> <span class="badge" style="background:{{ $coolingPointType->color }}">&nbsp;&nbsp;&nbsp;</span></p>
                <p><strong>Description :</strong> {{ $coolingPointType->description }}</p>
            </div></div>
        <div class="card shadow">
            <div class="card-header"><h6 class="m-0 font-weight-bold text-primary">Points associés ({{ $coolingPointType->coolingPoints->count() }})</h6></div>
            <div class="card-body">
                <ul>
                    @foreach($coolingPointType->coolingPoints as $p)
                        <li>{{ $p->name }} — {{ $p->latitude }}, {{ $p->longitude }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
