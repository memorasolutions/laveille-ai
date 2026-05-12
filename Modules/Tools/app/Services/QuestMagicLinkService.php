<?php

declare(strict_types=1);

namespace Modules\Tools\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Newsletter\Services\BrevoService;
use Modules\Tools\Models\QuestMagicToken;

class QuestMagicLinkService
{
    public function __construct(private readonly BrevoService $brevo) {}

    public function generateAndSend(string $email, ?string $ipAddress = null): bool
    {
        $email = mb_strtolower(trim($email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $token = Str::random(48);
        QuestMagicToken::create([
            'email' => $email,
            'token' => $token,
            'expires_at' => now()->addMinutes(30),
            'ip_address' => $ipAddress,
        ]);

        $link = url('/quete/auth/'.$token);

        if (! $this->brevo->isConfigured()) {
            Log::warning('QuestMagicLink: Brevo not configured, link in logs', ['link' => $link]);

            return true;
        }

        $html = $this->buildEmailHtml($link);

        $result = $this->brevo->sendCampaignEmail(
            $email,
            null,
            'Votre lien de connexion à la quête La veille',
            $html
        );

        return (bool) ($result['success'] ?? false);
    }

    public function consume(string $token): ?string
    {
        $row = QuestMagicToken::where('token', $token)->first();

        if (! $row || ! $row->isValid()) {
            return null;
        }

        $row->update(['used_at' => now()]);

        return $row->email;
    }

    private function buildEmailHtml(string $link): string
    {
        $appName = config('app.name', 'La veille');

        return <<<HTML
<!DOCTYPE html>
<html lang="fr-CA"><head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#F0F4F8;font-family:Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 20px;">
<table role="presentation" width="520" style="max-width:520px;background:#fff;border-radius:16px;overflow:hidden;border-top:6px solid #f97316;">
<tr><td style="padding:32px 28px;">
<h1 style="margin:0 0 14px;color:#064E5A;font-size:24px;">🎮 Votre quête vous attend</h1>
<p style="margin:0 0 18px;font-size:16px;color:#333;line-height:1.6;">Cliquez sur ce bouton pour ouvrir votre carnet de bord et reprendre votre quête « Les Sentiers de l'IA ».</p>
<p style="text-align:center;margin:24px 0;">
<a href="{$link}" style="display:inline-block;background:#f97316;color:#fff;padding:14px 28px;border-radius:8px;font-weight:bold;text-decoration:none;font-size:16px;">Ouvrir ma quête →</a>
</p>
<p style="margin:0 0 12px;font-size:13px;color:#666;">Ce lien est valide <strong>30 minutes</strong> et ne fonctionne qu'une seule fois.</p>
<p style="margin:0 0 12px;font-size:13px;color:#666;">Si vous n'avez pas demandé ce lien, ignorez ce message.</p>
<hr style="border:none;border-top:1px solid #e5e7eb;margin:20px 0;">
<p style="font-size:12px;color:#737373;margin:0;">{$appName} · veille IA Québec</p>
</td></tr></table>
</td></tr></table>
</body></html>
HTML;
    }
}
