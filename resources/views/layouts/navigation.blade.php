<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('dashboard') }}">
        <div class="sidebar-brand-icon rotate-n-15">
            <!-- <i class="fas fa-temperature-high"></i> -->
        </div>

        <div class="sidebar-brand-text mx-3">
            <img src="img/logo4.png" height="80">
        </div>
    </a>

    <hr class="sidebar-divider my-0">

    <!-- Dashboard -->
    <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('dashboard') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Tableau de bord</span>
        </a>
    </li>


    <!-- Administration -->
    @role('admin')
        <hr class="sidebar-divider">
        <div class="sidebar-heading">Administration</div>

        <li class="nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.users.index') }}">
                <i class="fas fa-fw fa-users"></i>
                <span>Utilisateurs</span>
            </a>
        </li>

        <li class="nav-item {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.notifications.broadcast.create') }}">
                <i class="fas fa-fw fa-paper-plane"></i>
                <span>Notification à un quartier</span>
            </a>
        </li>
    {{-- ⚙️ Admin : gestion des points de fraîcheur --}}
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


    <!-- Réseau électrique -->
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Réseau électrique</div>

    <li class="nav-item {{ request()->routeIs('outages.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('outages.index') }}">
            <i class="fas fa-fw fa-bolt"></i>
            <span>Coupures</span>
        </a>
    </li>

    @role('admin|gestionnaire')
        <li class="nav-item {{ request()->routeIs('neighborhoods.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('neighborhoods.index') }}">
                <i class="fas fa-fw fa-map-marker-alt"></i>
                <span>Quartiers</span>
            </a>
        </li>
    @endrole

    @hasanyrole('admin|gestionnaire|resident')
        <li class="nav-item {{ request()->routeIs('cooling-points.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('cooling-points.index') }}">
                <i class="fas fa-fw fa-snowflake"></i>
                <span>Points de Fraîcheur</span>
            </a>
        </li>
    @endhasanyrole


    <!-- Météo -->
    <hr class="sidebar-divider">
    <div class="sidebar-heading">Météo</div>

    <li class="nav-item {{ request()->routeIs('weather.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('weather.index') }}">
            <i class="fas fa-fw fa-cloud-sun"></i>
            <span>Prévisions canicule</span>
        </a>
    </li>


    <!-- Gestion météo -->
    @role('admin|gestionnaire')

        <li class="nav-item {{ request()->routeIs('admin.weather.forecasts.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.weather.forecasts.index') }}">
                <i class="fas fa-fw fa-temperature-high"></i>
                <span>Gérer les prévisions</span>
            </a>
        </li>

        <li class="nav-item {{ request()->routeIs('admin.weather.risks.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.weather.risks.index') }}">
                <i class="fas fa-fw fa-plug"></i>
                <span>Risques de coupure</span>
            </a>
        </li>

        <li class="nav-item {{ request()->routeIs('admin.weather.alerts.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.weather.alerts.index') }}">
                <i class="fas fa-fw fa-bell"></i>
                <span>Alertes météo</span>
            </a>
        </li>

    @endrole


    <!-- Seuils d'alerte -->
    @role('admin')

        <li class="nav-item {{ request()->routeIs('admin.weather.thresholds.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.weather.thresholds.edit') }}">
                <i class="fas fa-fw fa-sliders-h"></i>
                <span>Seuils d'alerte</span>
            </a>
        </li>

    @endrole


    <!-- Rapports -->
    <li class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('reports.index') }}">
            <i class="fas fa-fw fa-file-excel"></i>
            <span>Rapports</span>
        </a>
    </li>


    <!-- Équipements -->
    <li class="nav-item {{ request()->routeIs('admin.equipements.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('admin.equipements.index') }}">
            <i class="fas fa-fw fa-tools"></i>
            <span>Équipements</span>
        </a>
    </li>


    <!-- Contacts d'urgence -->
    @role('admin|gestionnaire')
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


    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
