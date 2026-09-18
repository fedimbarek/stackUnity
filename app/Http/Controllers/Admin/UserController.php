<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with(['neighborhood', 'roles'])
            ->latest()
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'in:resident,gestionnaire,admin'],
        ]);

        if ($user->id === $request->user()->id) {
            return back()->withErrors(['role' => "Tu ne peux pas modifier ton propre rôle."]);
        }

        $user->syncRoles([$request->role]);

        return back()->with('status', 'role-updated');
    }
}