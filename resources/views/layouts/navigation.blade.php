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
      <li class="nav-item {{ request()->routeIs('admin.equipements.*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.equipements.index') }}">
                <i class="fas fa-fw fa-tools"></i>
                <span>Équipements</span>
            </a>
        </li>
    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>