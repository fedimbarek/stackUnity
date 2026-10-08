<x-app-layout>
    <x-slot name="header">Déclencher une alerte</x-slot>

    <div class="card shadow mb-4">
        <div class="card-body">
            @include('admin.weather.alerts._form', [
                'action' => route('admin.weather.alerts.store'),
                'method' => 'POST',
            ])
        </div>
    </div>
</x-app-layout>