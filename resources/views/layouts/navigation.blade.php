<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion"
    id="accordionSidebar">

    {{-- LOGO --}}
    <a class="sidebar-brand d-flex align-items-center justify-content-center"
       href="{{ route('dashboard') }}">

        <div class="sidebar-brand-icon rotate-n-15"></div>

        <div class="sidebar-brand-text mx-3">
            <img src="{{ asset('img/logo4.png') }}" height="80" alt="Logo">
        </div>

    </a>


    {{-- DASHBOARD --}}
    <hr class="sidebar-divider my-0">

    <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('dashboard') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Tableau de bord</span>
        </a>
    </li>


    {{-- ADMINISTRATION (admin uniquement) --}}
    @role('admin')

        <hr class="sidebar-divider">

        <div class="sidebar-heading">Administration</div>

        <li class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.users.index') }}">
                <i class="fas fa-fw fa-users"></i>
                <span>Utilisateurs</span>
            </a>
        </li>

        {{-- Gestion des points de fraîcheur --}}
        <li class="nav-item {{ request()->routeIs('admin.cooling-points.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.cooling-points.index') }}">
                <i class="fas fa-fw fa-snowflake"></i>
                <span>Gérer les points de fraîcheur</span>
            </a>
        </li>

        <li class="nav-item {{ request()->routeIs('admin.cooling-point-types.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.cooling-point-types.index') }}">
                <i class="fas fa-fw fa-tags"></i>
                <span>Types de points</span>
            </a>
        </li>

    @endrole


    {{-- RÉSEAU ÉLECTRIQUE --}}
    <hr class="sidebar-divider">

    <div class="sidebar-heading">Réseau électrique</div>

    {{-- Coupures --}}
    <li class="nav-item {{ request()->routeIs('outages.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('outages.index') }}">
            <i class="fas fa-fw fa-bolt"></i>
            <span>Coupures</span>
        </a>
    </li>

    {{-- Points de fraîcheur (tous les rôles) --}}
    @hasanyrole('admin|gestionnaire|resident')
        <li class="nav-item {{ request()->routeIs('cooling-points.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('cooling-points.index') }}">
                <i class="fas fa-fw fa-snowflake"></i>
                <span>Points de Fraîcheur</span>
            </a>
        </li>
    @endhasanyrole


    {{-- MENU MÉTÉO --}}
    @php
        $meteoOpen =
            request()->routeIs('admin.notifications.*') ||
            request()->routeIs('neighborhoods.*') ||
            request()->routeIs('weather.*') ||
            request()->routeIs('admin.weather.*');
    @endphp

    <li class="nav-item {{ $meteoOpen ? 'active' : '' }}">

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

        <div id="collapseMeteo"
             class="collapse {{ $meteoOpen ? 'show' : '' }}"
             data-parent="#accordionSidebar">

            <div class="py-2 collapse-inner rounded meteo-menu">

                {{-- Notification à un quartier (admin) --}}
                @role('admin')
                    <a class="collapse-item {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}"
                       href="{{ route('admin.notifications.broadcast.create') }}">
                        <i class="fas fa-paper-plane fa-sm fa-fw mr-2"></i>
                        Notification à un quartier
                    </a>
                @endrole

                {{-- Quartiers (admin + gestionnaire) --}}
                @role('admin|gestionnaire')
                    <a class="collapse-item {{ request()->routeIs('neighborhoods.*') ? 'active' : '' }}"
                       href="{{ route('neighborhoods.index') }}">
                        <i class="fas fa-map-marker-alt fa-sm fa-fw mr-2"></i>
                        Quartiers
                    </a>
                @endrole

                {{-- Prévisions canicule (tous les connectés) --}}
                <a class="collapse-item {{ request()->routeIs('weather.*') ? 'active' : '' }}"
                   href="{{ route('weather.index') }}">
                    <i class="fas fa-cloud-sun fa-sm fa-fw mr-2"></i>
                    Prévisions canicule
                </a>

                {{-- Gestion météo (admin + gestionnaire) --}}
                @role('admin|gestionnaire')

                    <a class="collapse-item {{ request()->routeIs('admin.weather.forecasts.*') ? 'active' : '' }}"
                       href="{{ route('admin.weather.forecasts.index') }}">
                        <i class="fas fa-temperature-high fa-sm fa-fw mr-2"></i>
                        Gérer les prévisions
                    </a>

                    <a class="collapse-item {{ request()->routeIs('admin.weather.risks.*') ? 'active' : '' }}"
                       href="{{ route('admin.weather.risks.index') }}">
                        <i class="fas fa-plug fa-sm fa-fw mr-2"></i>
                        Risques de coupure
                    </a>

                    <a class="collapse-item {{ request()->routeIs('admin.weather.alerts.*') ? 'active' : '' }}"
                       href="{{ route('admin.weather.alerts.index') }}">
                        <i class="fas fa-bell fa-sm fa-fw mr-2"></i>
                        Alertes météo
                    </a>

                @endrole

                {{-- Seuils d'alerte (admin) --}}
                @role('admin')
                    <a class="collapse-item {{ request()->routeIs('admin.weather.thresholds.*') ? 'active' : '' }}"
                       href="{{ route('admin.weather.thresholds.edit') }}">
                        <i class="fas fa-sliders-h fa-sm fa-fw mr-2"></i>
                        Seuils d'alerte
                    </a>
                @endrole

            </div>
        </div>

    </li>


    {{-- RAPPORTS + ÉQUIPEMENTS (admin + gestionnaire) --}}
    @role('admin|gestionnaire')

        <li class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('reports.index') }}">
                <i class="fas fa-fw fa-file-excel"></i>
                <span>Rapports</span>
            </a>
        </li>

        <li class="nav-item {{ request()->routeIs('admin.equipements.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.equipements.index') }}">
                <i class="fas fa-fw fa-tools"></i>
                <span>Équipements</span>
            </a>
        </li>

    @endrole


    {{-- URGENCES : contacts d'urgence (admin + gestionnaire) --}}
    @role('admin|gestionnaire')

        <hr class="sidebar-divider">

        <div class="sidebar-heading">Urgences</div>

        <li class="nav-item {{ request()->routeIs('admin.contacts.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.contacts.index') }}">
                <i class="fas fa-fw fa-phone-alt"></i>
                <span>Contacts d'urgence</span>
            </a>
        </li>

        <li class="nav-item {{ request()->routeIs('admin.contact-categories.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.contact-categories.index') }}">
                <i class="fas fa-fw fa-tags"></i>
                <span>Catégories</span>
            </a>
        </li>

    @endrole


    {{-- MON COMPTE --}}
    <hr class="sidebar-divider">

    <div class="sidebar-heading">Mon compte</div>

    @php
        $unread = auth()->user()->unreadNotifications()->count();
    @endphp

    <li class="nav-item {{ request()->routeIs('notifications.index') || request()->routeIs('notifications.readAll') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('notifications.index') }}">
            <i class="fas fa-fw fa-envelope"></i>
            <span>Mes notifications</span>

            @if ($unread > 0)
                <span class="badge badge-danger ml-1">{{ $unread }}</span>
            @endif
        </a>
    </li>

    <li class="nav-item {{ request()->routeIs('notifications.preferences.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('notifications.preferences.edit') }}">
            <i class="fas fa-fw fa-cog"></i>
            <span>Préférences</span>
        </a>
    </li>


    {{-- BOUTON COLLAPSE SIDEBAR --}}
    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0"
                id="sidebarToggle"
                type="button"
                aria-label="Réduire la barre latérale">
        </button>
    </div>

</ul>