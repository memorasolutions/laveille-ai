<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{--
    Bandeau "mode travaux, visible par toi seul" (2026-09-19). Injecté par
    PublicToolController::show() juste après <body> UNIQUEMENT quand la page réelle de l'outil
    est servie pendant que celui-ci est en maintenance publique (contournement superadmin ou
    jeton d'aperçu) - pour que le propriétaire n'oublie jamais que le public, lui, reçoit un 503.
    Palette reprise telle quelle de .lv-uc__reassurance (under-construction.blade.php) : déjà en
    usage dans ce module, AAA.
--}}
<div class="lv-tools-preview-banner" role="status" aria-live="polite">
    <span aria-hidden="true">🔧</span>
    <span>{{ __('Mode travaux : cet outil est fermé au public, tu es la seule personne à le voir ainsi.') }}</span>
</div>
<style>
.lv-tools-preview-banner {
    position: sticky;
    top: 0;
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.65rem 1rem;
    background: #FFFBEB;
    color: #78350F;
    border-bottom: 2px solid #FDE68A;
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    font-size: 0.9rem;
    font-weight: 700;
    line-height: 1.4;
    text-align: center;
}
</style>
