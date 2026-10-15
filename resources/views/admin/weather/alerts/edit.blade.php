<x-app-layout>
    <x-slot name="header">Modifier l'alerte</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            @include('admin.weather.alerts._form', [
                'action' => route('admin.weather.alerts.update', $alert),
                'method' => 'PUT',
            ])
        </div>
    </div>
</x-app-layout>