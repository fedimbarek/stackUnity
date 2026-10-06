@extends('layouts.front')
@section('title', 'Carte en direct')

@section('content')

<header class="masthead" style="min-height: 40vh;">
    <div class="container">
        <h1 style="font-size: 2.2rem;">Carte en direct</h1>
        <p>Les coupures actuellement signalées ou confirmées, tous quartiers confondus.</p>
    </div>
</header>

<section class="section-light" style="padding-top: 50px;">
    <div class="container">
        <div id="publicMap" style="height: 480px; border-radius: 14px; box-shadow: 0 10px 30px rgba(0,0,0,0.08);"></div>

        <div class="row mt-4 text-center">
            <div class="col-md-6">
                <span class="badge rounded-pill" style="background:orange; padding:8px 16px;">● Signalée</span>
            </div>
            <div class="col-md-6">
                <span class="badge rounded-pill" style="background:#e63946; padding:8px 16px;">● Confirmée</span>
            </div>
        </div>

        <p class="text-center text-muted small mt-4">
            Envie d'être alerté en priorité et de signaler une coupure dans ton quartier ?
            <a href="{{ route('register') }}">Crée ton compte</a>.
        </p>
    </div>
</section>

@endsection

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    const map = L.map('publicMap').setView([36.8, 10.18], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    fetch("{{ route('front.map.data') }}")
        .then(r => r.json())
        .then(outages => {
            if (outages.length === 0) return;
            outages.forEach(o => {
                const color = o.status === 'confirmed' ? '#e63946' : 'orange';
                L.circleMarker([o.latitude, o.longitude], { radius: 9, color, fillColor: color, fillOpacity: 0.75 })
                    .addTo(map)
                    .bindPopup(`<strong>${o.neighborhood.name}</strong><br>Statut : ${o.status}`);
            });
        });
</script>
@endpush