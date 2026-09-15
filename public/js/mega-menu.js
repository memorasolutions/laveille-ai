/**
 * Méga-menus de la barre de navigation - ouverture au CLIC.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * POURQUOI LE CLIC PLUTÔT QUE LE SURVOL (tickets #2561 et #2562, 2026-09-14).
 * Le survol fermait le panneau instantanément au `mouseleave`. Or un panneau large déborde sous
 * les entrées voisines : toute trajectoire en diagonale vers son contenu traversait un voisin,
 * qui fermait le premier panneau et ouvrait le sien. Pour un débutant, les menus « disparaissent ».
 * Le clic est déterministe : ça s'ouvre quand on clique, ça se ferme quand on clique ailleurs.
 *
 * ET SURTOUT, LES DEUX DÉFAUTS SE COMMANDENT L'UN L'AUTRE : le panneau « Outils » débordait de
 * 34 px sous une fenêtre de 700 px (mesuré au navigateur), ce qui poussait hors de l'écran sa
 * barre « Voir tous les outils gratuits » - la SEULE sortie vers la page d'index. Ajouter un
 * défilement interne sans régler la fermeture aurait été inutilisable : on ne fait pas défiler un
 * panneau qui se ferme dès que la souris s'écarte. Les deux se corrigent ensemble ou pas du tout.
 *
 * Alpine est fourni par Livewire et démarre EN BAS de page : l'enregistrement passe donc
 * obligatoirement par `alpine:init`, jamais par un appel direct à Alpine.data().
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('megaMenu', (id) => ({
        id,
        open: false,
        _source: Symbol('megaMenu'),
        _revision: 0,
        _ouvertureClavier: false,
        _bouton: null,
        _surClic: null,
        _surOuverture: null,
        _surTouche: null,
        _surRedimensionnement: null,

        init() {
            this._bouton = this.$refs.bouton;

            // La capture détecte l'activation au clavier avant @click="toggle()".
            this._surClic = (event) => {
                this._ouvertureClavier = event.detail === 0;
            };

            this._surOuverture = (event) => {
                // Le jeton distingue les instances, même avec des identifiants identiques.
                if (event.detail?.source !== this._source) {
                    this.close();
                }
            };

            this._surTouche = (event) => {
                if (event.key !== 'Escape' || event.isComposing || !this.open) {
                    return;
                }

                event.preventDefault();
                this.close();

                if (this._bouton?.isConnected) {
                    this._bouton.focus();
                }
            };

            this._bouton?.addEventListener('click', this._surClic, true);
            window.addEventListener('mega-menu:ouverture', this._surOuverture);
            window.addEventListener('keydown', this._surTouche, true);
        },

        toggle() {
            const auClavier = this._ouvertureClavier;
            this._ouvertureClavier = false;

            if (this.open) {
                this.close();
                return;
            }

            window.dispatchEvent(new CustomEvent('mega-menu:ouverture', {
                detail: { id: this.id, source: this._source },
            }));

            this.open = true;
            const revision = ++this._revision;

            // Recadrage horizontal, systématique (#2589). Le panneau est ancré sous son déclencheur,
            // ce qui est la bonne place ; il peut donc sortir de la fenêtre quand le déclencheur est
            // à droite. On le ramène du STRICT nécessaire, sans jamais le détacher de son bouton.
            this.$nextTick(() => {
                if (!this.open || revision !== this._revision) {
                    return;
                }

                // Une frame de plus que $nextTick : le panneau a une transition d'opacité, et
                // mesurer trop tôt renvoie une boîte vide, donc un débordement nul et aucun
                // recadrage. Défaut observé le 2026-09-15, invisible autrement que par la mesure.
                requestAnimationFrame(() => {
                    if (!this.open || revision !== this._revision) {
                        return;
                    }

                    this._recadrer();
                });

                // LA VRAIE CAUSE, TROUVÉE EN JOURNALISANT CHAQUE FRAME (#2589, 2026-09-15).
                // Le recadrage FONCTIONNAIT : `translateX(-140px)` était bel et bien posé, à la
                // quatrième frame. Puis il disparaissait. Le relevé image par image l'a montré
                // sans ambiguïté - `x-transition` d'Alpine écrit LUI AUSSI dans `style.transform`
                // (il y met `scale(1)`), et il le remet à sa valeur de fin de transition, ce qui
                // effaçait notre décalage. Deux mécanismes se disputaient la même propriété.
                //
                // D'où le correctif : le décalage passe par `margin-left`, à laquelle la
                // transition ne touche pas. Le conflit disparaît par construction, plutôt que
                // d'être arbitré par un réglage de délai.
                //
                // Deux hypothèses ont été essayées et RÉFUTÉES par la mesure avant d'être
                // livrées, elles sont notées pour qu'on ne les réessaie pas : attendre une frame
                // de plus (le débordement était déjà visible dès la frame 2, ce n'était pas un
                // problème de moment), et un `ResizeObserver` (il voit la taille, pas la position,
                // et la largeur était fixée par la feuille de style dès le départ).
                //
                // La relecture bornée ci-dessous reste utile comme filet : le panneau est masqué
                // par `display: none` pendant les deux premières frames, où il n'y a rien à
                // mesurer. Elle s'arrête dès qu'un décalage est posé.
                this._recadrerSurPlusieursFrames(revision, 20);

                // La fenêtre peut changer de largeur pendant que le menu est ouvert.
                window.removeEventListener('resize', this._surRedimensionnement);
                this._surRedimensionnement = () => {
                    if (!this.open || revision !== this._revision) {
                        return;
                    }

                    this._recadrer();
                };
                window.addEventListener('resize', this._surRedimensionnement);
            });

            if (!auClavier) {
                return;
            }

            this.$nextTick(() => {
                // Une fermeture ultérieure annule le déplacement de focus.
                if (!this.open || revision !== this._revision) {
                    return;
                }

                const panneau = this.$refs.panneau;

                if (!panneau?.isConnected) {
                    return;
                }

                const premierLien = Array.from(
                    panneau.querySelectorAll('a[href]')
                ).find((lien) => {
                    const style = window.getComputedStyle(lien);

                    return lien.getClientRects().length > 0
                        && style.visibility !== 'hidden'
                        && style.visibility !== 'collapse'
                        && !lien.closest('[inert], [hidden], [aria-hidden="true"]');
                });

                premierLien?.focus();
            });
        },

        /**
         * Relit la géométrie sur plusieurs frames, et s'arrête dès qu'un décalage est posé (#2589).
         *
         * Le panneau n'est pas à sa place définitive à la frame où il apparaît : mesurer une seule
         * fois revient à parier sur un instant, et ce pari a été perdu deux fois (voir le détail
         * dans `toggle()`). Bornée à `framesRestantes`, cette relecture ne peut pas s'emballer.
         */
        _recadrerSurPlusieursFrames(revision, framesRestantes) {
            if (!this.open || revision !== this._revision || framesRestantes <= 0) {
                return;
            }

            this._recadrer();

            // Un décalage posé signifie que la géométrie était enfin lisible : plus rien à relire.
            if (this.$refs.panneau?.style.marginLeft) {
                return;
            }

            requestAnimationFrame(() => {
                this._recadrerSurPlusieursFrames(revision, framesRestantes - 1);
            });
        },

        /**
         * Ramène le panneau dans la fenêtre s'il en sort, du strict nécessaire (#2589).
         *
         * Deux gardes qui ne se devinent pas : on remet le décalage à zéro AVANT de mesurer, sinon
         * un décalage précédent fausse le calcul ; et on ne décale jamais plus que la place
         * disponible à gauche, sinon on corrige un débordement en en créant un autre.
         */
        _recadrer() {
            const panneau = this.$refs.panneau;

            if (!panneau || !panneau.isConnected) {
                return;
            }

            panneau.style.marginLeft = '';

            // Sous 992 px, une règle de la feuille de style masque le panneau : rien à recadrer.
            if (window.innerWidth < 992) {
                return;
            }

            const MARGE = 16;
            const rect = panneau.getBoundingClientRect();

            // Le panneau a une transition d'opacité : tant qu'il n'est pas rendu, sa boîte vaut
            // zéro et le débordement paraît nul. Diagnostiqué le 2026-09-15 : appelée à la main la
            // méthode fonctionnait, seul le moment de l'appel était trop tôt. On réessaie à la
            // frame suivante, avec un plafond pour ne jamais boucler.
            if (rect.width === 0) {
                this._essaisRecadrage = (this._essaisRecadrage || 0) + 1;

                if (this._essaisRecadrage <= 10 && this.open) {
                    requestAnimationFrame(() => this._recadrer());
                }

                return;
            }

            this._essaisRecadrage = 0;

            const debordement = rect.right - (window.innerWidth - MARGE);

            if (debordement <= 0) {
                return;
            }

            const decalage = Math.min(debordement, Math.max(0, rect.left - MARGE));

            if (decalage > 0) {
                panneau.style.marginLeft = '-' + Math.round(decalage) + 'px';
            }
        },

        close() {
            this.open = false;
            this._ouvertureClavier = false;
            this._revision++;

            window.removeEventListener('resize', this._surRedimensionnement);
            this._surRedimensionnement = null;

            if (this.$refs.panneau) {
                this.$refs.panneau.style.marginLeft = '';
            }
        },

        destroy() {
            this.close();
            this._bouton?.removeEventListener('click', this._surClic, true);
            window.removeEventListener('mega-menu:ouverture', this._surOuverture);
            window.removeEventListener('keydown', this._surTouche, true);
        },
    }));
});
