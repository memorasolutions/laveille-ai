/*
 * Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
 *
 * Composant Alpine.js de l'éditeur de signature. Mécanique du formulaire UNIQUEMENT (répéteurs,
 * validation légère, appels réseau pour la sauvegarde/les images) - jamais le rendu HTML final de
 * la signature, produit EXCLUSIVEMENT par window.renderSignature() (signature-render.js, section 4
 * du plan : une seule fonction alimente l'aperçu, la copie et le téléchargement).
 */
// LOT 3 - jumeau exact de SignatureContentValidator::PORTRAIT_SHAPES/FONT_SCALES (libellés FR
// accentués - les clés techniques restent ASCII, valeur stockée en base).
var SIG_PORTRAIT_SHAPE_LABELS = { carre: 'Carré', rond: 'Rond' };
var SIG_FONT_SCALE_LABELS = { petite: 'Petite', moyenne: 'Moyenne', grande: 'Grande' };

// LOT 4 - libellés FR accentués des catégories de la galerie (clés = valeur `category` du registre,
// voir SignatureTemplateRegistry). "toutes" est l'onglet ajouté par la galerie elle-même, absent du
// registre.
var SIG_GALLERY_CATEGORY_LABELS = {
    toutes: 'Toutes', classiques: 'Classiques', photo: 'Photo', vertical: 'Vertical',
    banniere: 'Bannière', reseaux: 'Réseaux sociaux'
};

