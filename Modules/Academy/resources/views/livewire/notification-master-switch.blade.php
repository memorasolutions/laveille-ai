{{-- D02 - Interrupteur maître des notifications courriel (admin). Charte tokens + WCAG. --}}
<div>
    @if (session('academy_master_status'))
        <div role="status" aria-live="polite" style="margin-bottom:18px;padding:12px 16px;border-radius:8px;background-color:#f0fdfa;border:1px solid #99f6e4;color:#0f766e;">
            {{ session('academy_master_status') }}
        </div>
    @endif

    @unless ($persistable)
        <div role="status" aria-live="polite" style="margin-bottom:18px;padding:12px 16px;border-radius:8px;background-color:#fffbeb;border:1px solid #fde68a;color:#92400e;">
            Le module Réglages est désactivé : l'interrupteur est piloté par la variable d'environnement « ACADEMY_NOTIFICATIONS_ENABLED » et ne peut pas être modifié ici.
        </div>
    @endunless

    <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;padding:16px 0;border-bottom:1px solid var(--sys-border, #e5e7eb);">
        <div>
            <div style="font-weight:600;color:var(--sys-text-default, #1A1D23);">Notifications courriel de l'Académie</div>
            <div style="color:var(--sys-text-muted, #6B7280);font-size:14px;">
                Interrupteur maître. Lorsqu'il est désactivé, aucun courriel de notification n'est envoyé (annonces, corrections, rappels). Les préférences individuelles des apprenants restent enregistrées et s'appliqueront dès la réactivation.
            </div>
        </div>

        <button
            type="button"
            wire:click="toggle"
            @disabled(! $persistable)
            role="switch"
            aria-checked="{{ $enabled ? 'true' : 'false' }}"
            aria-label="Activer ou désactiver les notifications courriel de l'Académie"
            style="flex:none;min-width:64px;min-height:44px;border:1px solid {{ $enabled ? '#0d9488' : '#cbd5e1' }};border-radius:9999px;background-color:{{ $enabled ? '#0d9488' : '#e2e8f0' }};color:{{ $enabled ? '#ffffff' : '#475569' }};font-size:13px;font-weight:600;cursor:{{ $persistable ? 'pointer' : 'not-allowed' }};padding:6px 12px;opacity:{{ $persistable ? '1' : '0.6' }};">
            {{ $enabled ? 'Activé' : 'Désactivé' }}
        </button>
    </div>

    <p style="margin-top:20px;color:var(--sys-text-muted, #6B7280);font-size:13px;">
        Ce réglage remplace la variable d'environnement « ACADEMY_NOTIFICATIONS_ENABLED » dès qu'il est défini ici. À l'ouverture publique de l'Académie, activez-le pour que les apprenants reçoivent leurs courriels.
    </p>
</div>
