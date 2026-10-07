<?php

namespace App\Http\Controllers;

class UserNotificationController extends Controller
{
    /**
     * Liste des notifications de l'utilisateur connecté.
     */
    public function index()
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->paginate(10);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Marquer une notification comme lue.
     * Elle est cherchée dans les notifications de l'utilisateur connecté,
     * donc impossible de lire celles d'un autre utilisateur (404).
     */
    public function read(string $id)
    {
        auth()->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail()
            ->markAsRead();

        return back();
    }

    /**
     * Bouton « Tout marquer comme lu ».
     */
    public function readAll()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Toutes les notifications sont marquées comme lues.');
    }
}