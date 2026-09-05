{{--
  Nettoyage des blancs invisibles colles autour d'une URL (demande 2026-09-05).

  MESURE du 2026-09-05, en navigateur reel : <input type="url"> retire DEJA
  lui-meme les espaces, tabulations et sauts de ligne ASCII. En revanche il ne
  retire PAS l'espace insecable U+00A0 et le declare quand meme valide : il part
  donc au serveur, ou la validation le refuse. C'est CE cas que le correctif ferme.

  LE MOTIF EST LE MEME QUE CELUI DU SERVEUR, litteralement (revue adversariale
  Codex, 2026-09-05). Une premiere version couvrait ici moins de caracteres que
  \p{Z}\p{C} cote PHP : SIX divergeaient (U+2066 a U+2069, U+00AD, U+180E),
  nettoyes par le serveur mais pas par le navigateur - le champ aurait affiche une
  valeur que le serveur modifiait ensuite. Toute evolution de l'un des deux motifs
  doit etre reportee dans l'autre, sinon la divergence revient.

  MESURE du meme jour : le formulaire PUBLIC n'a pas d'attribut name, il passe par
  Alpine (x-model="url") et envoie son propre etat, pas champ.value. D'ou
  l'evenement input reemis apres nettoyage : il resynchronise Alpine comme Livewire.

  Le selecteur nomme les champs des cinq vues plutot que tout input[type="url"] du
  document : un champ d'adresse ajoute par un gabarit ou une modale ne doit pas
  etre modifie a son insu (meme revue Codex).

  Le serveur reste la protection (trait NormalizesPastedUrls) ; ceci rend le
  nettoyage VISIBLE.
--}}
<script>
(function () {
    'use strict';

    // Identique au motif serveur. \p{Z} = separateurs, \p{C} = controles et
    // formatage (insecables, largeurs nulles, isolats bidi, BOM). Ancre en DEBUT
    // et FIN seulement : l'interieur de l'adresse n'est jamais reecrit.
    var BLANCS = /^[\p{Z}\p{C}\s]+|[\p{Z}\p{C}\s]+$/gu;

    var CHAMPS = [
        'input[type="url"][name="original_url"]',
        'input[type="url"][name="url"]',
        'input[type="url"][name="og_image"]',
        'input[type="url"][name="thumbnail"]',
        'input[type="url"][x-model="url"]',
        'input[type="url"][x-model="og_image"]'
    ].join(', ');

    function nettoyer(champ) {
        if (! champ || typeof champ.value !== 'string') { return; }
        var propre = champ.value.replace(BLANCS, '');
        if (propre === champ.value) { return; }

        champ.value = propre;
        // Indispensable : Alpine et Livewire lisent leur propre etat, pas le DOM.
        // Sans cet evenement, la valeur sale partirait quand meme.
        champ.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function brancher() {
        Array.prototype.forEach.call(document.querySelectorAll(CHAMPS), function (champ) {
            // Au collage : setTimeout(0) obligatoire, la valeur n'est pas encore
            // posee au moment ou l'evenement paste se declenche.
            champ.addEventListener('paste', function () {
                setTimeout(function () { nettoyer(champ); }, 0);
            });
            champ.addEventListener('blur', function () { nettoyer(champ); });
            champ.addEventListener('change', function () { nettoyer(champ); });

            if (champ.form) {
                champ.form.addEventListener('submit', function () { nettoyer(champ); });
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', brancher);
    } else {
        brancher();
    }
})();
</script>
