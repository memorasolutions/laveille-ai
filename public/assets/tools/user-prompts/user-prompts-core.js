/**
 * Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
 *
 * user-prompts-core.js - logique Alpine.js de /user/prompts ("Mes prompts", bibliothèque de
 * prompts sauvegardés). Extrait du bloc <script> inline de
 * Modules/Tools/resources/views/user/prompts/index.blade.php (tâche #1416, planifiée au round 132
 * de PromptsLibraryScriptIntegrityTest.php - voir ce fichier pour le contexte complet).
 *
 * Extraction PURE : toute la logique ci-dessous est identique à l'original, méthode par méthode.
 * Seul le point d'entrée des données serveur change, suivant exactement le pattern déjà établi
 * pour constructeur-prompts-core.js (Alpine.data('nomFonction', function (config) {...}) sur
 * alpine:init, données injectées via x-data="promptsLibrary(@js($config))") : les valeurs
 * auparavant interpolées inline via {{ Illuminate\Support\Js::from(...) }} arrivent maintenant par
 * l'objet `config` (profil, compteur) et `config.i18n` (tous les textes traduits __()), avec un
 * repli français en dur si l'injection venait à manquer (même filet de sécurité que
 * profile-anon-guard.js) - ce repli ne se déclenche jamais en usage normal, le Blade peuple
 * toujours `config` intégralement.
 */
document.addEventListener('DOMContentLoaded', function() { if (window.lucide) lucide.createIcons(); });
if (document.readyState !== 'loading' && window.lucide) { lucide.createIcons(); }

document.addEventListener('alpine:init', function () {
    'use strict';

    Alpine.data('promptsLibrary', function (config) {
        config = config || {};
        var i18n = config.i18n || {};

        return {
            profileRole: config.profileRole || '',
            profileStyle: config.profileStyle || '',
            profileConstraints: config.profileConstraints || '',
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
            promptCount: config.promptCount || 0,
            _promptCountZero: i18n.countZero || 'Aucun prompt sauvegardé.',
            _promptCountOne: i18n.countOne || '1 prompt sauvegardé.',
            _promptCountMany: i18n.countMany || ':count prompts sauvegardés.',

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
                        this._toast(i18n.profileSaved || 'Profil enregistré.', 'success');
                    } else {
                        this._toast(i18n.profileSaveError || 'Erreur lors de l\'enregistrement.', 'danger');
                    }
                } catch (e) {
                    this._toast(i18n.networkError || 'Erreur réseau.', 'danger');
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
                        buttonEl.setAttribute('aria-label', next ? (i18n.removeFavoriteLabel || 'Retirer des favoris') : (i18n.addFavoriteLabel || 'Ajouter aux favoris'));
                        this._toast(next ? (i18n.addedFavorite || 'Ajouté aux favoris.') : (i18n.removedFavorite || 'Retiré des favoris.'), 'success');
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
                        this._toast(i18n.updateError || 'Erreur lors de la mise à jour.', 'danger');
                    }
                } catch (e) {
                    this._toast(i18n.networkError || 'Erreur réseau.', 'danger');
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
                    var msgTooLong = i18n.tagsTooLong || 'Ces étiquettes dépassent 30 caractères : :tags. Raccourcissez-les avant d\'enregistrer.';
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
                        this._toast(i18n.tagsUpdated || 'Tags mis à jour.', 'success');
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
                        this._toast(i18n.tagsUpdateError || 'Erreur lors de la mise à jour des tags.', 'danger');
                    }
                    return false;
                } catch (e) {
                    this._toast(i18n.networkError || 'Erreur réseau.', 'danger');
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
                        this._toast(i18n.duplicated || 'Prompt dupliqué.', 'success');
                        setTimeout(function() { window.location.reload(); }, 700);
                    } else {
                        this._toast(i18n.duplicateError || 'Erreur lors de la duplication.', 'danger');
                        this._duplicatingIds.delete(publicId);
                        this._restoreCardFocus(publicId);
                    }
                } catch (e) {
                    this._toast(i18n.networkError || 'Erreur réseau.', 'danger');
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
                        this._toast(i18n.deleted || 'Prompt supprimé.', 'success');
                        this._reloadIfListEmpty();
                    } else {
                        this._toast(i18n.deleteError || 'Erreur lors de la suppression.', 'danger');
                        this._deletingIds.delete(publicId);
                    }
                } catch (e) {
                    this._toast(i18n.networkError || 'Erreur réseau.', 'danger');
                    this._deletingIds.delete(publicId);
                }
            },
        };
    });
});
