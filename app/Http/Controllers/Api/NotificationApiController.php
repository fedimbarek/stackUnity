<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BroadcastRequest;
use App\Http\Requests\NotificationPreferenceRequest;
use App\Jobs\SendBroadcastJob;
use App\Models\User;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    // GET /api/notifications
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()->paginate(15)->through(fn ($n) => [
            'id' => $n->id,
            'title' => $n->data['title'] ?? null,
            'message' => $n->data['message'] ?? null,
            'level' => $n->data['level'] ?? null,
            'read' => $n->read_at !== null,
            'created_at' => $n->created_at->toIso8601String(),
        ]);

        return response()->json(
            ['unread_count' => $user->unreadNotifications()->count()] + $notifications->toArray()
        );
    }

    // PUT /api/notifications/{id}/read
    public function read(Request $request, string $id)
    {
        $request->user()->notifications()->where('id', $id)->firstOrFail()->markAsRead();

        return response()->json(['message' => 'Notification marquée comme lue.']);
    }

    // GET /api/notifications/preferences
    public function preferences(Request $request)
    {
        $p = $request->user()->preferences();

        return response()->json(['data' => [
            'via_database' => $p->via_database,
            'via_mail' => $p->via_mail,
            'only_critical' => $p->only_critical,
        ]]);
    }

    // PUT /api/notifications/preferences  (envoyer les trois champs)
    public function updatePreferences(NotificationPreferenceRequest $request)
    {
        $user = $request->user();
        $current = $user->preferences();

        $p = $user->notificationPreference()->updateOrCreate([], [
            'via_database' => $request->boolean('via_database', $current->via_database),
            'via_mail' => $request->boolean('via_mail', $current->via_mail),
            'only_critical' => $request->boolean('only_critical', $current->only_critical),
        ]);

        return response()->json([
            'message' => 'Préférences enregistrées.',
            'data' => [
                'via_database' => $p->via_database,
                'via_mail' => $p->via_mail,
                'only_critical' => $p->only_critical,
            ],
        ]);
    }

    // POST /api/notifications/broadcast (admin)
    public function broadcast(BroadcastRequest $request)
    {
        $data = $request->validated();

        $recipients = User::where('neighborhood_id', $data['neighborhood_id'])->count();

        SendBroadcastJob::dispatch((int) $data['neighborhood_id'], $data['title'], $data['message']);

        return response()->json([
            'message' => 'Notification en cours d\'envoi.',
            'recipients' => $recipients,
        ], 202);
    }
}