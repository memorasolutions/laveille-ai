<!DOCTYPE html>
<html lang="fr-CA">
<head>
    <meta charset="utf-8">
    <title>Health-check annuaire</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; background:#F9FAFB; padding:24px; color:#1A1D23;">
    <div style="max-width:680px; margin:0 auto; background:#fff; border-radius:12px; padding:32px; box-shadow:0 2px 12px rgba(0,0,0,.05);">
        <h1 style="font-size:1.4rem; margin:0 0 8px; color:#064E5A;">🩺 Health-check annuaire</h1>
        <p style="color:#52586a; margin:0 0 20px; font-size:0.9rem;">
            Vérification HTTP HEAD de <strong>{{ $totalChecked }}</strong> outils published —
            <strong>{{ count($suspects) }}</strong> suspect(s) à vérifier manuellement.
        </p>

        <div style="background:#F1F3F5; border-radius:8px; padding:16px 20px; margin-bottom:24px;">
            <h2 style="font-size:1rem; margin:0 0 12px;">Statistiques</h2>
            <table style="width:100%; font-size:0.875rem; border-collapse:collapse;">
                @foreach($stats as $cat => $n)
                    @php
                        $color = match($cat) {
                            'ok', 'redirect' => '#16A34A',
                            'cloudflare_block' => '#F59E0B',
                            'client_error', 'server_error', 'refused', 'dns', 'timeout' => '#DC2626',
                            default => '#6B7280',
                        };
                        $label = match($cat) {
                            'ok' => '✅ OK (2xx)',
                            'redirect' => '↪️ Redirect (3xx)',
                            'cloudflare_block' => '🛡️ Cloudflare/anti-bot (403/503)',
                            'client_error' => '⚠️ Client error (4xx)',
                            'server_error' => '🔥 Server error (5xx)',
                            'refused' => '❌ Connection refused',
                            'dns' => '❌ DNS NXDOMAIN',
                            'timeout' => '⏱️ Timeout',
                            default => $cat,
                        };
                    @endphp
                    <tr>
                        <td style="padding:4px 0; color:{{ $color }};">{{ $label }}</td>
                        <td style="padding:4px 0; text-align:right; font-weight:600;">{{ $n }}</td>
                    </tr>
                @endforeach
            </table>
        </div>

        <h2 style="font-size:1rem; margin:0 0 12px; color:#064E5A;">⚠️ Outils à vérifier manuellement ({{ count($suspects) }})</h2>
        <p style="color:#52586a; font-size:0.85rem; margin:0 0 12px;">
            Connection refused / DNS NXDOMAIN / 4xx / 5xx / timeout. Cloudflare/anti-bot exclus (faux positifs).
        </p>

        <table style="width:100%; font-size:0.8rem; border-collapse:collapse; margin-bottom:24px;">
            <thead>
                <tr style="background:#F9FAFB; text-align:left;">
                    <th style="padding:8px; border-bottom:2px solid #E5E7EB;">Statut</th>
                    <th style="padding:8px; border-bottom:2px solid #E5E7EB;">Outil</th>
                    <th style="padding:8px; border-bottom:2px solid #E5E7EB;">URL</th>
                    <th style="padding:8px; border-bottom:2px solid #E5E7EB;">Détail</th>
                </tr>
            </thead>
            <tbody>
                @foreach(array_slice($suspects, 0, 50) as $s)
                    @php $tool = $s['tool']; @endphp
                    <tr style="border-bottom:1px solid #F3F4F6;">
                        <td style="padding:6px 8px; white-space:nowrap; font-weight:600; color:#DC2626;">
                            {{ $s['category'] }}{{ $s['status'] ? ' '.$s['status'] : '' }}
                        </td>
                        <td style="padding:6px 8px;">
                            <a href="{{ url('/admin/directory/'.($tool->id ?? '')) }}" style="color:#064E5A; text-decoration:none; font-weight:600;">
                                {{ $tool->name ?? '?' }}
                            </a>
                        </td>
                        <td style="padding:6px 8px;">
                            <a href="{{ $tool->url ?? '#' }}" target="_blank" style="color:#0B7285; text-decoration:none; font-size:0.75rem;">{{ $tool->url ?? '?' }}</a>
                        </td>
                        <td style="padding:6px 8px; color:#6B7280; font-size:0.7rem;">{{ $s['error'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if(count($suspects) > 50)
            <p style="color:#9CA3AF; font-size:0.8rem; font-style:italic;">+ {{ count($suspects) - 50 }} autres outils suspects (limité à 50 dans cet email).</p>
        @endif

        <div style="margin-top:32px; padding-top:20px; border-top:1px solid #E5E7EB; color:#9CA3AF; font-size:0.75rem;">
            <p style="margin:0;">⚙️ Rapport généré par <code>directory:health-check-report</code> — schedule hebdo dimanche 04h UTC.</p>
            <p style="margin:8px 0 0;">Aucun outil n'a été marqué automatiquement. Validation manuelle obligatoire via <code>/admin/directory</code>.</p>
        </div>
    </div>
</body>
</html>
