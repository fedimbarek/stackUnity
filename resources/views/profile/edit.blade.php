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

    <div class="card shadow mb-4 border-left-danger">
        <div class="card-body">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>