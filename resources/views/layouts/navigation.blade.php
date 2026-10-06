<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    {{-- ========================================================= --}}
    {{-- LOGO --}}
    {{-- ========================================================= --}}

    <a class="sidebar-brand d-flex align-items-center justify-content-center"
       href="{{ route('dashboard') }}">

        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-temperature-high"></i>
        </div>

        <div class="sidebar-brand-text mx-3">
            <img src="{{ asset('img/logo4.png') }}" height="80">
        </div>

    </a>


    {{-- ========================================================= --}}
    {{-- DASHBOARD --}}
    {{-- ========================================================= --}}

    <hr class="sidebar-divider my-0">

    <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">

        <a class="nav-link" href="{{ route('dashboard') }}">

            <i class="fas fa-fw fa-tachometer-alt"></i>

            <span>Tableau de bord</span>

        </a>

    </li>


    {{-- ========================================================= --}}
    {{-- ADMINISTRATION --}}
    {{-- ========================================================= --}}

    @role('admin')

        <hr class="sidebar-divider">

        <div class="sidebar-heading">
            Administration
        </div>


        {{-- Utilisateurs --}}
        <li class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">

            <a class="nav-link"
               href="{{ route('admin.users.index') }}">

                <i class="fas fa-fw fa-users"></i>

                <span>Utilisateurs</span>

            </a>

        </li>

    @endrole


    {{-- ========================================================= --}}
    {{-- RÉSEAU ÉLECTRIQUE --}}
    {{-- ========================================================= --}}

    <hr class="sidebar-divider">

    <div class="sidebar-heading">
        Réseau électrique
    </div>


    {{-- Coupures --}}
    <li class="nav-item {{ request()->routeIs('outages.*') ? 'active' : '' }}">

        <a class="nav-link"
           href="{{ route('outages.index') }}">

            <i class="fas fa-fw fa-bolt"></i>

            <span>Coupures</span>

        </a>

    </li>


    {{-- ========================================================= --}}
    {{-- MENU MÉTÉO --}}
    {{-- ========================================================= --}}

    @php

        /*
        |--------------------------------------------------------------------------
        | Détection des pages appartenant au menu Météo
        |--------------------------------------------------------------------------
        */

        $meteoOpen =
            request()->routeIs('admin.notifications.*') ||
            request()->routeIs('neighborhoods.*') ||
            request()->routeIs('weather.*') ||
            request()->routeIs('admin.weather.forecasts.*') ||
            request()->routeIs('admin.weather.risks.*') ||
            request()->routeIs('admin.weather.alerts.*') ||
            request()->routeIs('admin.weather.thresholds.*');

    @endphp


    <li class="nav-item {{ $meteoOpen ? 'active' : '' }}">

        {{-- ===================================================== --}}
        {{-- BOUTON MÉTÉO --}}
        {{-- ===================================================== --}}

        <a class="nav-link {{ $meteoOpen ? '' : 'collapsed' }}"
           href="#collapseMeteo"
           data-toggle="collapse"
           role="button"
           aria-expanded="{{ $meteoOpen ? 'true' : 'false' }}"
           aria-controls="collapseMeteo">

            <i class="fas fa-fw fa-cloud-sun"></i>

            <span>Météo</span>

            <i class="fas fa-angle-down float-right mt-1"></i>

        </a>


        {{-- ===================================================== --}}
        {{-- CONTENU DU MENU MÉTÉO --}}
        {{-- ===================================================== --}}

        <div id="collapseMeteo"
             class="collapse {{ $meteoOpen ? 'show' : '' }}"
             aria-labelledby="headingMeteo"
             data-parent="#accordionSidebar">

            <div class="py-2 collapse-inner rounded meteo-menu">


                {{-- ================================================= --}}
                {{-- NOTIFICATION À UN QUARTIER --}}
                {{-- ADMIN UNIQUEMENT --}}
                {{-- ================================================= --}}

                @role('admin')

                    <a class="collapse-item
                       {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}"
                       href="{{ route('admin.notifications.broadcast.create') }}">

                        <i class="fas fa-paper-plane fa-sm fa-fw mr-2 text-gray-400"></i>

                        Notification à un quartier

                    </a>

                @endrole


                {{-- ================================================= --}}
                {{-- QUARTIERS --}}
                {{-- ================================================= --}}

                <a class="collapse-item
                   {{ request()->routeIs('neighborhoods.*') ? 'active' : '' }}"
                   href="{{ route('neighborhoods.index') }}">

                    <i class="fas fa-map-marker-alt fa-sm fa-fw mr-2 text-gray-400"></i>

                    Quartiers

                </a>


                {{-- ================================================= --}}
                {{-- PRÉVISIONS CANICULE --}}
                {{-- ================================================= --}}

                <a class="collapse-item
                   {{ request()->routeIs('weather.*') ? 'active' : '' }}"
                   href="{{ route('weather.index') }}">

                    <i class="fas fa-cloud-sun fa-sm fa-fw mr-2 text-gray-400"></i>

                    Prévisions canicule

                </a>


                {{-- ================================================= --}}
                {{-- GESTION DES PRÉVISIONS --}}
                {{-- ADMIN + GESTIONNAIRE --}}
                {{-- ================================================= --}}

                @role('admin|gestionnaire')

                    <a class="collapse-item
                       {{ request()->routeIs('admin.weather.forecasts.*') ? 'active' : '' }}"
                       href="{{ route('admin.weather.forecasts.index') }}">

                        <i class="fas fa-temperature-high fa-sm fa-fw mr-2 text-gray-400"></i>

                        Gérer les prévisions

                    </a>


                    {{-- ================================================= --}}
                    {{-- RISQUES DE COUPURE --}}
                    {{-- ================================================= --}}

                    <a class="collapse-item
                       {{ request()->routeIs('admin.weather.risks.*') ? 'active' : '' }}"
                       href="{{ route('admin.weather.risks.index') }}">

                        <i class="fas fa-plug fa-sm fa-fw mr-2 text-gray-400"></i>

                        Risques de coupure

                    </a>


                    {{-- ================================================= --}}
                    {{-- ALERTES MÉTÉO --}}
                    {{-- ================================================= --}}

                    <a class="collapse-item
                       {{ request()->routeIs('admin.weather.alerts.*') ? 'active' : '' }}"
                       href="{{ route('admin.weather.alerts.index') }}">

                        <i class="fas fa-bell fa-sm fa-fw mr-2 text-gray-400"></i>

                        Alertes météo

                    </a>

                @endrole


                {{-- ================================================= --}}
                {{-- SEUILS D'ALERTE --}}
                {{-- ADMIN UNIQUEMENT --}}
                {{-- ================================================= --}}

                @role('admin')

                    <a class="collapse-item
                       {{ request()->routeIs('admin.weather.thresholds.*') ? 'active' : '' }}"
                       href="{{ route('admin.weather.thresholds.edit') }}">

                        <i class="fas fa-sliders-h fa-sm fa-fw mr-2 text-gray-400"></i>

                        Seuils d'alerte

                    </a>

                @endrole


            </div>

        </div>

    </li>


    {{-- ========================================================= --}}
    {{-- MON COMPTE --}}
    {{-- ========================================================= --}}

    <hr class="sidebar-divider">

    <div class="sidebar-heading">
        Mon compte
    </div>


    {{-- ========================================================= --}}
    {{-- NOTIFICATIONS --}}
    {{-- ========================================================= --}}

    @php

        $unread = auth()->user()
            ->unreadNotifications()
            ->count();

    @endphp


    <li class="nav-item {{ request()->routeIs('notifications.index') ? 'active' : '' }}">

        <a class="nav-link"
           href="{{ route('notifications.index') }}">

            <i class="fas fa-fw fa-envelope"></i>

            <span>Mes notifications</span>


            {{-- Nombre de notifications non lues --}}
            @if ($unread > 0)

                <span class="badge badge-danger ml-1">
                    {{ $unread }}
                </span>

            @endif

        </a>

    </li>


    {{-- ========================================================= --}}
    {{-- PRÉFÉRENCES --}}
    {{-- ========================================================= --}}

    <li class="nav-item
        {{ request()->routeIs('notifications.preferences.*') ? 'active' : '' }}">

        <a class="nav-link"
           href="{{ route('notifications.preferences.edit') }}">

            <i class="fas fa-fw fa-cog"></i>

            <span>Préférences</span>

        </a>

    </li>


    {{-- ========================================================= --}}
    {{-- BOUTON COLLAPSE SIDEBAR --}}
    {{-- ========================================================= --}}

    <hr class="sidebar-divider d-none d-md-block">


    <div class="text-center d-none d-md-inline">

        <button class="rounded-circle border-0"
                id="sidebarToggle">
        </button>

    </div>


</ul>