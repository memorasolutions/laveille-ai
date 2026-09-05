{{--
  Nettoyage des blancs invisibles colles autour d'une URL (demande 2026-09-05).

  MESURE du 2026-09-05, en navigateur reel : <input type="url"> retire DEJA
  lui-meme les espaces, tabulations et sauts de ligne ASCII (algorithme de
  nettoyage de la specification HTML). En revanche il ne retire PAS l'espace
  insecable U+00A0 et le declare quand meme valide : il part donc au serveur,
  ou la validation le refuse. C'est CE cas que le correctif couvre.

  MESURE du meme jour, dans ce module : le formulaire PUBLIC n'a pas
  d'attribut name, il passe par Alpine (x-model="url") et envoie this.url,
  pas champ.value. Nettoyer la valeur sans prevenir Alpine donnerait un champ
  propre A L'ECRAN mais une valeur sale ENVOYEE. D'ou l'evenement input
  reemis apres chaque nettoyage : il resynchronise Alpine comme Livewire.
  Le selecteur cible donc le TYPE (commun aux 5 vues) et non le nom.

  Le serveur normalise de son cote (trait NormalizesPastedUrls) : ce script
  ne fait que rendre le nettoyage visible, il n'est pas la protection.
--}}
<script>
(function () {
    'use strict';

    // En JS, \s couvre deja U+00A0, U+1680, U+2000-200A, U+2028, U+2029,
    // U+202F, U+205F, U+3000 et U+FEFF. On ajoute les largeurs nulles
    // (U+200B a U+200D, U+2060), qui n'en font PAS partie.
    // Ancre en DEBUT et FIN seulement : l'interieur n'est jamais reecrit.
    var BLANCS = /^[\s\u200B-\u200D\u2060]+|[\s\u200B-\u200D\u2060]+$/g;

    function nettoyer(champ) {
        if (! champ || typeof champ.value !== 'string') { return; }
        var propre = champ.value.replace(BLANCS, '');
        if (propre === champ.value) { return; }

        champ.value = propre;
        // Indispensable : Alpine et Livewire lisent leur propre etat, pas le
        // DOM. Sans cet evenement, la valeur sale partirait quand meme.
        champ.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function brancher() {
        var champs = document.querySelectorAll('input[type="url"]');

        Array.prototype.forEach.call(champs, function (champ) {
            // Au collage : setTimeout(0) obligatoire, la valeur n'est pas
            // encore posee au moment ou l'evenement paste se declenche.
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
