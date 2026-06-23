{{-- V5-c - Réglages des notifications courriel (opt-in/opt-out par type). --}}
<div>
    @if (! $notificationsEnabled)
        {{-- Bandeau info interrupteur maître désactivé (les préférences restent sauvegardées). --}}
        <div role="status" aria-live="polite" style="margin-bottom:18px;padding:12px 16px;border-radius:8px;background-color:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;">
            Les notifications courriel sont temporairement désactivées sur la plateforme. Tes préférences sont enregistrées et s'appliqueront dès leur réactivation.
        </div>
    @endif

    @if (session('academy_notif_prefs_status'))
        <div role="status" aria-live="polite" style="margin-bottom:18px;padding:12px 16px;border-radius:8px;background-color:#f0fdfa;border:1px solid #99f6e4;color:#0f766e;">
            {{ session('academy_notif_prefs_status') }}
        </div>
    @endif

    <ul style="list-style:none;padding:0;margin:0;">
        @foreach (\Modules\Academy\Services\AcademyNotificationService::TYPES as $type)
            @php($on = (bool) ($prefs[$type] ?? true))
            <li style="display:flex;justify-content:space-between;align-items:center;gap:16px;padding:16px 0;border-bottom:1px solid var(--sys-border, #e5e7eb);">
                <div>
                    <div style="font-weight:600;color:var(--sys-text-default, #1A1D23);">{{ $labels[$type]['titre'] ?? $type }}</div>
                    <div style="color:var(--sys-text-muted, #6B7280);font-size:14px;">{{ $labels[$type]['desc'] ?? '' }}</div>
                </div>

                <button
                    type="button"
                    wire:click="toggle('{{ $type }}')"
                    role="switch"
                    aria-checked="{{ $on ? 'true' : 'false' }}"
                    aria-label="Activer ou désactiver : {{ $labels[$type]['titre'] ?? $type }}"
                    style="flex:none;min-width:64px;min-height:44px;border:1px solid {{ $on ? '#0d9488' : '#cbd5e1' }};border-radius:9999px;background-color:{{ $on ? '#0d9488' : '#e2e8f0' }};color:{{ $on ? '#ffffff' : '#475569' }};font-size:13px;font-weight:600;cursor:pointer;padding:6px 12px;">
                    {{ $on ? 'Activé' : 'Désactivé' }}
                </button>
            </li>
        @endforeach
    </ul>

    <p style="margin-top:20px;color:var(--sys-text-muted, #6B7280);font-size:13px;">
        Certaines notifications importantes (corrections, fin de cours) sont activées par défaut. Vous gardez le contrôle total de vos courriels.
    </p>
</div>
