{{-- Module précaution données personnelles (désactivable : réglage directory.privacy_notice_enabled).
     Entrée : $tool. Rien n'est rendu si le module est éteint ou si aucune couche n'est remplie.
     Contraste texte #1F2937 sur #F1F5F9 : voir tests (>= 7:1). Pas d'icône seule : titre visible. --}}
@php($privacyNotice = app(\Modules\Directory\Services\PrivacyNoticeService::class)->forTool($tool))
@if($privacyNotice)
    <aside class="rt-privacy-notice" aria-labelledby="rt-privacy-title-{{ $tool->id }}-{{ $placement ?? 'top' }}" style="margin-top: 14px; background: #F1F5F9; color: #1F2937; border: 1px solid #94A3B8; border-left: 4px solid #334155; border-radius: 10px; padding: 14px 16px; font-size: 0.9rem; line-height: 1.6; text-align: left;">
        <p id="rt-privacy-title-{{ $tool->id }}-{{ $placement ?? 'top' }}" style="margin: 0 0 6px; font-weight: 700; color: #0F172A !important;"><span aria-hidden="true">🛡️</span> {{ __('directory::privacy_notice.title') }}</p>
        @if($privacyNotice['general'])
            <p style="margin: 0 0 6px; color: #1F2937 !important;">{{ $privacyNotice['general'] }}</p>
        @endif
        @if($privacyNotice['verified'])
            <p style="margin: 0 0 6px; color: #1F2937 !important;">{{ $privacyNotice['verified'] }}</p>
        @endif
        @if($privacyNotice['policyUrl'])
            <p style="margin: 0; color: #1F2937 !important;"><a href="{{ $privacyNotice['policyUrl'] }}" target="_blank" rel="noopener noreferrer nofollow" style="color: #1E3A8A !important; text-decoration: underline; text-underline-offset: 2px; font-weight: 600;">{{ __('directory::privacy_notice.policy_link', ['tool' => $tool->name]) }} {{ __('directory::privacy_notice.new_tab') }}</a></p>
        @endif
    </aside>
@endif
