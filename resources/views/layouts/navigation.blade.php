<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('dashboard') }}">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-temperature-high"></i>
        </div>
        <div class="sidebar-brand-text mx-3">
            <img src="img/logo4.png" height="80">
        </div>
    </a>

    <hr class="sidebar-divider my-0">

    <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('dashboard') }}">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Tableau de bord</span>
        </a>
    </li>

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
    @endrole

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Réseau électrique</div>

    <li class="nav-item {{ request()->routeIs('outages.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('outages.index') }}">
            <i class="fas fa-fw fa-bolt"></i>
            <span>Coupures</span>
        </a>
    </li>

    <li class="nav-item {{ request()->routeIs('neighborhoods.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('neighborhoods.index') }}">
            <i class="fas fa-fw fa-map-marker-alt"></i>
            <span>Quartiers</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Météo</div>

    <li class="nav-item {{ request()->routeIs('weather.*') ? 'active' : '' }}">
        <a class="nav-link" href="{{ route('weather.index') }}">
            <i class="fas fa-fw fa-cloud-sun"></i>
            <span>Prévisions canicule</span>
        </a>
    </li>

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

    @role('admin')
        <li class="nav-item {{ request()->routeIs('admin.weather.thresholds.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.weather.thresholds.edit') }}">
                <i class="fas fa-fw fa-sliders-h"></i>
                <span>Seuils d'alerte</span>
            </a>
        </li>
    @endrole

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Mon compte</div>

    @php $unread = auth()->user()->unreadNotifications()->count(); @endphp

    <li class="nav-item {{ request()->routeIs('notifications.index') ? 'active' : '' }}">
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

    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>