<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('2fa.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = $request->session()->get('2fa.user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate([
            'code'          => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user  = User::findOrFail($userId);
        $valid = false;

        if ($request->filled('code')) {
            $valid = Google2FA::verifyKey($user->two_factor_secret, $request->code);
        } elseif ($request->filled('recovery_code')) {
            $codes = $user->two_factor_recovery_codes ?? [];

            if (in_array($request->recovery_code, $codes, true)) {
                $valid = true;
                $user->forceFill([
                    'two_factor_recovery_codes' => array_values(array_diff($codes, [$request->recovery_code])),
                ])->save();
            }
        }

        if (! $valid) {
            return back()->withErrors(['code' => 'Code invalide.']);
        }

        $remember = $request->session()->pull('2fa.remember', false);
        $request->session()->forget('2fa.user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
