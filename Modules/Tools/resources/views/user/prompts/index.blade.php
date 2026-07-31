<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('title', __('Mes prompts') . ' - ' . config('app.name'))

{{-- Round 116 (2026-07-27, passe adversariale) : bibliothèque PRIVÉE d'un utilisateur connecté -
     même nature que « Mes journaux » (Modules/Journal), qui pose déjà page_noindex sur ses 3 vues.
     C'était la seule page de gestion privée du projet sans noindex. robots.txt a bien un
     « Disallow: /user », mais ce n'est PAS équivalent : un Disallow empêche le crawl (donc la
     balise meta n'est jamais lue) sans empêcher l'URL d'être indexée sans extrait si elle est
     découverte via un lien externe. Les 2 couches sont nécessaires (défense en profondeur). --}}
@section('page_noindex', true)

@section('user-content')

@assets
<script src="{{ asset('build/nobleui/plugins/lucide/lucide.min.js') }}"></script>
{{-- Round 112 (2026-07-27, passe adversariale) : le panneau "Mon profil" ci-dessous n'avait
     jamais de garde-fou anti-PII, alors que ses valeurs (profile_role/profile_style/
     profile_constraints) pré-remplissent automatiquement les champs déjà protégés du wizard
     (rounds 109-111) - la SOURCE de la fuite n'était jamais scannée. On charge ici uniquement
     le moteur de détection (anonymizer-core.js), jamais l'éditeur riche complet (inutile sur
     cette page, pas de workflow d'anonymisation ici, juste un avertissement). --}}
<script src="{{ asset('assets/tools/anonymiseur/anonymizer-core.js') }}?v={{ config('version.semver') }}" defer></script>
{{-- Round 114 (2026-07-27, passe adversariale) : i18n du garde-fou (textes auparavant codés en
     dur en français). Bloc inline volontairement AVANT le script deferred - l'inline s'exécute
     au parsing, donc window.promptProfileGuardI18n existe toujours quand le garde-fou démarre
     (même pattern que window.promptBuilderConfig pour prompt-anon-panel.js). --}}
<script>
window.promptProfileGuardI18n = {
    fieldRole: @json(__('Métier / rôle habituel')),
    fieldStyle: @json(__('Style d\'écriture préféré')),
    fieldConstraints: @json(__('Contraintes récurrentes')),
    warning: @json(__('On dirait qu\'il y a des infos personnelles dans « %s ». Retire-les avant d\'enregistrer ton profil - elles seront réutilisées automatiquement dans tes futurs prompts.')),
    close: @json(__('Fermer l\'avertissement')),
};
</script>
<script src="{{ asset('assets/tools/constructeur-prompts/profile-anon-guard.js') }}?v={{ config('version.semver') }}" defer></script>
@endassets

<div x-data="promptsLibrary()" x-cloak x-effect="$nextTick(() => { if (window.lucide) lucide.createIcons() })">

    {{-- En-tête --}}
    <div style="display: flex !important; justify-content: space-between !important; align-items: center !important; flex-wrap: wrap !important; gap: 12px; margin-bottom: 20px;">
        <div>
            <h1 style="font-family: var(--f-heading, 'Plus Jakarta Sans', sans-serif); font-weight: 800; color: var(--c-dark, #1A1D23); margin: 0 0 4px; font-size: 1.6rem;">
                ✨ {{ __('Mes prompts') }}
            </h1>
            {{-- Round 51 (2026-07-27) : compteur rendu côté Alpine (promptCountLabel()) pour rester
                 exact après une suppression ou un retrait de favori sans rechargement de page -
                 sinon il affichait toujours le total figé au chargement initial. --}}
            <p x-text="promptCountLabel()" style="color: var(--c-text-muted, #6E7687); margin: 0;">{{ trans_choice('{0}Aucun prompt sauvegardé.|{1}1 prompt sauvegardé.|[2,*]:count prompts sauvegardés.', $prompts->total(), ['count' => $prompts->total()]) }}</p>
        </div>
        <a href="{{ route('tools.show', ['slug' => 'constructeur-prompts']) }}" class="ct-btn ct-btn-primary d-inline-flex align-items-center gap-2" style="min-height:44px">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>{{ __('Nouveau prompt') }}</span>
        </a>
    </div>

    {{-- Panneau "Mon profil" : pré-remplit les futurs prompts (métier/style/contraintes) --}}
    {{-- Round 115 (2026-07-27, passe adversariale) : @profile-pii-detected.window déplie le
         panneau quand le garde-fou anti-PII (profile-anon-guard.js) détecte une info personnelle.
         Sans ça, la bannière d'avertissement était insérée dans ce conteneur replié (display:none)
         donc jamais visible ni annoncée - précisément dans le cas visé (profil déjà enregistré,
         donc panneau replié par défaut). --}}
    <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; margin-bottom: 20px; overflow: hidden;" x-data="{ open: {{ empty($promptProfile) ? 'true' : 'false' }} }" @profile-pii-detected.window="open = true">
        <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                style="width: 100%; display: flex !important; align-items: center !important; justify-content: space-between !important; gap: 10px; padding: 14px 20px; background: none; border: none; cursor: pointer; text-align: left; min-height: 44px;">
            <span style="font-weight: 700; color: var(--c-dark, #1A1D23); font-size: 15px;">
                👤 {{ __('Mon profil') }} <span style="font-weight: 400; color: var(--c-text-muted, #6E7687); font-size: 13px;">: {{ __('pré-remplit vos futurs prompts') }}</span>
            </span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" :style="open ? 'transform:rotate(180deg);transition:transform .2s;' : 'transition:transform .2s;'"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <div x-show="open" x-transition x-cloak style="padding: 0 20px 20px;">
            <div style="display: grid; gap: 14px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
                <div>
                    <label for="profile_role" style="display: block; font-size: 13px; font-weight: 600; color: var(--c-dark, #1A1D23); margin-bottom: 4px;">{{ __('Métier / rôle habituel') }}</label>
                    <input type="text" id="profile_role" x-model="profileRole" maxlength="80"
                           style="width: 100%; box-sizing: border-box; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px;"
                           placeholder="{{ __('Ex. : enseignant au secondaire') }}">
                </div>
                <div>
                    <label for="profile_style" style="display: block; font-size: 13px; font-weight: 600; color: var(--c-dark, #1A1D23); margin-bottom: 4px;">{{ __('Style d\'écriture préféré') }}</label>
                    <input type="text" id="profile_style" x-model="profileStyle" maxlength="80"
                           style="width: 100%; box-sizing: border-box; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px;"
                           placeholder="{{ __('Ex. : direct, ton chaleureux') }}">
                </div>
                <div style="grid-column: 1 / -1;">
                    <label for="profile_constraints" style="display: block; font-size: 13px; font-weight: 600; color: var(--c-dark, #1A1D23); margin-bottom: 4px;">{{ __('Contraintes récurrentes') }}</label>
                    <textarea id="profile_constraints" x-model="profileConstraints" maxlength="500" rows="2"
                              style="width: 100%; box-sizing: border-box; border: 1px solid #D1D5DB; border-radius: 8px; padding: 10px 12px; font-size: 14px; resize: vertical;"
                              placeholder="{{ __('Ex. : toujours vouvoyer, éviter le jargon technique') }}"></textarea>
                </div>
            </div>
            <button type="button" @click="saveProfile()" :disabled="savingProfile"
                    class="ct-btn ct-btn-primary" style="margin-top: 14px; min-height: 44px;">
                <span x-show="!savingProfile">{{ __('Enregistrer mon profil') }}</span>
                <span x-show="savingProfile" x-cloak>{{ __('Enregistrement...') }}</span>
            </button>
        </div>
    </div>

    {{-- Barre de filtres --}}
    <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 16px 20px; margin-bottom: 20px;">
        <form method="GET" role="search" aria-label="{{ __('Rechercher dans mes prompts') }}">
            <div style="position: relative; margin-bottom: 14px;">
                <label for="prompt-search" style="display: block; font-size: 13px; font-weight: 600; color: var(--c-dark, #1A1D23); margin-bottom: 4px;">{{ __('Rechercher') }}</label>
                <div style="display: flex; gap: 8px;">
                    <input type="search" id="prompt-search" name="search" value="{{ $currentFilters['search'] }}"
                           placeholder="{{ __('Nom ou contenu du prompt...') }}"
                           style="flex: 1; box-sizing: border-box; height: 44px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 12px; font-size: 14px;">
                    <button type="submit" class="ct-btn ct-btn-primary" style="min-height: 44px;">{{ __('Rechercher') }}</button>
                </div>
            </div>

            @if($currentFilters['favorite'])
                <input type="hidden" name="favorite" value="1">
            @endif
            @if(!empty($currentFilters['tag']))
                <input type="hidden" name="tag" value="{{ $currentFilters['tag'] }}">
            @endif
        </form>

        @if(!empty($availableTags))
            <div style="margin-top: 4px;" role="region" aria-label="{{ __('Filtrer par tag') }}">
                <span style="font-size: 12px; font-weight: 700; color: var(--c-text-muted, #6E7687); text-transform: uppercase; letter-spacing: 0.5px;">{{ __('Tags') }} :</span>
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px;">
                    <a href="{{ request()->fullUrlWithQuery(['tag' => null, 'page' => null]) }}"
                       style="display: inline-flex; align-items: center; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; text-decoration: none; min-height: 44px; box-sizing: border-box; {{ empty($currentFilters['tag']) ? 'background: var(--c-primary, #064E5A); color: #fff;' : 'background: #F3F4F6; color: #374151;' }}">
                        {{ __('Tous') }}
                    </a>
                    @foreach($availableTags as $tag)
                        <a href="{{ request()->fullUrlWithQuery(['tag' => $tag, 'page' => null]) }}"
                           style="display: inline-flex; align-items: center; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; text-decoration: none; min-height: 44px; box-sizing: border-box; {{ $currentFilters['tag'] === $tag ? 'background: var(--c-primary, #064E5A); color: #fff;' : 'background: #F3F4F6; color: #374151;' }}">
                            {{ $tag }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div style="margin-top: 14px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <a href="{{ request()->fullUrlWithQuery(['favorite' => $currentFilters['favorite'] ? null : 1, 'page' => null]) }}"
               aria-pressed="{{ $currentFilters['favorite'] ? 'true' : 'false' }}"
               style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; min-height: 44px; box-sizing: border-box; {{ $currentFilters['favorite'] ? 'background: #FEF3C7; color: #78350F;' : 'background: #F3F4F6; color: #374151;' }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="{{ $currentFilters['favorite'] ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                {{ __('Favoris seulement') }}
            </a>
            @if(!empty($currentFilters['search']) || !empty($currentFilters['tag']) || $currentFilters['favorite'])
                {{-- Round 88 (2026-07-27, passe adversariale) : min-height:44px (cible tactile WCAG 2.2
                     AAA SC 2.5.5) + couleur #991B1B au lieu de #DC2626 (contraste sur blanc ~8,3:1 AAA
                     vs ~4,83:1 AA seulement) - token déjà utilisé pour texte danger sur fond clair
                     (charte.css:1009, .alert-danger). --}}
                <a href="{{ request()->url() }}" style="display: inline-flex; align-items: center; font-size: 13px; color: #991B1B; font-weight: 600; text-decoration: none; min-height: 44px; padding: 4px 6px;">
                    {{ __('Effacer les filtres') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Liste des prompts --}}
    @if($prompts->isEmpty())
        <div style="text-align: center; padding: 3rem 1rem; background: #F8FAFC; border: 2px dashed #CBD5E1; border-radius: 12px; color: #1A1D23;">
            @if(!empty($currentFilters['search']) || !empty($currentFilters['tag']) || $currentFilters['favorite'])
                <p style="margin: 0;">{{ __('Aucun prompt ne correspond à ces filtres.') }}</p>
                <a href="{{ request()->url() }}" style="display: inline-flex; align-items: center; color: var(--c-primary, #064E5A); font-weight: 600; min-height: 44px; padding: 4px 6px;">{{ __('Effacer les filtres') }}</a>
            @else
                <h2 style="color: var(--c-primary, #064E5A); font-weight: 700; margin-bottom: 8px;">{{ __('Aucun prompt sauvegardé') }}</h2>
                <p style="margin-bottom: 16px;">{{ __('Créez et sauvegardez votre premier prompt pour le retrouver ici.') }}</p>
                <a href="{{ route('tools.show', ['slug' => 'constructeur-prompts']) }}" class="ct-btn ct-btn-primary" style="min-height: 44px;">{{ __('Créer un prompt') }}</a>
            @endif
        </div>
    @else
        <div style="display: grid; gap: 14px; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));">
            @foreach($prompts as $prompt)
                @php
                    $tagsCsv = implode(', ', $prompt->tags ?? []);
                    $promptActions = [
                        // Round 121 (2026-07-30, passe adversariale) : `open = false` masque le
                        // conteneur x-show="open" du menu, donc le bouton QUI A LE FOCUS clavier au
                        // moment du clic - le navigateur renvoie alors le focus à <body>, sans
                        // annonce. Le round 107 avait corrigé le trajet INVERSE (fermeture du
                        // panneau via restoreFocusIfLost), jamais l'ouverture. openTagsEditor()
                        // déplace le focus dans le champ de tags qui vient d'apparaître.
                        // NB : commentaire PHP (//) et non Blade ({{-- --}}) - on est DANS un bloc
                        // @php, où un commentaire Blade produirait une ParseError.
                        ['label' => __('Modifier les tags'), 'icon' => 'tag', 'alpineClick' => 'open = false; openTagsEditor($el)'],
                        ['divider' => true],
                        ['label' => __('Dupliquer'), 'icon' => 'copy', 'alpineClick' => 'open = false; duplicatePrompt('.json_encode($prompt->public_id).')'],
                        ['label' => __('Réutiliser'), 'icon' => 'rotate-ccw', 'url' => route('tools.show', ['slug' => 'constructeur-prompts', 'edit' => $prompt->public_id])],
                        ['divider' => true],
                        ['label' => __('Supprimer'), 'icon' => 'trash-2', 'danger' => true, 'alpineClick' => "open = false; \$dispatch('open-confirm-global', { message: ".\Illuminate\Support\Js::from(__('Supprimer ce prompt ?')).", callback: () => deletePrompt(".json_encode($prompt->public_id).") })"],
                    ];
                @endphp
                {{-- Round 107 (2026-07-27, passe adversariale) : restoreFocusIfLost() (même principe
                     que _restoreFocusIfLost, constructeur-prompts-core.js, rounds 105-106) - Enregistrer
                     et Annuler masquent (x-show) le panneau qui les contient, ce qui fait perdre le
                     focus clavier vers <body> silencieusement. Ne restaure vers le bouton ⋮ de la
                     carte QUE si le focus est réellement tombé sur <body> (jamais écrasé si l'utilisateur
                     a déjà cliqué ailleurs volontairement). --}}
                <article id="prompt-card-{{ $prompt->public_id }}" x-data="{ editingTags: false, tagsInput: {{ Illuminate\Support\Js::from($tagsCsv) }}, openTagsEditor(el) { this.editingTags = true; this.$nextTick(() => { var t = el.closest('article').querySelector('input[id^=tags-input-]'); if (t) t.focus(); }); }, restoreFocusIfLost(el) { this.$nextTick(() => { if (document.activeElement === document.body) { var t = el.closest('article').querySelector('[x-ref=trigger]'); if (t) t.focus(); } }); } }"
                         style="background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 16px 18px; transition: box-shadow .15s;">
                    <div style="display: flex !important; justify-content: space-between !important; align-items: flex-start !important; gap: 10px;">
                        <h2 style="font-size: 1rem; font-weight: 700; color: var(--c-dark, #1A1D23); margin: 0 0 6px; word-break: break-word;">{{ $prompt->name }}</h2>
                        <button type="button"
                                @click="toggleFavorite({{ json_encode($prompt->public_id) }}, $el)"
                                :aria-pressed="{{ $prompt->is_favorite ? 'true' : 'false' }}"
                                aria-label="{{ $prompt->is_favorite ? __('Retirer des favoris') : __('Ajouter aux favoris') }}"
                                style="flex-shrink: 0; width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; background: none; border: none; cursor: pointer; color: {{ $prompt->is_favorite ? '#D97706' : 'var(--c-text-muted, #52586A)' }};">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="{{ $prompt->is_favorite ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        </button>
                    </div>

                    <p style="font-size: 13px; color: var(--c-text-muted, #6E7687); margin: 0 0 10px; line-height: 1.5;">
                        {{ \Illuminate\Support\Str::limit($prompt->prompt_text, 140) }}
                    </p>

                    @if(!empty($prompt->tags))
                        <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 10px;">
                            @foreach($prompt->tags as $tag)
                                {{-- Round 92 (2026-07-27, passe adversariale) : ces chips de tag dans la
                                     carte (distinctes de la barre de filtres globale déjà corrigée au
                                     round 88) n'avaient ni min-height ni display inline-flex - cible
                                     tactile WCAG 2.2 AAA SC 2.5.5 en échec (~19px de hauteur effective). --}}
                                <a href="{{ request()->fullUrlWithQuery(['tag' => $tag, 'page' => null]) }}"
                                   style="background: #E0F2F1; color: #053D4A; padding: 2px 10px; border-radius: 999px; font-weight: 600; font-size: 11px; text-decoration: none; min-height: 44px; box-sizing: border-box; display: inline-flex; align-items: center;">
                                    {{ $tag }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div style="display: flex !important; justify-content: space-between !important; align-items: center !important; font-size: 12px; color: var(--c-text-muted, #6E7687); margin-top: 4px;">
                        <span title="{{ $prompt->updated_at }}">{{ __('Mis à jour') }} {{ $prompt->updated_at->diffForHumans() }}</span>
                        @include('core::components.action-menu', ['actions' => $promptActions])
                    </div>

                    {{-- Édition inline des tags (pas de window.prompt() natif) --}}
                    <div x-show="editingTags" x-cloak x-transition style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #F3F4F6;">
                        <label for="tags-input-{{ $prompt->public_id }}" style="display: block; font-size: 12px; font-weight: 600; color: var(--c-dark, #1A1D23); margin-bottom: 4px;">{{ __('Tags (séparés par des virgules, max 5 tags de 30 caractères max chacun)') }}</label>
                        <div style="display: flex; gap: 8px;">
                            <input type="text" id="tags-input-{{ $prompt->public_id }}" x-model="tagsInput"
                                   style="flex: 1; box-sizing: border-box; height: 40px; border: 1px solid #D1D5DB; border-radius: 8px; padding: 0 10px; font-size: 13px;">
                            {{-- Round 96 (2026-07-27, passe adversariale) : le panneau ne se ferme
                                 plus qu'APRÈS résolution de saveTags() (attend la Promise), et
                                 seulement en cas de succès - sinon il reste ouvert pour corriger. --}}
                            <button type="button" @click="(async () => { if (await saveTags({{ json_encode($prompt->public_id) }}, tagsInput)) { editingTags = false; restoreFocusIfLost($el); } })()"
                                    class="ct-btn ct-btn-primary ct-btn-sm" style="min-height:44px;">{{ __('Enregistrer') }}</button>
                            <button type="button" @click="tagsInput = {{ Illuminate\Support\Js::from($tagsCsv) }}; editingTags = false; restoreFocusIfLost($el)"
                                    class="ct-btn ct-btn-ghost ct-btn-sm" style="min-height:44px;">{{ __('Annuler') }}</button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if($prompts->hasPages())
            <div style="margin-top: 24px; display: flex; justify-content: center;">
                {{ $prompts->appends($currentFilters)->links() }}
            </div>
        @endif
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() { if (window.lucide) lucide.createIcons(); });
if (document.readyState !== 'loading' && window.lucide) { lucide.createIcons(); }

function promptsLibrary() {
    return {
        profileRole: {{ Illuminate\Support\Js::from($promptProfile['profile_role'] ?? '') }},
        profileStyle: {{ Illuminate\Support\Js::from($promptProfile['profile_style'] ?? '') }},
        profileConstraints: {{ Illuminate\Support\Js::from($promptProfile['profile_constraints'] ?? '') }},
        savingProfile: false,
        // Round 56 (2026-07-27) : garde anti double-invocation - duplicatePrompt() n'avait aucun
        // verrou (contrairement à d'autres actions du site), et le menu ⋮ se referme au clic sans
        // désactiver le déclencheur. Un 2e clic pendant la fenêtre de 700ms avant reload créait un
        // 2e SavedPrompt distinct côté serveur (SavedPromptController::duplicate() n'a aucune
        // contrainte d'unicité) - N clics = N copies réelles en base, chacune confirmée par son
        // propre toast succès, sans avertissement d'action redondante.
        _duplicatingIds: new Set(),
        // Round 58 (2026-07-27) : même manque que round 56, mais sur deletePrompt() - la modale
        // de confirmation globale (confirm-modal.blade.php) ne désactive pas synchroniquement son
        // bouton « Confirmer » (x-show/x-transition seulement) : un double-clic déclenche deux
        // DELETE quasi simultanés. Le 1er réussit (204) ; le 2e tombe sur firstOrFail() déjà
        // supprimé côté serveur → 404 → message "Erreur lors de la suppression." affiché à tort
        // alors que la suppression a pleinement réussi.
        _deletingIds: new Set(),
        // Round 51 (2026-07-27) : compteur d'en-tête tenu à jour côté client (delete/unfavorite
        // filtré) - voir promptCountLabel(), deletePrompt() et toggleFavorite().
        promptCount: {{ (int) $prompts->total() }},
        _promptCountZero: {{ Illuminate\Support\Js::from(__('Aucun prompt sauvegardé.')) }},
        _promptCountOne: {{ Illuminate\Support\Js::from(__('1 prompt sauvegardé.')) }},
        _promptCountMany: {{ Illuminate\Support\Js::from(__(':count prompts sauvegardés.')) }},

        promptCountLabel() {
            if (this.promptCount === 0) return this._promptCountZero;
            if (this.promptCount === 1) return this._promptCountOne;
            return this._promptCountMany.replace(':count', this.promptCount);
        },

        _headers() {
            return {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            };
        },

        _toast(message, variant) {
            window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: message, variant: variant, duration: 3500 } }));
        },

        // Round 57 (2026-07-27) : rechargait la MÊME URL (même ?page=N). Si l'action fait sortir
        // le dernier prompt d'une page de pagination (suppression, retrait de favori filtré, ou
        // round 59 : un tag retiré qui fait sortir le prompt du filtre ?tag=X actif), ?page=N
        // devient hors-limites - le serveur renvoie une collection vide pour cette page alors que
        // des prompts valides restent sur les pages précédentes, affichant un message trompeur
        // sans lien de retour. Retirer "page" avant de recharger ramène toujours sur une page
        // valide, en conservant les autres filtres actifs.
        _reloadWithoutPage() {
            var url = new URL(window.location.href);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        },

        // Round 52 (2026-07-27) : deletePrompt() et le retrait de favori sous le filtre
        // "Favoris seulement" ne font que card.remove() (jamais de reload, contrairement à
        // saveTags()/duplicatePrompt()) - si la carte retirée était la DERNIÈRE de la page
        // (dernière page de pagination, ou dernier favori sous ?favorite=1), la grille restait
        // un espace blanc sans le message "Aucun prompt" et sans que la pagination figée au
        // rendu initial soit corrigée. Recharger uniquement quand la grille devient vide laisse
        // le serveur re-rendre l'état vide/pagination correct, sans imposer un reload sur
        // chaque suppression/retrait (cas normal, non vide, reste instantané).
        _reloadIfListEmpty() {
            if (!document.querySelector('article[id^="prompt-card-"]')) {
                setTimeout(this._reloadWithoutPage.bind(this), 600);
            }
        },

        async saveProfile() {
            this.savingProfile = true;
            var value = {};
            if (this.profileRole.trim() !== '') value.profile_role = this.profileRole.trim();
            if (this.profileStyle.trim() !== '') value.profile_style = this.profileStyle.trim();
            if (this.profileConstraints.trim() !== '') value.profile_constraints = this.profileConstraints.trim();

            try {
                var res = await fetch('/api/tool-preferences/constructeur-prompts', {
                    method: 'POST',
                    headers: this._headers(),
                    body: JSON.stringify({ key: 'prompt_profile', value: value }),
                });
                if (res.ok) {
                    this._toast({{ Illuminate\Support\Js::from(__('Profil enregistré.')) }}, 'success');
                } else {
                    this._toast({{ Illuminate\Support\Js::from(__('Erreur lors de l\'enregistrement.')) }}, 'danger');
                }
            } catch (e) {
                this._toast({{ Illuminate\Support\Js::from(__('Erreur réseau.')) }}, 'danger');
            } finally {
                this.savingProfile = false;
            }
        },

        async toggleFavorite(publicId, buttonEl) {
            // Round 50 (2026-07-27) : lire l'état courant depuis le DOM (aria-pressed, tenu à jour
            // ci-dessous à chaque succès) au lieu d'un paramètre PHP figé au rendu serveur - sinon
            // chaque clic après le premier renvoyait toujours le même `next`, rendant le bouton
            // favori inversible une seule fois par chargement de page.
            var current = buttonEl.getAttribute('aria-pressed') === 'true';
            var next = !current;
            try {
                var res = await fetch('/api/prompts/' + publicId, {
                    method: 'PUT',
                    headers: this._headers(),
                    body: JSON.stringify({ is_favorite: next }),
                });
                if (res.ok) {
                    var icon = buttonEl.querySelector('svg');
                    if (icon) icon.setAttribute('fill', next ? 'currentColor' : 'none');
                    buttonEl.style.color = next ? '#D97706' : 'var(--c-text-muted, #52586A)';
                    buttonEl.setAttribute('aria-pressed', String(next));
                    buttonEl.setAttribute('aria-label', next ? {{ Illuminate\Support\Js::from(__('Retirer des favoris')) }} : {{ Illuminate\Support\Js::from(__('Ajouter aux favoris')) }});
                    this._toast(next ? {{ Illuminate\Support\Js::from(__('Ajouté aux favoris.')) }} : {{ Illuminate\Support\Js::from(__('Retiré des favoris.')) }}, 'success');
                    // Round 51 (2026-07-27) : sur la vue filtrée "Favoris seulement" (?favorite=1),
                    // retirer un favori doit faire disparaître la carte immédiatement - sinon elle
                    // reste visible dans une vue qui prétend n'afficher que les favoris, jusqu'à un
                    // rechargement manuel.
                    if (!next && new URLSearchParams(window.location.search).get('favorite') === '1') {
                        var favCard = document.getElementById('prompt-card-' + publicId);
                        if (favCard) favCard.remove();
                        this.promptCount = Math.max(0, this.promptCount - 1);
                        this._reloadIfListEmpty();
                    }
                } else {
                    this._toast({{ Illuminate\Support\Js::from(__('Erreur lors de la mise à jour.')) }}, 'danger');
                }
            } catch (e) {
                this._toast({{ Illuminate\Support\Js::from(__('Erreur réseau.')) }}, 'danger');
            }
        },

        // Round 120 (2026-07-30, passe adversariale) : le contrôle de longueur individuelle
        // manquait côté client, alors que le label du champ promet « max 5 tags de 30 caractères
        // max chacun ». Le serveur rejetait le LOT ENTIER en 422 dès qu'un seul tag dépassait 30
        // caractères : aucun tag n'était enregistré, pas même les tags valides du même lot. Et le
        // message serveur (« Le texte étiquette ne doit pas contenir plus de 30 caractères. ») ne
        // peut pas désigner LEQUEL est fautif, puisque le champ est un unique input à virgules.
        async saveTags(publicId, tagsInput) {
            // Doit rester aligné sur la règle serveur 'tags.*' => 'string|max:30'
            // (SavedPromptController::update) - défense en profondeur, pas un remplacement.
            var MAX_TAG_LENGTH = 30;
            var seen = {};
            var tags = tagsInput.split(',').map(function(t) { return t.trim(); }).filter(function(t) {
                if (t.length === 0) return false;
                var key = t.toLowerCase();
                if (seen[key]) return false;
                seen[key] = true;
                return true;
            }).slice(0, 5);

            var tagsTooLong = tags.filter(function(tag) {
                return tag.length > MAX_TAG_LENGTH;
            });

            if (tagsTooLong.length > 0) {
                var invalidTags = tagsTooLong.map(function(tag) {
                    var displayedTag = tag.length > 20 ? tag.slice(0, 20) + '…' : tag;
                    return '« ' + displayedTag + ' »';
                });
                var msgTooLong = {{ Illuminate\Support\Js::from(__('Ces étiquettes dépassent 30 caractères : :tags. Raccourcissez-les avant d\'enregistrer.')) }};
                this._toast(msgTooLong.replace(':tags', invalidTags.join(', ')), 'danger');
                return false;
            }

            try {
                var res = await fetch('/api/prompts/' + publicId, {
                    method: 'PUT',
                    headers: this._headers(),
                    body: JSON.stringify({ tags: tags }),
                });
                if (res.ok) {
                    this._toast({{ Illuminate\Support\Js::from(__('Tags mis à jour.')) }}, 'success');
                    // Round 59 (2026-07-27) : retrait de "page" avant reload (voir
                    // _reloadWithoutPage()) - retirer un tag qui fait sortir le prompt du filtre
                    // ?tag=X actif peut vider la dernière page de pagination pour ce filtre.
                    setTimeout(this._reloadWithoutPage.bind(this), 600);
                    return true;
                }
                // Round 96 (2026-07-27, passe adversariale) : lire le message de validation
                // précis (422, ex. "tag > 30 caractères") au lieu d'un message générique -
                // même pattern que addToHistory() (constructeur-prompts-core.js, rounds 35/82).
                var body = {};
                try { body = await res.json(); } catch (e) {}
                if (res.status === 422 && body.message) {
                    this._toast(body.message, 'danger');
                } else {
                    this._toast({{ Illuminate\Support\Js::from(__('Erreur lors de la mise à jour des tags.')) }}, 'danger');
                }
                return false;
            } catch (e) {
                this._toast({{ Illuminate\Support\Js::from(__('Erreur réseau.')) }}, 'danger');
                return false;
            }
        },

        // Round 122 (2026-07-30, passe adversariale) : l'item « Dupliquer » du menu ⋮ exécute
        // `open = false` avant l'appel, donc le bouton cliqué passe en display:none et le focus
        // clavier retombe sur <body>. Seuls les chemins d'ÉCHEC sont concernés : en cas de succès,
        // un rechargement complet de la page reprend la main et replace le focus au début de
        // façon attendue. Cible le bouton ⋮ de la carte (x-ref=trigger), qui lui reste visible.
        //
        // NB rédaction : ne PAS écrire ici le nom exact de l'appel de rechargement. Cette méthode
        // est définie entre saveTags() et duplicatePrompt(), or le test du round 59 découpe
        // justement cette tranche pour vérifier que saveTags() ne recharge pas la page - il
        // matche du texte brut, donc un simple commentaire le fait échouer (vécu au round 122).
        _restoreCardFocus(publicId) {
            if (document.activeElement !== document.body) return;

            var card = document.getElementById('prompt-card-' + publicId);
            if (!card) return;

            var trigger = card.querySelector('[x-ref=trigger]');
            if (trigger && typeof trigger.focus === 'function') {
                trigger.focus();
            }
        },

        async duplicatePrompt(publicId) {
            if (this._duplicatingIds.has(publicId)) return;
            this._duplicatingIds.add(publicId);
            try {
                var res = await fetch('/api/prompts/' + publicId + '/duplicate', {
                    method: 'POST',
                    headers: this._headers(),
                });
                if (res.status === 201) {
                    this._toast({{ Illuminate\Support\Js::from(__('Prompt dupliqué.')) }}, 'success');
                    setTimeout(function() { window.location.reload(); }, 700);
                } else {
                    this._toast({{ Illuminate\Support\Js::from(__('Erreur lors de la duplication.')) }}, 'danger');
                    this._duplicatingIds.delete(publicId);
                    this._restoreCardFocus(publicId);
                }
            } catch (e) {
                this._toast({{ Illuminate\Support\Js::from(__('Erreur réseau.')) }}, 'danger');
                this._duplicatingIds.delete(publicId);
                this._restoreCardFocus(publicId);
            }
        },

        async deletePrompt(publicId) {
            if (this._deletingIds.has(publicId)) return;
            this._deletingIds.add(publicId);
            try {
                var res = await fetch('/api/prompts/' + publicId, {
                    method: 'DELETE',
                    headers: this._headers(),
                });
                if (res.status === 204) {
                    var card = document.getElementById('prompt-card-' + publicId);
                    if (card) card.remove();
                    // Round 51 (2026-07-27) : le compteur d'en-tête doit refléter la suppression
                    // immédiatement (deletePrompt() ne recharge jamais la page, contrairement à
                    // saveTags()/duplicatePrompt() qui le font).
                    this.promptCount = Math.max(0, this.promptCount - 1);
                    this._toast({{ Illuminate\Support\Js::from(__('Prompt supprimé.')) }}, 'success');
                    this._reloadIfListEmpty();
                } else {
                    this._toast({{ Illuminate\Support\Js::from(__('Erreur lors de la suppression.')) }}, 'danger');
                    this._deletingIds.delete(publicId);
                }
            } catch (e) {
                this._toast({{ Illuminate\Support\Js::from(__('Erreur réseau.')) }}, 'danger');
                this._deletingIds.delete(publicId);
            }
        },
    };
}
</script>
@endsection
