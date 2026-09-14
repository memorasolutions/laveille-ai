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

        close() {
            this.open = false;
            this._ouvertureClavier = false;
            this._revision++;
        },

        destroy() {
            this.close();
            this._bouton?.removeEventListener('click', this._surClic, true);
            window.removeEventListener('mega-menu:ouverture', this._surOuverture);
            window.removeEventListener('keydown', this._surTouche, true);
        },
    }));
});
