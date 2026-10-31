<x-app-layout>
    <x-slot name="header">Profil</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            @include('profile.partials.update-password-form')
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <h6 class="m-0 font-weight-bold text-primary mb-3">Authentification à deux facteurs</h6>
            <p class="small">Statut : {{ $user->hasTwoFactorEnabled() ? 'Activée' : 'Désactivée' }}</p>
            <a href="{{ route('two-factor.show') }}" class="btn btn-outline-primary btn-sm">Gérer la 2FA</a>
        </div>
    </div>

    <div class="card shadow mb-4 border-left-danger">
        <div class="card-body">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>