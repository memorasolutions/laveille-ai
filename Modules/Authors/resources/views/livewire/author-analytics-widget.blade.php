<div>
    <div class="lv-analytics-grid">
        @php
            $cards = [
                ['icon' => '📝', 'label' => 'Articles', 'total' => $postsTotal, 'points' => $postsPath, 'series' => $postsSeries],
                ['icon' => '💬', 'label' => 'Commentaires', 'total' => $commentsTotal, 'points' => $commentsPath, 'series' => $commentsSeries],
                ['icon' => '📬', 'label' => 'Abonnés', 'total' => $subscribersTotal, 'points' => $subscribersPath, 'series' => $subscribersSeries],
            ];
        @endphp
        @foreach($cards as $card)
            <div class="lv-analytics-card">
                <div class="lv-analytics-total">{{ number_format($card['total']) }}</div>
                <div class="lv-analytics-label">{{ $card['icon'] }} {{ $card['label'] }}</div>
                <svg viewBox="0 0 100 32" class="lv-analytics-spark" preserveAspectRatio="none" role="img"
                     aria-label="Évolution {{ strtolower($card['label']) }} sur 30 jours, total {{ $card['total'] }}.">
                    <polyline fill="none" stroke="#9A2A06" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round" points="{{ $card['points'] }}"></polyline>
                    <polyline fill="rgba(154,42,6,0.10)" stroke="none" points="{{ $card['points'] }} 100,30 0,30"></polyline>
                </svg>
                <div class="lv-analytics-sub">30 derniers jours</div>
                <table class="lv-sr-only">
                    <caption>{{ $card['label'] }} par jour sur 30 jours</caption>
                    <thead><tr><th scope="col">Jour</th><th scope="col">Valeur</th></tr></thead>
                    <tbody>
                        @foreach($card['series'] as $i => $value)
                            <tr><td>J-{{ 29 - $i }}</td><td>{{ $value }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    <style>
        .lv-analytics-grid { display:grid; gap:16px; grid-template-columns:1fr; }
        @media (min-width:640px){ .lv-analytics-grid{ grid-template-columns:repeat(3,1fr);} }
        .lv-analytics-card { background:#F8FAFB; padding:20px; border-radius:12px; box-shadow:0 2px 8px rgba(6,78,90,0.08); border:1px solid rgba(6,78,90,0.08); }
        .lv-analytics-total { font-size:32px; font-weight:700; color:#064E5A; line-height:1; }
        .lv-analytics-label { font-size:14px; color:#3F4554; margin:6px 0 12px; font-weight:600; }
        .lv-analytics-spark { width:100%; height:40px; display:block; }
        .lv-analytics-sub { font-size:11px; color:#5A6270; margin-top:6px; }
        .lv-sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
    </style>
</div>
