<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nouveau risque de coupure</h2>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded p-6">
            @include('admin.weather.risks._form', [
                'action' => route('admin.weather.risks.store'),
                'method' => 'POST',
            ])
        </div>
    </div>
</x-app-layout>