<nav class="navbar navbar-expand-lg navbar-dark front-nav fixed-top">
    <div class="container">
        <a class="navbar-brand" href="{{ route('front.home') }}">
            <!-- <i class="fas fa-temperature-high"></i> -->
              <img src="img/logo4.png" height="80">
            HeatAlert
           
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#frontNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="frontNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="{{ route('front.home') }}#features">Fonctionnalités</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('front.home') }}#how">Comment ça marche</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('front.map') }}">Carte en direct</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('front.home') }}#contact">Contact</a></li>

                @auth
                    <li class="nav-item ms-lg-3"><a class="btn-heat" href="{{ route('dashboard') }}">Mon espace</a></li>
                @else
                    <li class="nav-item ms-lg-3"><a class="nav-link btn-heat-outline" href="{{ route('login') }}">Connexion</a></li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0"><a class="btn-heat" href="{{ route('register') }}">S'inscrire</a></li>
                @endauth
            </ul>
        </div>
    </div>
</nav>