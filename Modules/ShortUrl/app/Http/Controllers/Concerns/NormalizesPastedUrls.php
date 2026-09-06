<?php

declare(strict_types=1);

namespace Modules\ShortUrl\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Nettoie les blancs invisibles qu'un collage traine autour d'une URL.
 *
 * Demande du 2026-09-05 : coller un lien depuis une cellule Google Sheets
 * ramene des espaces, une tabulation et un retour de ligne. La regle de
 * validation `url` de Laravel s'appuie sur filter_var(), qui REFUSE alors
 * l'adresse - l'usager devait nettoyer a la main a chaque fois.
 */
trait NormalizesPastedUrls
{
    /** Tous les champs du module qui recoivent une URL collee. */
    protected const URL_INPUT_FIELDS = ['url', 'original_url', 'og_image', 'thumbnail'];

    // ACTION: retirer les blancs de DEBUT et de FIN des champs d'URL avant validation.
    // SELF: 12 lignes, coeur critique du correctif (le piege Unicode se joue ici).
    // RAISON: trim() ne retire QUE " \t\n\r\0\x0B". Il laisse passer l'espace
    // insecable U+00A0, les espaces fins U+2000-200A, le U+200B et le BOM U+FEFF,
    // qui sont precisement ce que produisent les collages depuis un tableur ou une
    // page web. LV_URL_BLANCS_INVISIBLES (app/Helpers/typo.php) est la SOURCE UNIQUE
    // de cette classe de caracteres (ticket #2289, 2026-09-05 - avant ce correctif elle
    // etait dupliquee ici en dur) : \p{Z} couvre les separateurs Unicode, \p{C} les
    // caracteres de controle et de format. Les blancs INTERNES ne sont jamais touches :
    // on ne reecrit pas l'adresse de l'usager, on enleve seulement ce qui l'entoure.
    protected function normalizePastedUrls(Request $request): void
    {
        $propres = [];

        foreach (static::URL_INPUT_FIELDS as $champ) {
            $valeur = $request->input($champ);

            if (! is_string($valeur)) {
                continue;
            }

            $nettoyee = preg_replace('/^['.LV_URL_BLANCS_INVISIBLES.'\s]+|['.LV_URL_BLANCS_INVISIBLES.'\s]+$/u', '', $valeur);

            // preg_replace rend null sur erreur (ex. sequence UTF-8 invalide) :
            // on garde alors la valeur d'origine plutot que de vider le champ.
            $propres[$champ] = $nettoyee ?? $valeur;
        }

        if ($propres !== []) {
            $request->merge($propres);
        }
    }
}
