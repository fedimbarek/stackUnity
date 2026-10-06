<?php

namespace App\Http\Controllers;

class UserNotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(10);

        return view('notifications.index', compact('notifications'));
    }

    public function read(string $id)
    {
        auth()->user()->notifications()->where('id', $id)->firstOrFail()->markAsRead();

        return back();
    }

    public function readAll()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Toutes les notifications sont marquées comme lues.');
    }
}