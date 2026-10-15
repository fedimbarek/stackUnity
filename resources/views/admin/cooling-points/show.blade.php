<x-app-layout>
    <div class="container-fluid">
        <h1 class="h3 mb-4 text-gray-800">{{ $coolingPoint->name }}</h1>
        <div class="card shadow"><div class="card-body">
                <p><strong>Type :</strong> {{ $coolingPoint->coolingPointType?->name }}</p>
                <p><strong>Latitude :</strong> {{ $coolingPoint->latitude }}</p>
                <p><strong>Longitude :</strong> {{ $coolingPoint->longitude }}</p>
                <p><strong>Adresse :</strong> {{ $coolingPoint->address }}</p>
                <p><strong>Horaires :</strong> {{ $coolingPoint->opening_hours }}</p>
            </div></div>
    </div>
</x-app-layout>
