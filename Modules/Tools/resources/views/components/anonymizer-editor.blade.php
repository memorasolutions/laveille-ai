{{-- Composant ÉDITEUR D'ANONYMISATION réutilisable (DRY).
     Utilisé par /outils/anonymiseur ET par le panneau « Anonymiser » du constructeur de prompts.
     Le JS partagé (anonymizer-core/rich/ui.js, via @include('tools::partials.anonymizer-scripts'))
     s'initialise sur ces éléments. Slot `previewActions` = boutons sous le panneau de droite (diffèrent par page).
     Auteur: MEMORA solutions, https://memora.solutions --}}
@props(['previewActions' => null])

{{-- Barre d'outils : 1 action primaire + menu « Actions » --}}
<div class="anon-toolbar" role="toolbar" aria-label="{{ __('Actions d\'anonymisation') }}">
    <button type="button" id="btnDetectAnonAll" class="anon-btn">🕵️ {{ __('Détecter et anonymiser') }}</button>
    <div class="anon-menu-wrap">
        <button type="button" id="btnActionsMenu" class="anon-btn secondary" aria-haspopup="menu" aria-expanded="false">⋯ {{ __('Actions') }}</button>
        <div id="actionsMenu" class="anon-menu hidden" role="menu" aria-label="{{ __('Plus d\'actions') }}">
            <button type="button" id="btnDetect" class="anon-menu-item" role="menuitem">🔍 {{ __('Détecter seulement') }}</button>
            <button type="button" id="btnAnonymizeAll" class="anon-menu-item" role="menuitem">🕵️ {{ __('Tout anonymiser') }}</button>
            <button type="button" id="btnAnonymizeSelection" class="anon-menu-item" role="menuitem" title="{{ __('Sélectionnez un passage dans votre texte, puis cliquez') }}">✍️ {{ __('Anonymiser la sélection') }}</button>
            <button type="button" id="btnEditText" class="anon-menu-item" role="menuitem">✏️ {{ __('Modifier le texte') }}</button>
            <button type="button" id="btnModeToggle" class="anon-menu-item" role="menuitem" aria-pressed="false" title="{{ __('Basculer entre faux noms réalistes et jetons balisés stables') }}">🎭 {{ __('Réaliste') }}</button>
            <button type="button" id="btnResetAll" class="anon-menu-item" role="menuitem" title="{{ __('Efface le masquage mais garde votre texte') }}">↺ {{ __('Réinitialiser le masquage') }}</button>
            <button type="button" id="btnForgetAll" class="anon-menu-item" role="menuitem" title="{{ __('Efface tout (texte + table de correspondance) de ce navigateur') }}">🗑️ {{ __('Oublier mes données') }}</button>
        </div>
    </div>
</div>
<p id="anonModeHint" class="anon-help" style="display:none;margin-top:.5rem;">🏷️ <strong>{{ __('Mode jetons stables') }}</strong> : {{ __('vos données deviennent des balises comme [PERSONNE_1]. Ajoutez à votre demande à l\'IA : « garde les jetons entre crochets intacts ». Restauration la plus fiable même si l\'IA reformule beaucoup.') }}</p>

{{-- Bascule de vue : Éditeur / Split / Aperçu --}}
<div style="display:flex;align-items:center;gap:.4rem;flex-wrap:wrap;">
    <div class="anon-viewswitch" role="group" aria-label="{{ __('Affichage des volets') }}">
        <button type="button" data-view="editor" aria-pressed="false">✍️ {{ __('Éditeur') }}</button>
        <button type="button" data-view="split" aria-pressed="true">⬓ {{ __('Split') }}</button>
        <button type="button" data-view="preview" aria-pressed="false">👁️ {{ __('Aperçu') }}</button>
    </div>
</div>

<div class="anon-grid">
    {{-- Volet gauche : éditeur annoté --}}
    <div class="anon-pane-editor">
        <div class="anon-pane-head">
            <label class="anon-pane-label" for="anonSource">{{ __('Votre texte') }}</label>
        </div>
        <div id="anonEditorWrap" class="mode-edit">
            <div id="anonSource" class="anon-textarea anon-rich" contenteditable="true" role="textbox" aria-multiline="true" aria-label="{{ __('Votre texte – la mise en forme (gras, listes…) est conservée au collage') }}" data-placeholder="{{ __('Collez ou écrivez votre texte ici…') }}"></div>
            <div id="anonAnnotated" tabindex="0" role="textbox" aria-label="{{ __('Texte annoté – cliquez une entité pour l\'anonymiser') }}"></div>
        </div>
        <div class="anon-legend" aria-hidden="true">
            <span><span class="anon-cand">{{ __('souligné') }}</span> = {{ __('sera anonymisé') }}</span>
            <span><span class="anon-anon">{{ __('surligné') }}</span> = {{ __('anonymisé') }}</span>
        </div>
    </div>

    {{-- Volet droit : aperçu anonymisé --}}
    <div class="anon-pane-preview">
        <div class="anon-pane-head">
            <label class="anon-pane-label" for="anonOutput">{{ __('Texte anonymisé') }}</label>
            <button type="button" id="btnCopyAnonTop" class="anon-copy-inline" aria-label="{{ __('Copier le texte anonymisé') }}">📋 {{ __('Copier') }}</button>
        </div>
        <div class="anon-output-wrap">
            <div id="anonOutput" class="anon-textarea anon-out-rich" role="textbox" aria-readonly="true" tabindex="0" aria-label="{{ __('Texte anonymisé – valeurs surlignées') }}"></div>
        </div>
        @isset($previewActions)
            <div class="anon-actions">{{ $previewActions }}</div>
        @endisset
    </div>
</div>

{{-- Bulle contextuelle de sélection (overlay fixed) --}}
<div id="anonSelBubble" class="anon-sel-bubble hidden" role="tooltip">
    <div class="anon-sel-main">
        <button type="button" id="anonSelBubbleBtn">🕵️ {{ __('Anonymiser') }}</button>
        <button type="button" id="anonSelBubbleCustomBtn" title="{{ __('Choisir ma propre valeur de remplacement') }}" aria-label="{{ __('Valeur personnalisée') }}">✎ {{ __('Ma valeur') }}</button>
    </div>
    <div id="anonSelBubbleCustom" class="anon-sel-custom hidden">
        <input type="text" id="anonSelBubbleInput" placeholder="{{ __('Votre valeur…') }}" aria-label="{{ __('Valeur de remplacement personnalisée') }}">
        <button type="button" id="anonSelBubbleConfirm" aria-label="{{ __('Confirmer la valeur') }}">✓</button>
    </div>
</div>

{{-- Popover sur une occurrence déjà anonymisée --}}
<div id="anonOccPopover" class="anon-sel-bubble hidden" role="dialog" aria-label="{{ __('Options de cette occurrence') }}">
    <div class="anon-sel-main">
        <button type="button" id="anonOccValue" title="{{ __('Saisir ma propre valeur (remplace partout)') }}">✎ {{ __('Ma valeur') }}</button>
        <button type="button" id="anonOccDiff" title="{{ __('Une valeur différente uniquement pour cette occurrence-ci') }}">🔀 {{ __('Seulement ici') }}</button>
        <button type="button" id="anonOccCancel" title="{{ __('Annuler l\'anonymisation') }}">↩︎ {{ __('Annuler') }}</button>
    </div>
    <div id="anonOccCustom" class="anon-sel-custom hidden">
        <input type="text" id="anonOccInput" placeholder="{{ __('Valeur pour cette occurrence…') }}" aria-label="{{ __('Valeur de remplacement pour cette occurrence') }}">
        <button type="button" id="anonOccConfirm" aria-label="{{ __('Confirmer') }}">✓</button>
    </div>
</div>

{{-- Tooltip accessible (WCAG 1.4.13) --}}
<div id="anonTip" class="anon-tip hidden" role="tooltip"></div>
