@extends('layouts.front')
@section('title', 'Accueil')

@section('content')

<header class="masthead">
    <div class="container position-relative">
        <h1>HeatAlert</h1>
        <p>Anticipez les canicules et les coupures de courant dans votre quartier — alertes en temps réel, signalement communautaire, points de fraîcheur à proximité.</p>
        <a href="{{ route('front.map') }}" class="btn-heat me-2"><i class="fas fa-map-marked-alt me-1"></i> Voir la carte en direct</a>
        <a href="{{ route('register') }}" class="btn-heat-outline"><i class="fas fa-user-plus me-1"></i> Créer un compte</a>
                <a href="{{ route('contacts.index') }}" class="btn-heat-outline"><i class="fas fa-phone-alt me-1"></i> Urgences</a>

    </div>
</header>

<section class="stats-bar">
    <div class="container">
        <div class="row">
            <div class="col-6 col-md-3 stat-item">
                <div class="num">{{ $stats['neighborhoods'] }}</div>
                <div class="label">Quartiers couverts</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="num">{{ $stats['outages_tracked'] }}</div>
                <div class="label">Coupures suivies</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="num">{{ $stats['outages_resolved'] }}</div>
                <div class="label">Coupures résolues</div>
            </div>
            <div class="col-6 col-md-3 stat-item">
                <div class="num">{{ $stats['residents'] }}</div>
                <div class="label">Résidents inscrits</div>
            </div>
        </div>
    </div>
</section>

<section class="section-dark text-center" id="about">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2>Pourquoi HeatAlert ?</h2>
                <p>
                    Lors des canicules, les coupures de courant (délestage, surcharge réseau) sont fréquentes mais
                    mal anticipées. Les habitants n'ont pas de vision centralisée des risques, des coupures en cours,
                    ni des solutions immédiates disponibles autour d'eux. HeatAlert centralise tout ça, en s'appuyant
                    sur la communauté du quartier plutôt que sur une annonce officielle souvent absente ou tardive.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="section-light" id="features">
    <div class="container">
        <h2>Fonctionnalités</h2>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-cloud-sun"></i></div>
                    <h5>Alertes Météo</h5>
                    <p>Reçois une alerte automatique dès qu'une canicule est prévue dans ton quartier.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                    <h5>Coupures signalées<span class="badge-live">EN DIRECT</span></h5>
                    <p>Signale une coupure, confirmée automatiquement dès que plusieurs voisins la signalent aussi.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-tree"></i></div>
                    <h5>Points de Fraîcheur</h5>
                    <p>Localise les parcs, salles climatisées et fontaines accessibles près de chez toi.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-lightbulb"></i></div>
                    <h5>Conseils personnalisés</h5>
                    <p>Des recommandations adaptées à ta situation (hydratation, équipements sensibles...).</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-dark" id="how">
    <div class="container">
        <h2 class="text-center mb-5">Comment ça marche ?</h2>
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="step-num">1</div>
                <h5>Inscris-toi</h5>
                <p>Crée ton compte et indique ton quartier ou ta résidence.</p>
            </div>
            <div class="col-md-4">
                <div class="step-num">2</div>
                <h5>Reçois les alertes</h5>
                <p>Sois informé dès qu'une canicule ou une coupure touche ton quartier.</p>
            </div>
            <div class="col-md-4">
                <div class="step-num">3</div>
                <h5>Agis</h5>
                <p>Signale, consulte la carte en direct et trouve un point de fraîcheur proche.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <h2>Rejoins HeatAlert dès maintenant</h2>
        <p class="mb-4">C'est gratuit, et ça ne prend qu'une minute.</p>
        <a href="{{ route('register') }}" class="btn btn-light btn-lg fw-bold">Créer mon compte</a>
    </div>
</section>

<section class="section-light" id="contact">
    <div class="container">
        <h2>Contact</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="contact-card">
                    <i class="fas fa-map-marked-alt"></i>
                    <h6 class="text-uppercase fw-bold">Adresse</h6>
                    <div class="small text-muted">ESPRIT, Ariana, Tunisie</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-card">
                    <i class="fas fa-envelope"></i>
                    <h6 class="text-uppercase fw-bold">Email</h6>
                    <div class="small text-muted">contact@heatalert.tn</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-card">
                    <i class="fas fa-mobile-alt"></i>
                    <h6 class="text-uppercase fw-bold">Téléphone</h6>
                    <div class="small text-muted">+216 00 000 000</div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection