<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BroadcastRequest;
use App\Jobs\SendBroadcastJob;
use App\Models\Neighborhood;
use App\Models\User;

class BroadcastController extends Controller
{
    public function create()
    {
        return view('admin.notifications.broadcast', [
            'neighborhoods' => Neighborhood::orderBy('name')->get(),
        ]);
    }

    public function store(BroadcastRequest $request)
    {
        $data = $request->validated();

        $count = User::where('neighborhood_id', $data['neighborhood_id'])->count();

        SendBroadcastJob::dispatch((int) $data['neighborhood_id'], $data['title'], $data['message']);

        return redirect()->route('admin.notifications.broadcast.create')
            ->with('status', "Notification envoyée à {$count} habitant(s) du quartier.");
    }
}