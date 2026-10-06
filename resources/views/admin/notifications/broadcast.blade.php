<x-app-layout>
    <x-slot name="header">Notification manuelle à un quartier</x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-body">
            <p class="text-muted">
                Le message est envoyé à tous les habitants du quartier choisi, selon les canaux qu'ils ont activés.
            </p>

            <form method="POST" action="{{ route('admin.notifications.broadcast.store') }}">
                @csrf

                <div class="form-group">
                    <label>Quartier</label>
                    <select name="neighborhood_id" class="form-control @error('neighborhood_id') is-invalid @enderror">
                        <option value="">-- Choisir --</option>
                        @foreach ($neighborhoods as $n)
                            <option value="{{ $n->id }}" @selected(old('neighborhood_id') == $n->id)>
                                {{ $n->name }} ({{ $n->city }})
                            </option>
                        @endforeach
                    </select>
                    @error('neighborhood_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label>Titre</label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title') }}">
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label>Message</label>
                    <textarea name="message" rows="4"
                              class="form-control @error('message') is-invalid @enderror">{{ old('message') }}</textarea>
                    @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button class="btn btn-danger"><i class="fas fa-paper-plane"></i> Envoyer</button>
            </form>
        </div>
    </div>
</x-app-layout>