<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Nom complet')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="neighborhood_id" :value="__('Quartier / Résidence')" />
            <select id="neighborhood_id" name="neighborhood_id" required
                    class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="">-- Choisir votre quartier --</option>
                @foreach ($neighborhoods as $n)
                    <option value="{{ $n->id }}" @selected(old('neighborhood_id') == $n->id)>
                        {{ $n->name }} ({{ $n->city }})
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('neighborhood_id')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="phone" :value="__('Téléphone (optionnel)')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Mot de passe')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmer le mot de passe')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md" href="{{ route('login') }}">
                {{ __('Déjà inscrit ?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __("S'inscrire") }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>