<x-app-layout>
    <x-slot name="header">Préférences de notification</x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @php
        $old = session()->hasOldInput();
        $db = $old ? old('via_database') : $prefs->via_database;
        $mail = $old ? old('via_mail') : $prefs->via_mail;
        $critical = $old ? old('only_critical') : $prefs->only_critical;
    @endphp

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('notifications.preferences.update') }}">
                @csrf
                @method('PUT')

                <h6 class="font-weight-bold">Où recevoir mes alertes ?</h6>

                <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="via_database" name="via_database" value="1" @checked($db)>
                    <label class="custom-control-label" for="via_database">Dans l'application (historique « Mes notifications »)</label>
                </div>

                <div class="custom-control custom-checkbox mb-2">
                    <input type="checkbox" class="custom-control-input" id="via_mail" name="via_mail" value="1" @checked($mail)>
                    <label class="custom-control-label" for="via_mail">Par e-mail</label>
                </div>

                @error('via_database')
                    <div class="text-danger small mb-2">{{ $message }}</div>
                @enderror

                <hr>

                <h6 class="font-weight-bold">Quelles alertes ?</h6>

                <div class="custom-control custom-checkbox mb-3">
                    <input type="checkbox" class="custom-control-input" id="only_critical" name="only_critical" value="1" @checked($critical)>
                    <label class="custom-control-label" for="only_critical">
                        Seulement les alertes critiques (canicule), pas les alertes de forte chaleur
                    </label>
                </div>

                <button class="btn btn-primary">Enregistrer</button>
                <a href="{{ route('notifications.index') }}" class="btn btn-light">Retour</a>
            </form>
        </div>
    </div>
</x-app-layout>