function signatureAssistant(config) {
    return {
        template: config.initialTemplate || 'minimal',
        content: Object.assign({
            first_name: '', last_name: '', job_title: '', organization: '', email: '', phone: '',
            mobile: '', website: '', address: '', tagline: '', cta_text: '', cta_url: '',
            accent_color: '#064E5A', font_family: 'Arial', social_links: [], mention_lines: [],
            // LOT 3 (2026-09-25) - défauts neutres, aucun impact sur le rendu tant qu'ils ne sont
            // pas changés (voir SignatureRenderer::normalize()).
            pronouns: '', portrait_shape: 'carre', font_scale: 'moyenne',
            // Mention laveille.ai en commentaire HTML - ACTIVÉE par défaut, la case peut la retirer.
            show_attribution: true
        }, config.initialContent || {}),
        images: Object.assign({ logo: null, portrait: null, banniere: null }, config.initialImages || {}),
        templates: config.templates || [
            'minimal', 'professionnel', 'portrait', 'compact', 'vertical', 'banniere', 'executive', 'social',
            // LOT 4 - repli défensif seulement (les 3 contrôleurs qui servent l'éditeur passent
            // toujours `templates` depuis Signature::templates() - jamais rencontré en usage normal).
            'photo_droite', 'logo_gauche', 'logo_bas', 'photo_centree', 'deux_colonnes', 'coordonnees_sous_nom'
        ],
        fontFamilies: config.fontFamilies || ['Arial', 'Helvetica', 'Verdana', 'Georgia', 'Tahoma'],
        socialPlatforms: config.socialPlatforms || ['linkedin', 'facebook', 'instagram', 'x', 'youtube', 'website'],
        // LOT 3 - options des nouveaux champs de contenu (voir SignatureContentValidator).
        portraitShapes: config.portraitShapes || ['carre', 'rond'],
        fontScales: config.fontScales || ['petite', 'moyenne', 'grande'],
        token: config.token || null,
        signatureId: config.signatureId || null,
        draftStoreUrl: config.draftStoreUrl,
        imagesUploadUrl: config.imagesUploadUrl,
        manageUrlBase: config.manageUrlBase, // "/outils/signature-courriel/gerer" - le jeton est concaténé au besoin
        updateUrl: config.updateUrl || null, // déjà connu sur la page de gestion (PATCH /gerer/{token} ou /mes-signatures/{id})
        extendUrl: config.extendUrl || null,
        rotateUrl: config.rotateUrl || null,
        attachUrl: config.attachUrl || null,
        reminderOptIn: false,
        reminderEmail: '',
        saving: false,
        uploading: { logo: false, portrait: false, banniere: false },
        previewSrcdoc: '',
        // LOT 3 - bascule d'aperçu bureau/mobile (largeur contrainte, voir signature.css).
        previewMode: 'desktop',
        // LOT 5 (2026-09-26) - assistant par étapes : 1 Mise en page, 2 Vos informations,
        // 3 Images, 4 Style et liens, 5 Finaliser. `step` est la SEULE source de vérité de
        // l'étape affichée (x-show="step === N" dans editor.blade.php) - jamais un doublon d'état.
        step: 1,
        showStepValidation: false,
        // Bande d'aperçu collante mobile (<lg) : réduite à une poignée dès qu'un champ de saisie
        // reçoit le focus (clavier virtuel) - voir onWizardFieldFocusIn/Out plus bas - pour ne
        // jamais recouvrir le champ actif, ses erreurs ni les boutons Précédent/Suivant.
        mobilePreviewCollapsed: false,
        // Feuille plein écran (bouton « Agrandir » de la bande mobile) - aperçu à taille réelle.
        mobilePreviewSheetOpen: false,
        _previewSheetOpener: null,
        showTokenModal: false,
        issuedManageUrl: '',
        confirmRotate: false,
        linkActionBusy: false,
        // LOT 3 - modale « Voir le code HTML » (export brut, copie en un clic).
        showHtmlModal: false,
        htmlSourceCode: '',
        // LOT 4 - modale « Galerie de mises en page » (onglets par catégorie, vraies vignettes).
        showGalleryModal: false,
        galleryCategory: 'toutes',
        gallerySelected: null,
        _galleryOpener: null,

        init() {
            this.updatePreview();
            this.$watch('template', () => this.updatePreview());
            this.$watch('content', () => this.updatePreview(), { deep: true });
            this.$watch('images', () => this.updatePreview(), { deep: true });
        },

        csrfToken() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.content : '';
        },

        // ------------------------------------------------------------------
        // Assistant par étapes (LOT 5, 2026-09-26) - navigation + validation minimale. Seule
        // l'étape 2 (Vos informations) bloque la progression : une signature minimale exige
        // prénom, nom et un courriel valide - EXACTEMENT les 3 champs `required` de
        // SignatureContentValidator côté serveur (content.first_name/last_name/email), jamais un
        // champ de plus. Tout le reste (étapes 1, 3, 4, 5) est optionnel et ne bloque jamais.
        // ------------------------------------------------------------------
        hasMinimalIdentity() {
            var email = (this.content.email || '').trim();
            return !!(this.content.first_name && this.content.first_name.trim())
                && !!(this.content.last_name && this.content.last_name.trim())
                && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },
        canGoToStep(s) {
            if (s <= 2) { return true; }
            return this.hasMinimalIdentity();
        },
        goToStep(s) {
            if (this.canGoToStep(s)) {
                this.showStepValidation = false;
                this.step = s;
                this._focusStepHeading();
            } else {
                // Renvoie à l'étape 2 (celle qui porte les champs manquants) plutôt que de
                // bloquer silencieusement sur place - la personne voit tout de suite ce qui manque.
                this.showStepValidation = true;
                this.step = 2;
                this._focusStepHeading();
            }
        },
        nextStep() {
            this.goToStep(Math.min(this.step + 1, 5));
        },
        prevStep() {
            if (this.step > 1) {
                this.step--;
                this._focusStepHeading();
            }
        },
        _focusStepHeading() {
            var self = this;
            this.$nextTick(function () {
                var el = document.getElementById('sigStepHeading' + self.step);
                if (el && typeof el.focus === 'function') { el.focus(); }
            });
        },
        // Trois états visuels du stepper (jamais un simple booléen) - lisibles SANS la couleur
        // (WCAG 1.4.1) : le glyphe change (chiffre/✓) ET l'aria-label du bouton porte toujours le
        // nom de l'étape + son état. Seule l'étape 2 distingue "partiel" (un champ rempli sur les
        // trois) - les autres étapes sont entièrement optionnelles : "complétée" y signale
        // seulement qu'un choix a été personnalisé, jamais une obligation à remplir.
        stepState(n) {
            if (n === 1) { return 'complete'; } // un gabarit est toujours choisi (valeur par défaut)
            if (n === 2) {
                if (this.hasMinimalIdentity()) { return 'complete'; }
                if (this.content.first_name || this.content.last_name || this.content.email) { return 'partiel'; }
                return 'vide';
            }
            if (n === 3) {
                return (this.images.logo || this.images.portrait || this.images.banniere) ? 'complete' : 'vide';
            }
            if (n === 4) {
                var touched = this.content.social_links.length > 0
                    || this.content.mention_lines.length > 0
                    || !!this.content.cta_text
                    || !!this.content.cta_url
                    || this.content.accent_color !== '#064E5A'
                    || this.content.font_family !== 'Arial'
                    || this.content.font_scale !== 'moyenne';
                return touched ? 'complete' : 'vide';
            }
            return 'vide'; // étape 5 (Finaliser) : pas de notion de complétion, seulement des actions
        },
        stepStateLabel(n) {
            var state = this.stepState(n);
            if (state === 'complete') { return 'complétée'; }
            if (state === 'partiel') { return 'en cours'; }
            return 'à faire';
        },

        // ------------------------------------------------------------------
        // Aperçu mobile collant (LOT 5) - bande réduite en haut + feuille plein écran. Réutilise
        // previewSrcdoc (même moteur que l'aperçu bureau, aucune logique de rendu dupliquée).
        // ------------------------------------------------------------------
        // Dès qu'un vrai champ de saisie reçoit le focus (clavier virtuel), la bande se réduit à
        // une poignée. `focusin`/`focusout` sont délégués depuis le conteneur des étapes
        // (#sig-wizard-steps, editor.blade.php) - aucun champ individuel à instrumenter un par un.
        onWizardFieldFocusIn(event) {
            if (event.target && event.target.matches && event.target.matches('input,select,textarea')) {
                this.mobilePreviewCollapsed = true;
            }
        },
        onWizardFieldFocusOut(event) {
            // `relatedTarget` porte le prochain élément focusé (supporté par les navigateurs
            // courants sur focusout) : s'il s'agit encore d'un champ, on reste réduit - on
            // ré-agrandit seulement quand le focus quitte réellement la zone de saisie.
            var next = event.relatedTarget;
            var stillInField = !!(next && next.matches && next.matches('input,select,textarea'));
            if (!stillInField) { this.mobilePreviewCollapsed = false; }
        },
        openPreviewSheet() {
            this._previewSheetOpener = document.activeElement;
            this.mobilePreviewSheetOpen = true;
        },
        closePreviewSheet() {
            this.mobilePreviewSheetOpen = false;
            if (this._previewSheetOpener && typeof this._previewSheetOpener.focus === 'function') {
                this._previewSheetOpener.focus();
            }
        },

        // LOT 2 - libellé d'affichage d'un gabarit : lu dans window.SIGNATURE_TEMPLATES (registre
        // PHP sérialisé, voir signature-render.js) plutôt que capitalisé depuis la clé technique -
        // certaines clés (ex. "banniere") perdraient leur accent, d'autres ("executive") ne sont
        // pas des mots français. Repli sur la capitalisation UNIQUEMENT si le registre est absent.
        templateLabel(tpl) {
            var def = (window.SIGNATURE_TEMPLATES || {})[tpl];
            if (def && def.label) { return def.label; }
            return tpl.charAt(0).toUpperCase() + tpl.slice(1);
        },

        // LOT 3 - libellés FR accentués des nouveaux champs (mêmes clés ASCII que le validateur).
        portraitShapeLabel(value) {
            return SIG_PORTRAIT_SHAPE_LABELS[value] || value;
        },
        fontScaleLabel(value) {
            return SIG_FONT_SCALE_LABELS[value] || value;
        },

        // LOT 4 - « quand l'utiliser » d'un gabarit, lu dans window.SIGNATURE_TEMPLATES (même patron
        // que templateLabel() ci-dessus) - affiché sous chaque vignette de la galerie.
        templateHint(tpl) {
            var def = (window.SIGNATURE_TEMPLATES || {})[tpl];
            return (def && def.hint) || '';
        },

        // LOT 6 (2026-09-26) - schéma de disposition (wireframe abstrait) d'un gabarit : SOURCE
        // UNIQUE (window.SIGNATURE_TEMPLATE_WIREFRAMES, calculée une fois côté serveur par
        // SignatureWireframeRenderer à partir du registre - voir son docblock), consommée à
        // l'identique par le sélecteur de l'étape 1 ET par la galerie modale, via x-html. Jamais un
        // aperçu réel (aucune donnée de contenu) - remplace les anciens iframes de mini-aperçu.
        templateWireframe(tpl) {
            return (window.SIGNATURE_TEMPLATE_WIREFRAMES || {})[tpl] || '';
        },

        // ------------------------------------------------------------------
        // Galerie de mises en page (LOT 4) - onglets par catégorie (registre), vraies vignettes
        // (même moteur renderSignature() que l'aperçu principal, jeu de données d'exemple générique).
        // ------------------------------------------------------------------

        // Catégories DISTINCTES du registre, dans leur ordre de première apparition (jamais triées
        // alphabétiquement - l'ordre du registre reflète déjà un classement éditorial voulu),
        // précédées de l'onglet « toutes » ajouté par la galerie elle-même.
        galleryCategoriesWithAll() {
            var registry = window.SIGNATURE_TEMPLATES || {};
            var seen = [];
            this.templates.forEach(function (tpl) {
                var cat = (registry[tpl] && registry[tpl].category) || 'classiques';
                if (seen.indexOf(cat) === -1) { seen.push(cat); }
            });
            return ['toutes'].concat(seen);
        },
        galleryCategoryLabel(cat) {
            return SIG_GALLERY_CATEGORY_LABELS[cat] || cat;
        },
        // Gabarits de l'onglet actif, dans l'ordre du registre - jamais un ré-agencement propre à la
        // galerie (une seule source d'ordre, comme templates ci-dessus).
        galleryTemplatesFor(cat) {
            var registry = window.SIGNATURE_TEMPLATES || {};
            return this.templates.filter(function (tpl) {
                if (cat === 'toutes') { return true; }
                return (registry[tpl] && registry[tpl].category) === cat;
            });
        },

        openGallery() {
            // Élément qui avait le focus avant l'ouverture (le bouton « Voir toutes les mises en
            // page ») - retrouvé à la fermeture, pour ne jamais perdre le focus clavier dans la page.
            this._galleryOpener = document.activeElement;
            this.gallerySelected = this.template;
            this.galleryCategory = 'toutes';
            this.showGalleryModal = true;
        },
        _closeGalleryReturnFocus() {
            this.showGalleryModal = false;
            if (this._galleryOpener && typeof this._galleryOpener.focus === 'function') {
                this._galleryOpener.focus();
            }
        },
        closeGallery() {
            this._closeGalleryReturnFocus();
        },
        selectGalleryTemplate(tpl) {
            this.gallerySelected = tpl;
        },
        // Applique le choix et ferme - updatePreview() est déjà déclenché par le $watch('template')
        // posé dans init(), aucun appel direct requis ici (DRY).
        applyGalleryTemplate() {
            if (this.gallerySelected) { this.template = this.gallerySelected; }
            this._closeGalleryReturnFocus();
        },

        // Navigation clavier ← → (et Origine/Fin) sur la barre d'onglets (ARIA tablist) - déplace le
        // focus ET sélectionne l'onglet visé (patron d'onglets standard, activation immédiate).
        // `delta` est soit un entier (±1, cyclique), soit 'first'/'last' pour Origine/Fin.
        galleryMoveTab(delta) {
            var cats = this.galleryCategoriesWithAll();
            var next;
            if (delta === 'first') {
                next = cats[0];
            } else if (delta === 'last') {
                next = cats[cats.length - 1];
            } else {
                var index = cats.indexOf(this.galleryCategory);
                // Modulo à deux temps : robuste même pour un delta négatif (JS `%` peut renvoyer un
                // résultat négatif sur un dividende négatif).
                next = cats[((index + delta) % cats.length + cats.length) % cats.length];
            }
            this.galleryCategory = next;
            this.$nextTick(() => {
                var el = document.getElementById('sig-gallery-tab-' + next);
                if (el) { el.focus(); }
            });
        },

        addSocialLink() {
            if (this.content.social_links.length >= 6) { return; }
            this.content.social_links.push({ platform: this.socialPlatforms[0], url: '' });
        },
        removeSocialLink(index) {
            this.content.social_links.splice(index, 1);
        },
        addMentionLine() {
            if (this.content.mention_lines.length >= 6) { return; }
            this.content.mention_lines.push('');
        },
        removeMentionLine(index) {
            this.content.mention_lines.splice(index, 1);
        },

        updatePreview() {
            var html = renderSignature({ template: this.template, content: this.content, images: this.images });
            this.previewSrcdoc = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><style>body{margin:0;padding:16px;font-family:Arial,Helvetica,sans-serif;background:#ffffff;}</style></head><body>' + html + '</body></html>';
        },

        payload() {
            return {
                template: this.template,
                content: this.content,
                reminder_email: this.reminderOptIn && this.reminderEmail ? this.reminderEmail : null
            };
        },

        async save() {
            this.saving = true;
            try {
                // Correctif B4 : c'est updateUrl SEUL qui decide du chemin PATCH, jamais
                // "token ET updateUrl". Un membre connecte (chemin "Mes signatures") n'a jamais de
                // token en clair (il n'est reaffiche qu'une fois, a la creation anonyme) mais
                // connait updateUrl des l'ouverture de l'editeur - avec l'ancienne condition,
                // save() retombait a tort sur draftStoreUrl (POST du flux anonyme) et n'ecrivait
                // jamais sur la signature du membre.
                var url = this.updateUrl ? this.updateUrl : this.draftStoreUrl;
                var method = this.updateUrl ? 'PATCH' : 'POST';
                var res = await fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' },
                    body: JSON.stringify(this.payload())
                });
                if (!res.ok) { throw new Error('save-failed'); }
                var data = await res.json();
                if (data.admin_token) {
                    this.token = data.admin_token;
                    this.updateUrl = data.manage_url ? data.manage_url.replace(location.origin, '') : this.updateUrl;
                    this.issuedManageUrl = data.manage_url || '';
                    this.showTokenModal = true;
                }
                if (window.toast) { window.toast('Signature enregistrée', 'success', 2500); }
                return true;
            } catch (e) {
                if (window.toast) { window.toast("Erreur lors de l'enregistrement.", 'danger', 4000); }
                return false;
            } finally {
                this.saving = false;
            }
        },

        // Correctif B4 : les deux branches de l'ancienne version etaient IDENTIQUES
        // (if (!this.token) { return this.save(); } return this.save();) - code mort qui trahissait
        // l'intention jamais implementee de traiter differemment le cas "pas encore de token". Un
        // simple appel suffit : save() gere deja tous les cas via updateUrl.
        async ensureSaved() {
            return this.save();
        },

        async onFileSelected(role, event) {
            var file = event.target.files && event.target.files[0];
            if (!file) { return; }

            var ok = await this.ensureSaved();
            if (!ok) { return; }

            this.uploading[role] = true;
            try {
                var displayWidth = (this.images[role] && this.images[role].display_width) || (role === 'portrait' ? 80 : 96);
                var form = new FormData();
                // Correctif B4 : un membre n'a jamais de token en clair - il s'authentifie par
                // signature_id + session (SignatureImageController::resolveSignature()). Le flux
                // anonyme continue d'envoyer le token comme avant.
                if (this.token) {
                    form.append('token', this.token);
                } else if (this.signatureId) {
                    form.append('signature_id', this.signatureId);
                }
                form.append('role', role);
                form.append('display_width', displayWidth);
                form.append('image', file);

                var res = await fetch(this.imagesUploadUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' },
                    body: form
                });
                if (!res.ok) {
                    var err = await res.json().catch(function () { return {}; });
                    throw new Error(err.message || 'upload-failed');
                }
                var data = await res.json();
                this.images[role] = data;
                if (window.toast) { window.toast('Image ajoutée', 'success', 2000); }
            } catch (e) {
                if (window.toast) { window.toast(e.message || "Erreur lors du téléversement de l'image.", 'danger', 4000); }
            } finally {
                this.uploading[role] = false;
                event.target.value = '';
            }
        },

        // M2.2 - les trois actions ci-dessous remplacent les <form method="POST"> bruts de
        // manage.blade.php : ces routes renvoient du JSON (JsonResponse), qu'un formulaire HTML
        // classique affichait tel quel dans le navigateur au lieu de l'interpreter - et rotateByToken()
        // invalidait l'ancien lien SANS aucune confirmation. Toutes passent par fetch() + toast, et
        // rotateLink() par la modale de confirmation confirmRotate (jamais confirm() natif).

        async extendLink() {
            if (!this.extendUrl) { return; }
            this.linkActionBusy = true;
            try {
                var res = await fetch(this.extendUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' }
                });
                if (!res.ok) { throw new Error('extend-failed'); }
                if (window.toast) { window.toast('Lien prolongé - avertissement annulé', 'success', 2500); }
            } catch (e) {
                if (window.toast) { window.toast('Erreur lors de la prolongation du lien.', 'danger', 4000); }
            } finally {
                this.linkActionBusy = false;
            }
        },

        async rotateLink() {
            if (!this.rotateUrl) { return; }
            this.linkActionBusy = true;
            try {
                var res = await fetch(this.rotateUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' }
                });
                if (!res.ok) { throw new Error('rotate-failed'); }
                var data = await res.json();

                // Le jeton change : toutes les URL qui le portaient doivent être redérivées AVANT
                // toute action suivante sur cette même page, sinon un clic ultérieur sur
                // "Prolonger"/"Rattacher" viserait encore l'ancien jeton, déjà invalidé.
                this.token = data.admin_token;
                this.updateUrl = data.manage_url ? data.manage_url.replace(location.origin, '') : this.updateUrl;
                if (this.manageUrlBase) {
                    this.extendUrl = this.manageUrlBase + '/' + this.token + '/prolonger';
                    this.rotateUrl = this.manageUrlBase + '/' + this.token + '/rotation';
                    this.attachUrl = this.manageUrlBase + '/' + this.token + '/rattacher-compte';
                }

                this.confirmRotate = false;
                this.issuedManageUrl = data.manage_url || '';
                this.showTokenModal = true;
                if (window.toast) { window.toast('Nouveau lien généré - l\'ancien ne fonctionne plus', 'success', 3000); }
            } catch (e) {
                if (window.toast) { window.toast('Erreur lors de la régénération du lien.', 'danger', 4000); }
            } finally {
                this.linkActionBusy = false;
            }
        },

        async attachAccount() {
            if (!this.attachUrl) { return; }
            this.linkActionBusy = true;
            try {
                var res = await fetch(this.attachUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrfToken(), 'Accept': 'application/json' }
                });
                if (!res.ok) {
                    var err = await res.json().catch(function () { return {}; });
                    throw new Error(err.message || 'attach-failed');
                }
                if (window.toast) { window.toast('Signature rattachée à ton compte', 'success', 2500); }
            } catch (e) {
                if (window.toast) { window.toast(e.message || 'Erreur lors du rattachement.', 'danger', 4000); }
            } finally {
                this.linkActionBusy = false;
            }
        },

        async resizeImage(role, displayWidth) {
            if (!this.images[role]) { return; }
            this.images[role].display_width = displayWidth;
            this.images[role].display_height = Math.round((this.images[role].height / this.images[role].width) * displayWidth);
        },

        async copyToClipboard() {
            var html = renderSignature({ template: this.template, content: this.content, images: this.images });
            var plain = document.createElement('div');
            plain.innerHTML = html;
            var text = plain.textContent || '';

            try {
                if (window.ClipboardItem) {
                    var item = new window.ClipboardItem({
                        'text/html': new Blob([html], { type: 'text/html' }),
                        'text/plain': new Blob([text], { type: 'text/plain' })
                    });
                    await navigator.clipboard.write([item]);
                } else {
                    await navigator.clipboard.writeText(text);
                }
                if (window.toast) { window.toast('Signature copiée - collez-la dans votre client courriel', 'success', 3000); }
            } catch (e) {
                if (window.toast) { window.toast('Impossible de copier automatiquement - sélectionnez l\'aperçu et copiez-le manuellement.', 'warning', 5000); }
            }
        },

        // LOT 3 - « Voir le code HTML » : export brut dans une modale DU THÈME (jamais une popup
        // native), avec copie en un clic. Le HTML montré est le rendu FINAL du moteur canonique
        // (renderSignature), identique à celui produit par copyToClipboard()/downloadHtm().
        openHtmlCode() {
            this.htmlSourceCode = renderSignature({ template: this.template, content: this.content, images: this.images });
            this.showHtmlModal = true;
        },
        async copyHtmlSource() {
            try {
                await navigator.clipboard.writeText(this.htmlSourceCode);
                if (window.toast) { window.toast('Code HTML copié', 'success', 2000); }
            } catch (e) {
                if (window.toast) { window.toast('Impossible de copier automatiquement - sélectionne le texte et copie-le manuellement.', 'warning', 4000); }
            }
        },

        // M4.5 - focus piégé dans la modale : pas de plugin Alpine Focus installé dans ce projet,
        // implémentation minimale (2 méthodes, aucune dépendance neuve) branchée sur les deux
        // modales de l'outil (jeton, confirmation de rotation). focusFirstIn() déplace le focus au
        // premier élément focusable à l'ouverture ; trapFocusTab() empêche Tab/Maj+Tab de sortir de
        // la boîte tant qu'elle est ouverte.
        focusFirstIn(container) {
            var focusables = container.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');
            if (focusables.length) { focusables[0].focus(); }
        },

        trapFocusTab(event) {
            var container = event.currentTarget;
            var focusables = container.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');
            if (!focusables.length) { return; }
            var first = focusables[0];
            var last = focusables[focusables.length - 1];
            if (event.shiftKey) {
                if (document.activeElement === first) { event.preventDefault(); last.focus(); }
            } else if (document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        },

        downloadHtm() {
            var html = renderSignature({ template: this.template, content: this.content, images: this.images });
            var doc = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Signature</title></head><body>' + html + '</body></html>';
            var blob = new Blob([doc], { type: 'text/html' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'signature.htm';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    };
}

// Enregistrement Alpine.data (patron déjà en place pour code-qr.blade.php,
// Modules/Tools/resources/views/public/tools/code-qr.blade.php) - x-data="signatureAssistant(...)"
// fonctionne aussi comme simple appel de fonction globale, ce bloc n'est qu'une garantie
// supplémentaire de disponibilité au moment où Alpine démarre.
document.addEventListener('alpine:init', function () {
    if (window.Alpine) {
        window.Alpine.data('signatureAssistant', signatureAssistant);
    }
});
