<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Auth\Services\TwoFactorService;
use Modules\Core\Shared\Traits\VerifiesPassword;

class TwoFactorProfileController extends Controller
{
    use VerifiesPassword;

    public function __construct(protected TwoFactorService $twoFactor) {}

    public function setup(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->hasEnabledTwoFactor()) {
            return redirect()->route('user.profile')->with('info', 'Double authentification déjà activée.');
        }

        if (! $user->hasTwoFactorSecret()) {
            $data = $this->twoFactor->enable($user);
            session(['pending_recovery_codes' => $data['recovery_codes']]);

            return view('auth::two-factor.setup', [
                'qrCodeSvg' => $data['qr_url'],
                'recoveryCodes' => $data['recovery_codes'],
            ]);
        }

        $qrCodeSvg = $this->twoFactor->getQrCodeUrl($user);

        // Récupérer les codes depuis la session (1er passage) ou depuis la DB (passages suivants)
        $recoveryCodes = session('pending_recovery_codes', []);

        if (empty($recoveryCodes) && $user->two_factor_recovery_codes) {
            $recoveryCodes = json_decode(Crypt::decrypt($user->two_factor_recovery_codes), true) ?? [];
        }

        return view('auth::two-factor.setup', ['qrCodeSvg' => $qrCodeSvg, 'recoveryCodes' => $recoveryCodes]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => 'required|string|size:6']);

        if ($this->twoFactor->confirm(auth()->user(), $request->code)) {
            return redirect()->route('user.two-factor.recovery-codes')
                ->with('success', 'Double authentification activée !');
        }

        return back()->withErrors(['code' => 'Code invalide. Vérifiez votre application authenticator.']);
    }

    public function disable(Request $request): RedirectResponse
    {
        if ($failed = $this->verifyPasswordOrFail($request)) {
            return $failed;
        }

        $this->twoFactor->disable(auth()->user());

        return redirect()->route('user.profile')->with('success', 'Double authentification désactivée.');
    }

    public function recoveryCodes(): View|RedirectResponse
    {
        $user = auth()->user();

        if (! $user->hasEnabledTwoFactor()) {
            return redirect()->route('user.profile');
        }

        $recoveryCodes = json_decode(Crypt::decrypt($user->two_factor_recovery_codes), true);

        return view('auth::two-factor.recovery-codes', ['recoveryCodes' => $recoveryCodes]);
    }

    public function regenerateRecoveryCodes(): RedirectResponse
    {
        $user = auth()->user();

        if (! $user->hasEnabledTwoFactor()) {
            return redirect()->route('user.profile');
        }

        $newCodes = collect(range(1, 8))
            ->map(fn () => Str::upper(Str::random(8).'-'.Str::random(8)))
            ->all();

        $user->update(['two_factor_recovery_codes' => Crypt::encrypt(json_encode($newCodes))]);

        return redirect()->route('user.two-factor.recovery-codes')
            ->with('success', 'Codes de secours régénérés.');
    }
}
