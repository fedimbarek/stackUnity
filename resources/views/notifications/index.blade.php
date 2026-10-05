<x-app-layout>
    <x-slot name="header">Mes notifications</x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (auth()->user()->unreadNotifications()->count() > 0)
        <form method="POST" action="{{ route('notifications.readAll') }}" class="mb-3">
            @csrf
            @method('PUT')
            <button class="btn btn-secondary"><i class="fas fa-check-double"></i> Tout marquer comme lu</button>
        </form>
    @endif

    @forelse ($notifications as $n)
                @php
            $level = $n->data['level'] ?? null;
            $border = $n->read_at
                ? 'border-left-secondary'
                : match ($level) {
                    'canicule' => 'border-left-danger',
                    'info' => 'border-left-info',
                    default => 'border-left-warning',
                };
        @endphp

        <div class="card {{ $border }} shadow mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="font-weight-bold">
                            {{ $n->data['title'] ?? 'Notification' }}
                            @if (! $n->read_at)
                                <span class="badge badge-danger ml-2">Nouvelle</span>
                            @endif
                        </div>
                        <div class="text-muted small">{{ $n->created_at->format('d/m/Y H:i') }}</div>
                    </div>

                    @if (! $n->read_at)
                        <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                            @csrf
                            @method('PUT')
                            <button class="btn btn-sm btn-outline-primary">Marquer comme lue</button>
                        </form>
                    @endif
                </div>

                <p class="mb-0 mt-2">{{ $n->data['message'] ?? '' }}</p>
            </div>
        </div>
    @empty
        <div class="alert alert-info">Aucune notification pour le moment.</div>
    @endforelse

    {{ $notifications->links() }}
</x-app-layout>