<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationPreferenceRequest;

class NotificationPreferenceController extends Controller
{
    public function edit()
    {
        return view('notifications.preferences', ['prefs' => auth()->user()->preferences()]);
    }

    public function update(NotificationPreferenceRequest $request)
    {
        auth()->user()->notificationPreference()->updateOrCreate([], [
            'via_database' => $request->boolean('via_database'),
            'via_mail' => $request->boolean('via_mail'),
            'only_critical' => $request->boolean('only_critical'),
        ]);

        return redirect()->route('notifications.preferences.edit')
            ->with('status', 'Préférences enregistrées.');
    }
}