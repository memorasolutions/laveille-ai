/*
 * Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
 *
 * Composant Alpine.js de l'éditeur de signature. Mécanique du formulaire UNIQUEMENT (répéteurs,
 * validation légère, appels réseau pour la sauvegarde/les images) - jamais le rendu HTML final de
 * la signature, produit EXCLUSIVEMENT par window.renderSignature() (signature-render.js, section 4
 * du plan : une seule fonction alimente l'aperçu, la copie et le téléchargement).
 */
function signatureAssistant(config) {
    return {
        template: config.initialTemplate || 'minimal',
        content: Object.assign({
            first_name: '', last_name: '', job_title: '', organization: '', email: '', phone: '',
            mobile: '', website: '', address: '', tagline: '', cta_text: '', cta_url: '',
            accent_color: '#064E5A', font_family: 'Arial', social_links: [], mention_lines: []
        }, config.initialContent || {}),
        images: Object.assign({ logo: null, portrait: null, banniere: null }, config.initialImages || {}),
        templates: config.templates || ['minimal', 'professionnel', 'portrait', 'compact'],
        fontFamilies: config.fontFamilies || ['Arial', 'Helvetica', 'Verdana', 'Georgia', 'Tahoma'],
        socialPlatforms: config.socialPlatforms || ['linkedin', 'facebook', 'instagram', 'x', 'youtube', 'website'],
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
        showTokenModal: false,
        issuedManageUrl: '',
        confirmRotate: false,
        linkActionBusy: false,

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
