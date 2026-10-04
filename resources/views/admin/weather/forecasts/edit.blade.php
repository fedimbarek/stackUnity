<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Modifier la prévision</h2>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded p-6">
            @include('admin.weather.forecasts._form', [
                'action' => route('admin.weather.forecasts.update', $forecast),
                'method' => 'PUT',
            ])
        </div>
    </div>
</x-app-layout>