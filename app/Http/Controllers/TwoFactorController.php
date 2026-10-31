<?php

namespace App\Http\Controllers;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PragmaRX\Google2FALaravel\Facade as Google2FA;

class TwoFactorController extends Controller
{
    public function show(Request $request): View
    {
        $user  = $request->user();
        $qrSvg = null;

        if ($user->two_factor_secret && ! $user->two_factor_confirmed_at) {
            $url    = Google2FA::getQRCodeUrl(config('app.name'), $user->email, $user->two_factor_secret);
            $writer = new Writer(new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd()));
            $qrSvg  = preg_replace('/^<\?xml.*?\?>\s*/', '', $writer->writeString($url));
        }

        return view('profile.two-factor', compact('user', 'qrSvg'));
    }

    public function enable(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'two_factor_secret'         => Google2FA::generateSecretKey(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();

        return redirect()->route('two-factor.show');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $user = $request->user();

        if (! $user->two_factor_secret || ! Google2FA::verifyKey($user->two_factor_secret, $request->code)) {
            return back()->withErrors(['code' => 'Code invalide, réessaie.']);
        }

        $user->forceFill([
            'two_factor_recovery_codes' => $this->generateRecoveryCodes(),
            'two_factor_confirmed_at'   => now(),
        ])->save();

        return redirect()->route('two-factor.show')->with('status', '2FA activée avec succès.');
    }

    public function regenerate(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasTwoFactorEnabled(), 403);

        $request->user()->forceFill([
            'two_factor_recovery_codes' => $this->generateRecoveryCodes(),
        ])->save();

        return redirect()->route('two-factor.show')->with('status', 'Nouveaux codes de récupération générés.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $request->user()->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ])->save();

        return redirect()->route('two-factor.show')->with('status', '2FA désactivée.');
    }

    private function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::lower(Str::random(10)) . '-' . Str::lower(Str::random(10)))
            ->all();
    }
}
