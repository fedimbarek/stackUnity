<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="{{ route('dashboard') }}">
        <div class="sidebar-brand-icon rotate-n-15"><i class="fas fa-temperature-high"></i></div>
        <div class="sidebar-brand-text mx-3">HeatAlert</div>
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
    @endrole
<li class="nav-item {{ request()->routeIs('outages.*') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('outages.index') }}">
        <i class="fas fa-fw fa-bolt"></i>
        <span>Coupures</span>
    </a>
</li>
    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>