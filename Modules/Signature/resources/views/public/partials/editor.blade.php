<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
{{-- Partagé par public.assistant (création) et public.manage (lien secret / membre) - DRY strict :
     un seul formulaire, alimenté par le même moteur de rendu (signature-render.js). --}}
{{-- LOT 2 : le registre PHP (SignatureTemplateRegistry) sérialisé pour que signature-render.js
     lise le MÊME agencement par gabarit que SignatureRenderer.php, plutôt que de le dupliquer -
     injecté ici (avant les scripts poussés en fin de page) pour être disponible dès le premier
     appel à renderSignature(). --}}
<script>window.SIGNATURE_TEMPLATES = @json($templateDefinitions ?? []);</script>

{{-- LOT 5 (2026-09-26) - assistant par ÉTAPES + aperçu collant. Réorganisation de l'éditeur
     (aucun champ ni aucune fonctionnalité perdue - tout est RÉPARTI en 5 étapes courtes), avec le
     MÊME patron de stepper que le constructeur de prompts (.ct-stepper, voir constructeur-prompts.
     blade.php) et un aperçu qui reste visible en tout temps : collant à droite sur bureau, bande
     réduite collante en haut sur mobile (agrandissable en feuille plein écran). --}}
<div class="sig-wizard">

    {{-- Bande d'aperçu collante MOBILE (<lg seulement, voir signature.css) - signature réduite mais
         lisible, toujours visible en haut. Se réduit à une simple poignée dès qu'un champ de
         saisie reçoit le focus (voir @focusin/@focusout sur #sig-wizard-steps plus bas), pour ne
         jamais recouvrir le champ actif, ses erreurs ni les boutons Précédent/Suivant (WCAG 2.4.11). --}}
    <div class="sig-mobile-preview-bar d-lg-none" :class="{ 'sig-mobile-preview-bar--collapsed': mobilePreviewCollapsed }">
        <div class="sig-mobile-preview-bar__inner">
            <button type="button" class="sig-mobile-preview-handle" @click="mobilePreviewCollapsed = false" aria-label="{{ __("Agrandir l'aperçu réduit") }}">
                👁️ {{ __('Aperçu') }} ▾
            </button>
            <span class="sig-mobile-preview-bar__viewport" aria-hidden="true">
                <iframe :srcdoc="previewSrcdoc" tabindex="-1" title="" sandbox=""></iframe>
            </span>
            <button type="button" class="btn btn-outline-primary btn-sm sig-mobile-preview-expand" @click="openPreviewSheet()">
                🔍 {{ __('Agrandir') }}
            </button>
        </div>
    </div>

    {{-- Stepper accessible (même patron que .ct-stepper du constructeur de prompts) : 3 états
         visuels (vide/en cours/complétée), role=tab/tablist, aria-selected, aria-current, focus
         programmatique sur le titre de l'étape après chaque changement (_focusStepHeading()). --}}
    <div class="ct-stepper mb-3" role="tablist" aria-label="{{ __('Étapes de la signature') }}">
        <template x-for="(s, sIdx) in [[1,'{{ __('Mise en page') }}'],[2,'{{ __('Vos informations') }}'],[3,'{{ __('Images') }}'],[4,'{{ __('Style et liens') }}'],[5,'{{ __('Finaliser') }}']]" :key="s[0]">
            <div class="ct-stepper__item" :class="{ 'ct-stepper__item--done': stepState(s[0]) === 'complete' }">
                <button type="button" class="ct-stepper__btn"
                        :id="'sig-step-tab-' + s[0]"
                        :class="{ 'ct-stepper__btn--on': step === s[0], 'ct-stepper__btn--done': stepState(s[0]) === 'complete', 'ct-stepper__btn--partial': stepState(s[0]) === 'partiel' }"
                        @click="goToStep(s[0])" role="tab"
                        :aria-selected="(step === s[0]).toString()"
                        :aria-current="step === s[0] ? 'step' : null"
                        :aria-controls="'sig-step-panel-' + s[0]"
                        :aria-label="s[1] + ' - ' + stepStateLabel(s[0])">
                    <span class="ct-stepper__circle" aria-hidden="true" x-text="stepState(s[0]) === 'complete' ? '✓' : s[0]"></span>
                    <span class="ct-stepper__label" x-text="s[1]"></span>
                </button>
            </div>
        </template>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div id="sig-wizard-steps" @focusin="onWizardFieldFocusIn($event)" @focusout="onWizardFieldFocusOut($event)">

                {{-- ÉTAPE 1 - Mise en page --}}
                <div x-show="step === 1" role="tabpanel" id="sig-step-panel-1" aria-labelledby="sig-step-tab-1">
                    <h2 id="sigStepHeading1" tabindex="-1" class="h5 fw-bold mb-3">1. {{ __('Mise en page') }}</h2>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <label class="form-label fw-bold mb-0" id="sig-template-label">{{ __('Gabarit') }}</label>
                            {{-- LOT 4 - ouvre la galerie de mises en page (modale, onglets par catégorie, vraies vignettes). --}}
                            <button type="button" class="btn btn-outline-primary btn-sm" @click="openGallery()">
                                🖼️ {{ __('Voir toutes les mises en page') }}
                            </button>
                        </div>
                        <div class="sig-template-cards mt-2" role="group" aria-labelledby="sig-template-label">
                            <template x-for="tpl in templates" :key="tpl">
                                <div class="sig-template-card" :class="{active: template === tpl}" @click="template = tpl"
                                     role="button" tabindex="0" :aria-pressed="template === tpl ? 'true' : 'false'"
                                     @keydown.enter.prevent="template = tpl" @keydown.space.prevent="template = tpl">
                                    <span x-text="templateLabel(tpl)"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- ÉTAPE 2 - Vos informations --}}
                <div x-show="step === 2" role="tabpanel" id="sig-step-panel-2" aria-labelledby="sig-step-tab-2">
                    <h2 id="sigStepHeading2" tabindex="-1" class="h5 fw-bold mb-3">2. {{ __('Vos informations') }}</h2>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label" for="sig-first-name">{{ __('Prénom') }} *</label>
                            <input id="sig-first-name" type="text" class="form-control" :class="{ 'is-invalid': showStepValidation && !content.first_name }" x-model="content.first_name" maxlength="80" required aria-required="true" :aria-invalid="(showStepValidation && !content.first_name).toString()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sig-last-name">{{ __('Nom') }} *</label>
                            <input id="sig-last-name" type="text" class="form-control" :class="{ 'is-invalid': showStepValidation && !content.last_name }" x-model="content.last_name" maxlength="80" required aria-required="true" :aria-invalid="(showStepValidation && !content.last_name).toString()">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label" for="sig-job-title">{{ __('Fonction') }}</label>
                            <input id="sig-job-title" type="text" class="form-control" x-model="content.job_title" maxlength="120">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sig-organization">{{ __('Organisation') }}</label>
                            <input id="sig-organization" type="text" class="form-control" x-model="content.organization" maxlength="120">
                        </div>
                    </div>
                    {{-- LOT 3 - pronoms (facultatif), affichés discrètement après le nom. --}}
                    <div class="mb-2">
                        <label class="form-label" for="sig-pronouns">{{ __('Pronoms (facultatif)') }}</label>
                        <input id="sig-pronouns" type="text" class="form-control" x-model="content.pronouns" maxlength="30" placeholder="{{ __('Ex. : elle, il, iel') }}">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label" for="sig-email">{{ __('Courriel') }} *</label>
                            <input id="sig-email" type="email" class="form-control" :class="{ 'is-invalid': showStepValidation && !content.email }" x-model="content.email" maxlength="190" required aria-required="true" :aria-invalid="(showStepValidation && !content.email).toString()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sig-phone">{{ __('Téléphone') }}</label>
                            <input id="sig-phone" type="text" class="form-control" x-model="content.phone" maxlength="40">
                        </div>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label class="form-label" for="sig-mobile">{{ __('Cellulaire') }}</label>
                            <input id="sig-mobile" type="text" class="form-control" x-model="content.mobile" maxlength="40">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sig-website">{{ __('Site web') }}</label>
                            <input id="sig-website" type="url" class="form-control" x-model="content.website" placeholder="https://" maxlength="255">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="sig-address">{{ __('Adresse') }}</label>
                        <input id="sig-address" type="text" class="form-control" x-model="content.address" maxlength="255">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="sig-tagline">{{ __('Slogan ou courte déclaration') }}</label>
                        <input id="sig-tagline" type="text" class="form-control" x-model="content.tagline" maxlength="160" placeholder="{{ __('Ex. : sur rendez-vous seulement') }}">
                    </div>
                    <div x-show="showStepValidation" class="text-danger small mt-2" role="alert">
                        ⚠️ {{ __('Le prénom, le nom et un courriel valide sont requis pour continuer.') }}
                    </div>
                </div>

                {{-- ÉTAPE 3 - Images --}}
                <div x-show="step === 3" role="tabpanel" id="sig-step-panel-3" aria-labelledby="sig-step-tab-3">
                    <h2 id="sigStepHeading3" tabindex="-1" class="h5 fw-bold mb-3">3. {{ __('Images') }}</h2>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="sig-logo-file">{{ __('Logo') }}</label>
                            <div class="sig-image-slot">
                                <template x-if="images.logo && images.logo.url">
                                    <img :src="images.logo.url" :width="images.logo.display_width" alt="" class="mb-2" style="max-width:100%;">
                                </template>
                                <input id="sig-logo-file" type="file" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm" @change="onFileSelected('logo', $event)" :disabled="uploading.logo">
                                <template x-if="images.logo">
                                    <div class="mt-2">
                                        <label class="form-label small" for="sig-logo-range">{{ __("Taille d'affichage") }} (<span x-text="images.logo.display_width"></span>px)</label>
                                        <input id="sig-logo-range" type="range" class="form-range" min="32" max="240" x-model.number="images.logo.display_width" @input="resizeImage('logo', images.logo.display_width)">
                                    </div>
                                </template>
                                <span class="spinner-border spinner-border-sm" x-show="uploading.logo" role="status">
                                    <span class="visually-hidden">{{ __('Téléversement en cours') }}</span>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold" for="sig-portrait-file">{{ __('Portrait') }}</label>
                            <div class="sig-image-slot">
                                <template x-if="images.portrait && images.portrait.url">
                                    <img :src="images.portrait.url" :width="images.portrait.display_width" alt="" class="mb-2 rounded" style="max-width:100%;">
                                </template>
                                <input id="sig-portrait-file" type="file" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm" @change="onFileSelected('portrait', $event)" :disabled="uploading.portrait">
                                <template x-if="images.portrait">
                                    <div class="mt-2">
                                        <label class="form-label small" for="sig-portrait-range">{{ __("Taille d'affichage") }} (<span x-text="images.portrait.display_width"></span>px)</label>
                                        <input id="sig-portrait-range" type="range" class="form-range" min="32" max="240" x-model.number="images.portrait.display_width" @input="resizeImage('portrait', images.portrait.display_width)">
                                    </div>
                                </template>
                                <span class="spinner-border spinner-border-sm" x-show="uploading.portrait" role="status">
                                    <span class="visually-hidden">{{ __('Téléversement en cours') }}</span>
                                </span>
                                {{-- LOT 3 - forme du portrait (n'affecte jamais le logo), rattachée directement au
                                     bloc du portrait puisqu'elle n'a aucun effet ailleurs. --}}
                                <div class="mt-2">
                                    <label class="form-label small" for="sig-portrait-shape">{{ __('Forme du portrait') }}</label>
                                    <select id="sig-portrait-shape" class="form-select form-select-sm" x-model="content.portrait_shape">
                                        <template x-for="shape in portraitShapes" :key="shape">
                                            <option :value="shape" x-text="portraitShapeLabel(shape)"></option>
                                        </template>
                                    </select>
                                    <p class="form-text small">{{ __("N'affecte que le portrait, jamais le logo.") }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Bannière (LOT 2, gabarit « Bannière ») - même mécanisme d'upload que logo/portrait
                         ci-dessus (mêmes gardes de sécurité : SignatureImagePipeline, quarantaine, public_id).
                         Toujours visible, comme logo/portrait, pour permettre de la préparer avant de choisir
                         ce gabarit. --}}
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label class="form-label fw-bold" for="sig-banniere-file">{{ __('Bannière') }}</label>
                            <div class="sig-image-slot">
                                <template x-if="images.banniere && images.banniere.url">
                                    <img :src="images.banniere.url" :width="images.banniere.display_width" alt="" class="mb-2" style="max-width:100%;">
                                </template>
                                <input id="sig-banniere-file" type="file" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm" @change="onFileSelected('banniere', $event)" :disabled="uploading.banniere">
                                <template x-if="images.banniere">
                                    <div class="mt-2">
                                        <label class="form-label small" for="sig-banniere-range">{{ __("Taille d'affichage") }} (<span x-text="images.banniere.display_width"></span>px)</label>
                                        <input id="sig-banniere-range" type="range" class="form-range" min="120" max="600" x-model.number="images.banniere.display_width" @input="resizeImage('banniere', images.banniere.display_width)">
                                    </div>
                                </template>
                                <span class="spinner-border spinner-border-sm" x-show="uploading.banniere" role="status">
                                    <span class="visually-hidden">{{ __('Téléversement en cours') }}</span>
                                </span>
                                <p class="form-text small">{{ __('Image pleine largeur affichée sous tes coordonnées - utilisée uniquement par le gabarit «'."\u{00A0}".'Bannière'."\u{00A0}".'», cliquable vers le lien de l\'appel à l\'action ci-dessus.') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- LOT 3 - sécurité mode sombre (angle mort nommé par le club des sages) : le moteur pose
                         déjà des couleurs de texte explicites et un fond blanc sur l'enveloppe, mais un logo
                         texte-sur-fond-opaque reste un piège que seul le choix de l'image peut éviter. --}}
                    <p class="form-text small text-muted">
                        🌓 {{ __('Certains clients de courriel inversent les couleurs en mode sombre' . "\u{00A0}" . ': privilégie un logo à fond neutre ou transparent sûr plutôt qu\'un texte noir sur un fond blanc opaque, qui deviendrait illisible si le fond bascule en sombre.') }}
                    </p>
                </div>

                {{-- ÉTAPE 4 - Style et liens --}}
                <div x-show="step === 4" role="tabpanel" id="sig-step-panel-4" aria-labelledby="sig-step-tab-4">
                    <h2 id="sigStepHeading4" tabindex="-1" class="h5 fw-bold mb-3">4. {{ __('Style et liens') }}</h2>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="sig-accent-color">{{ __("Couleur d'accent") }}</label>
                            <input id="sig-accent-color" type="color" class="form-control form-control-color" x-model="content.accent_color">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sig-font-family">{{ __('Police (client courriel)') }}</label>
                            <select id="sig-font-family" class="form-select" x-model="content.font_family">
                                <template x-for="font in fontFamilies" :key="font">
                                    <option :value="font" x-text="font"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="sig-font-scale">{{ __('Taille du texte') }}</label>
                        <select id="sig-font-scale" class="form-select" x-model="content.font_scale">
                            <template x-for="scale in fontScales" :key="scale">
                                <option :value="scale" x-text="fontScaleLabel(scale)"></option>
                            </template>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="sig-cta-text">{{ __("Texte de l'appel à l'action") }}</label>
                            <input id="sig-cta-text" type="text" class="form-control" x-model="content.cta_text" maxlength="60" placeholder="{{ __('Ex. : Réserver un appel') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="sig-cta-url">{{ __("Lien de l'appel à l'action") }}</label>
                            <input id="sig-cta-url" type="url" class="form-control" x-model="content.cta_url" placeholder="https://" maxlength="255">
                            @if(\Illuminate\Support\Facades\Route::has('shorturl.create'))
                            <p class="form-text small mb-0">
                                {{ __('Besoin d\'un lien plus court?') }}
                                <a href="{{ route('shorturl.create') }}" target="_blank" rel="noopener">{{ __('Utilisez le raccourcisseur de liens de laveille.ai') }}</a>.
                            </p>
                            @endif
                        </div>
                    </div>

                    {{-- Répéteur : liens sociaux (patron Alpine `spaces` de constructeur-prompts-core.js) --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold" id="sig-social-links-label">{{ __('Liens sociaux') }}</label>
                        <template x-for="(link, index) in content.social_links" :key="index">
                            <div class="sig-repeater-row">
                                <label :for="'sig-social-platform-' + index" class="visually-hidden" x-text="'{{ __('Réseau, lien') }} ' + (index + 1)"></label>
                                <select :id="'sig-social-platform-' + index" class="form-select" x-model="link.platform" aria-labelledby="sig-social-links-label">
                                    <template x-for="p in socialPlatforms" :key="p">
                                        <option :value="p" x-text="p"></option>
                                    </template>
                                </select>
                                <label :for="'sig-social-url-' + index" class="visually-hidden" x-text="'{{ __('Adresse du lien') }} ' + (index + 1)"></label>
                                <input :id="'sig-social-url-' + index" type="url" class="form-control" x-model="link.url" placeholder="https://" maxlength="255">
                                <button type="button" class="btn btn-outline-danger btn-sm" @click="removeSocialLink(index)" aria-label="{{ __('Retirer ce lien') }}">✕</button>
                            </div>
                        </template>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="addSocialLink()" x-show="content.social_links.length < 6">+ {{ __('Ajouter un lien social') }}</button>
                    </div>

                    {{-- Répéteur : lignes de mention légale/secondaire --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold" id="sig-mention-lines-label">{{ __('Mentions (titre réglementé, numéro de permis...)') }}</label>
                        <template x-for="(line, index) in content.mention_lines" :key="index">
                            <div class="sig-repeater-row">
                                <label :for="'sig-mention-line-' + index" class="visually-hidden" x-text="'{{ __('Ligne de mention') }} ' + (index + 1)"></label>
                                <input :id="'sig-mention-line-' + index" type="text" class="form-control" x-model="content.mention_lines[index]" maxlength="160" aria-labelledby="sig-mention-lines-label">
                                <button type="button" class="btn btn-outline-danger btn-sm" @click="removeMentionLine(index)" aria-label="{{ __('Retirer cette ligne') }}">✕</button>
                            </div>
                        </template>
                        <button type="button" class="btn btn-outline-secondary btn-sm" @click="addMentionLine()" x-show="content.mention_lines.length < 6">+ {{ __('Ajouter une ligne') }}</button>
                        <p class="form-text small" x-show="content.mention_lines.length > 2">⚠️ {{ __('Une signature trop longue nuit à la lisibilité.') }}</p>
                    </div>
                </div>

                {{-- ÉTAPE 5 - Finaliser --}}
                <div x-show="step === 5" role="tabpanel" id="sig-step-panel-5" aria-labelledby="sig-step-tab-5">
                    <h2 id="sigStepHeading5" tabindex="-1" class="h5 fw-bold mb-3">5. {{ __('Finaliser') }}</h2>

                    {{-- M1.4 - limite mesurée du signal de chargement d'image : beaucoup de clients de bureau
                         (Outlook classique en tête) et Apple Mail bloquent par défaut le chargement des images
                         distantes, ou les mettent en cache localement sans jamais rappeler le serveur. Sur ces
                         clients, l'absence de chargement ne veut PAS dire que le courriel n'est plus consulté -
                         le dire clairement évite de faire croire que ce signal est un indicateur fiable
                         d'usage réel, quel que soit le client courriel. --}}
                    <p class="form-text small text-muted">
                        ℹ️ {{ __('Le chargement d\'image ne fonctionne pas de la même façon partout' . "\u{00A0}" . ': Outlook de bureau et Apple Mail bloquent souvent les images distantes par défaut ou les mettent en cache localement, donc ce signal ne mesure fiablement ni l\'un ni l\'autre. Il retarde une suppression quand il fonctionne - son absence ne prouve rien.') }}
                    </p>

                    @if($showReminderOptIn ?? false)
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="sigReminderOptIn" x-model="reminderOptIn">
                        <label class="form-check-label" for="sigReminderOptIn">{{ __('Recevoir un courriel de rappel avant la suppression (facultatif)') }}</label>
                        <template x-if="reminderOptIn">
                            <div>
                                <label class="visually-hidden" for="sig-reminder-email">{{ __('Courriel de rappel') }}</label>
                                <input id="sig-reminder-email" type="email" class="form-control mt-2" x-model="reminderEmail" placeholder="{{ __('ton.courriel@exemple.com') }}" maxlength="190">
                            </div>
                        </template>
                        <p class="form-text small">{{ __('Le chargement du logo/portrait dans un courriel déjà envoyé peut aussi retarder la suppression, jamais la provoquer. Aucune de ces informations ne sert à savoir si tu as lu un courriel.') }}</p>
                    </div>
                    @endif

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-primary" @click="copyToClipboard()">📋 {{ __('Copier') }}</button>
                        <button type="button" class="btn btn-outline-secondary" @click="downloadHtm()">⬇️ {{ __('Télécharger (.htm)') }}</button>
                        {{-- LOT 3 - export brut, pour un client courriel qui exige de coller le code source
                             (ex. champ « Signature HTML » de Gmail/Outlook web en mode « éditeur HTML »). --}}
                        <button type="button" class="btn btn-outline-secondary" @click="openHtmlCode()">👀 {{ __('Voir le code HTML') }}</button>
                        <button type="button" class="btn btn-outline-primary" @click="save()" :disabled="saving">
                            <span x-show="!saving">💾 {{ __('Enregistrer') }}</span>
                            <span x-show="saving">{{ __('Enregistrement...') }}</span>
                        </button>
                    </div>

                    <div class="mt-3 small">
                        <a href="{{ route('signature.guide.gmail') }}">{{ __('Guide Gmail') }}</a>
                        ·
                        <a href="{{ route('signature.guide.outlook-web') }}">{{ __('Guide Outlook web') }}</a>
                    </div>
                </div>

                {{-- Navigation Précédent/Suivant, commune aux 5 étapes. --}}
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <button type="button" class="btn btn-outline-secondary" @click="prevStep()" x-show="step > 1">← {{ __('Précédent') }}</button>
                    <span x-show="step === 1"></span>
                    <button type="button" class="btn btn-primary" @click="nextStep()" x-show="step < 5">{{ __('Suivant') }} →</button>
                </div>
            </div>
        </div>

        {{-- Aperçu BUREAU collant (≥lg seulement - sur mobile, la bande + la feuille ci-dessus le
             remplacent). `position: sticky` (signature.css) - reste visible pendant le défilement
             du formulaire, sous l'en-tête du site (qui n'est pas fixe ici, un petit décalage
             suffit). --}}
        <div class="col-lg-5 d-none d-lg-block">
            <div class="sig-preview-sticky">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <label class="form-label fw-bold mb-0" id="sig-preview-label">{{ __('Aperçu en direct') }}</label>
                    {{-- LOT 3 - bascule bureau/mobile : contraint la largeur de l'aperçu pour vérifier le
                         rendu mobile, sans dupliquer l'iframe. --}}
                    <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Largeur de l\'aperçu') }}">
                        <button type="button" class="btn" :class="previewMode === 'desktop' ? 'btn-primary' : 'btn-outline-secondary'" @click="previewMode = 'desktop'" :aria-pressed="previewMode === 'desktop' ? 'true' : 'false'">🖥️ {{ __('Bureau') }}</button>
                        <button type="button" class="btn" :class="previewMode === 'mobile' ? 'btn-primary' : 'btn-outline-secondary'" @click="previewMode = 'mobile'" :aria-pressed="previewMode === 'mobile' ? 'true' : 'false'">📱 {{ __('Mobile') }}</button>
                    </div>
                </div>
                {{-- Cadre courriel sobre : fond blanc, quelques lignes grises pour évoquer un message
                     réel au-dessus de la signature, et une note de simulation - décision arbitrée
                     dans storage/app/signature-wizard-spec.md. --}}
                <div class="sig-email-frame">
                    <div class="sig-email-frame__meta" aria-hidden="true">
                        <span class="sig-email-frame__line sig-email-frame__line--w60"></span>
                        <span class="sig-email-frame__line sig-email-frame__line--w90"></span>
                        <span class="sig-email-frame__line sig-email-frame__line--w40"></span>
                    </div>
                    <div class="sig-preview-wrap" :class="{ 'sig-preview-wrap--mobile': previewMode === 'mobile' }">
                        <iframe :srcdoc="previewSrcdoc" title="{{ __('Aperçu de la signature') }}" aria-labelledby="sig-preview-label" sandbox=""></iframe>
                    </div>
                    <p class="sig-email-frame__note form-text small text-muted mb-0">{{ __('Simulation - le rendu peut varier selon le client de courriel.') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Feuille plein écran MOBILE (bouton « Agrandir » de la bande collante) - aperçu à taille réelle,
     défilable, réutilise le MÊME previewSrcdoc (aucune logique de rendu dupliquée). Modale MAISON
     (Alpine pur, jamais alert()/confirm()/prompt() natif), focus piégé via focusFirstIn()/
     trapFocusTab() déjà en place plus bas, focus rendu au bouton « Agrandir » à la fermeture. --}}
<div x-show="mobilePreviewSheetOpen" x-cloak class="sig-preview-sheet-overlay d-lg-none"
     role="dialog" aria-modal="true" aria-labelledby="sigPreviewSheetTitle"
     x-init="$watch('mobilePreviewSheetOpen', (v) => v && $nextTick(() => focusFirstIn($el)))"
     @keydown.escape.window="closePreviewSheet()"
     @keydown.tab="trapFocusTab($event)">
    <div class="sig-preview-sheet" @click.outside="closePreviewSheet()">
        <div class="sig-preview-sheet__header">
            <h2 id="sigPreviewSheetTitle" class="h6 mb-0">{{ __('Aperçu de la signature') }}</h2>
            <button type="button" class="btn-close" @click="closePreviewSheet()" aria-label="{{ __('Fermer') }}"></button>
        </div>
        <div class="sig-preview-sheet__body">
            <div class="sig-email-frame">
                <div class="sig-email-frame__meta" aria-hidden="true">
                    <span class="sig-email-frame__line sig-email-frame__line--w60"></span>
                    <span class="sig-email-frame__line sig-email-frame__line--w90"></span>
                    <span class="sig-email-frame__line sig-email-frame__line--w40"></span>
                </div>
                <div class="sig-preview-wrap">
                    <iframe :srcdoc="previewSrcdoc" title="{{ __('Aperçu de la signature') }}" sandbox=""></iframe>
                </div>
                <p class="sig-email-frame__note form-text small text-muted mb-0">{{ __('Simulation - le rendu peut varier selon le client de courriel.') }}</p>
            </div>
        </div>
    </div>
</div>

{{-- LOT 4 - modale MAISON « Galerie de mises en page » (même patron que les autres modales de cette
     page : Alpine pur, jamais alert()/confirm()/prompt() natif, focus piégé via
     focusFirstIn()/trapFocusTab() de signature-core.js). Barre d'onglets ARIA (tablist/tab/tabpanel)
     par catégorie du registre + grille de vignettes, chacune un VRAI mini-aperçu (même moteur
     renderSignature() que l'aperçu principal, jeu de données d'exemple générique - jamais un schéma
     abstrait). --}}
<div x-show="showGalleryModal" x-cloak
     style="position:fixed;inset:0;z-index:1055;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5);padding:16px;"
     role="dialog" aria-modal="true" aria-labelledby="sigGalleryModalTitle"
     x-init="$watch('showGalleryModal', (v) => v && $nextTick(() => focusFirstIn($el)))"
     @keydown.escape.window="closeGallery()"
     @keydown.tab="trapFocusTab($event)">
    <div class="sig-gallery-modal" @click.outside="closeGallery()">
        <div class="d-flex justify-content-between align-items-start p-3 pb-2">
            <div>
                <h5 id="sigGalleryModalTitle" class="mb-0">{{ __('Toutes les mises en page') }}</h5>
                <p class="form-text small mb-0">{{ __('Choisis une vignette puis confirme - l\'aperçu principal se met à jour immédiatement.') }}</p>
            </div>
            <button type="button" class="btn-close" @click="closeGallery()" aria-label="{{ __('Fermer') }}"></button>
        </div>

        <div class="px-3">
            <div class="sig-gallery-tabs" role="tablist" aria-label="{{ __('Catégories de mises en page') }}"
                 @keydown.right.prevent="galleryMoveTab(1)" @keydown.left.prevent="galleryMoveTab(-1)"
                 @keydown.home.prevent="galleryMoveTab('first')" @keydown.end.prevent="galleryMoveTab('last')">
                <template x-for="cat in galleryCategoriesWithAll()" :key="cat">
                    <button type="button" class="sig-gallery-tab" role="tab"
                            :id="'sig-gallery-tab-' + cat"
                            :aria-selected="galleryCategory === cat ? 'true' : 'false'"
                            aria-controls="sig-gallery-panel"
                            :tabindex="galleryCategory === cat ? 0 : -1"
                            :class="{ active: galleryCategory === cat }"
                            @click="galleryCategory = cat"
                            x-text="galleryCategoryLabel(cat)"></button>
                </template>
            </div>
        </div>

        <div id="sig-gallery-panel" role="tabpanel" class="sig-gallery-scroll p-3"
             :aria-labelledby="'sig-gallery-tab-' + galleryCategory" tabindex="0">
            <div class="sig-gallery-grid">
                <template x-for="tpl in galleryTemplatesFor(galleryCategory)" :key="tpl">
                    <button type="button" class="sig-gallery-card"
                            :class="{ selected: gallerySelected === tpl }"
                            @click="selectGalleryTemplate(tpl)"
                            :aria-pressed="gallerySelected === tpl ? 'true' : 'false'"
                            :aria-current="template === tpl ? 'true' : 'false'">
                        <span class="sig-gallery-thumb-viewport">
                            <iframe :srcdoc="galleryThumbSrcdoc(tpl)" tabindex="-1" aria-hidden="true" sandbox=""></iframe>
                        </span>
                        <span class="sig-gallery-card-current" x-show="template === tpl">✓ {{ __('Actuel') }}</span>
                        <span class="sig-gallery-card-label" x-text="templateLabel(tpl)"></span>
                        <span class="sig-gallery-card-hint" x-text="templateHint(tpl)"></span>
                    </button>
                </template>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 p-3 pt-2 border-top">
            <button type="button" class="btn btn-secondary" @click="closeGallery()">{{ __('Annuler') }}</button>
            <button type="button" class="btn btn-primary" @click="applyGalleryTemplate()" :disabled="!gallerySelected">
                {{ __('Utiliser cette mise en page') }}
            </button>
        </div>
    </div>
</div>

{{-- LOT 3 - modale MAISON « Voir le code HTML » (même patron que la modale du jeton ci-dessous :
     Alpine pur, jamais alert()/confirm()/prompt() natif). Le code montré est le rendu FINAL du
     moteur canonique (renderSignature), copiable en un clic - utile pour un client courriel qui
     exige de coller le code source plutôt qu'une copie riche. --}}
<div x-show="showHtmlModal" x-cloak
     style="position:fixed;inset:0;z-index:1055;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5);"
     role="dialog" aria-modal="true" aria-labelledby="sigHtmlModalTitle"
     x-init="$watch('showHtmlModal', (v) => v && $nextTick(() => focusFirstIn($el)))"
     @keydown.escape.window="showHtmlModal = false"
     @keydown.tab="trapFocusTab($event)">
    <div style="background:#fff;border-radius:var(--r-base,0.75rem);max-width:640px;width:94%;padding:1.5rem;box-shadow:0 1rem 3rem rgba(0,0,0,.2);" @click.outside="showHtmlModal = false">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 id="sigHtmlModalTitle" class="mb-0">{{ __('Code HTML de la signature') }}</h5>
            <button type="button" class="btn-close" @click="showHtmlModal = false" aria-label="{{ __('Fermer') }}"></button>
        </div>
        <p class="form-text small">{{ __('Colle ce code dans le champ « Signature HTML » de ton client courriel (voir nos guides ci-dessous), ou utilise plutôt le bouton «'."\u{00A0}".'Copier'."\u{00A0}".'» pour une copie déjà mise en forme.') }}</p>
        <label class="visually-hidden" for="sig-html-source">{{ __('Code HTML de la signature') }}</label>
        <textarea id="sig-html-source" class="form-control sig-html-source" rows="10" readonly x-text="htmlSourceCode" @click="$event.target.select()"></textarea>
        <div class="text-end mt-3 d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-secondary" @click="showHtmlModal = false">{{ __('Fermer') }}</button>
            <button type="button" class="btn btn-outline-primary" @click="copyHtmlSource()">📋 {{ __('Copier le code') }}</button>
        </div>
    </div>
</div>

{{-- Modale MAISON (Alpine pur, sans dépendre du CSS .modal/.fade/.show de Bootstrap - une
     tentative de réutiliser ces classes avec x-show seul s'est révélée invisible en pratique :
     Bootstrap attend une instance JS bootstrap.Modal() pour poser display:block, qu'Alpine ne
     fournit pas) - jamais alert()/confirm() natif (règle projet). Focus piégé (M4.5) via
     focusFirstIn()/trapFocusTab() (signature-core.js), aucun plugin Alpine Focus dans ce projet. --}}
<div x-show="showTokenModal" x-cloak
     style="position:fixed;inset:0;z-index:1055;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5);"
     role="dialog" aria-modal="true" aria-labelledby="sigTokenModalTitle"
     x-init="$watch('showTokenModal', (v) => v && $nextTick(() => focusFirstIn($el)))"
     @keydown.escape.window="showTokenModal = false"
     @keydown.tab="trapFocusTab($event)">
    <div style="background:#fff;border-radius:var(--r-base,0.75rem);max-width:480px;width:92%;padding:1.5rem;box-shadow:0 1rem 3rem rgba(0,0,0,.2);" @click.outside="showTokenModal = false">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h5 id="sigTokenModalTitle" class="mb-0">{{ __('Conserve ce lien') }}</h5>
            <button type="button" class="btn-close" @click="showTokenModal = false" aria-label="{{ __('Fermer') }}"></button>
        </div>
        <p>{{ __('Ce lien te permet de modifier ta signature plus tard. Nous ne pouvons pas te le renvoyer si tu le perds.') }}</p>
        <code class="sig-secret-link" x-text="issuedManageUrl"></code>
        <div class="text-end mt-3">
            <button type="button" class="btn btn-secondary" @click="showTokenModal = false">{{ __('Fermer') }}</button>
        </div>
    </div>
</div>
