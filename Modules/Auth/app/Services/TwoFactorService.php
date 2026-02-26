<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(protected Google2FA $google2fa) {}

    public function enable(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        $recoveryCodes = collect(range(1, 8))->map(
            fn () => Str::upper(Str::random(8).'-'.Str::random(8))
        )->all();

        $user->update([
            'two_factor_secret' => Crypt::encrypt($secret),
            'two_factor_recovery_codes' => Crypt::encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => null,
        ]);

        return [
            'secret' => $secret,
            'qr_url' => $this->getQrCodeUrl($user),
            'recovery_codes' => $recoveryCodes,
        ];
    }

    public function confirm(User $user, string $code): bool
    {
        if ($this->verify($user, $code)) {
            $user->update(['two_factor_confirmed_at' => now()]);

            return true;
        }

        return false;
    }

    public function disable(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    public function verify(User $user, string $code): bool
    {
        if (empty($user->two_factor_secret)) {
            return false;
        }

        $decryptedSecret = Crypt::decrypt($user->two_factor_secret);

        return (bool) $this->google2fa->verifyKey($decryptedSecret, $code);
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (empty($user->two_factor_recovery_codes)) {
            return false;
        }

        /** @var array<string> $recoveryCodes */
        $recoveryCodes = json_decode(Crypt::decrypt($user->two_factor_recovery_codes), true);

        if (! in_array($code, $recoveryCodes, true)) {
            return false;
        }

        $remaining = array_values(array_diff($recoveryCodes, [$code]));

        $user->update([
            'two_factor_recovery_codes' => Crypt::encrypt(json_encode($remaining)),
        ]);

        return true;
    }

    public function getQrCodeUrl(User $user): string
    {
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            Crypt::decrypt((string) $user->two_factor_secret)
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd
        );

        $svgContent = (new Writer($renderer))->writeString($qrCodeUrl);

        return 'data:image/svg+xml;base64,'.base64_encode($svgContent);
    }
}
