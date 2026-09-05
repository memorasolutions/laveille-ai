<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\ShortUrl\Tests\Feature;

use Illuminate\Http\Request;
use Modules\ShortUrl\Http\Controllers\Concerns\NormalizesPastedUrls;
use Tests\TestCase;

/**
 * Demande du 2026-09-05 : un lien colle depuis une cellule Google Sheets traine
 * des espaces, une tabulation et un retour de ligne, et la validation le REFUSE.
 */
class NormalizesPastedUrlsTest extends TestCase
{
    private object $sujet;

    protected function setUp(): void
    {
        parent::setUp();

        // Objet minimal qui expose le trait, sans dependre d'un controleur reel.
        $this->sujet = new class
        {
            use NormalizesPastedUrls;

            public function nettoie(Request $r): void
            {
                $this->normalizePastedUrls($r);
            }
        };
    }

    /** Le cas EXACT rapporte par Stephane : collage depuis Google Sheets. */
    public function test_le_collage_google_sheets_devient_une_url_valide(): void
    {
        $propre = 'https://docs.google.com/spreadsheets/d/1KNK/export?format=pdf&gid=2034284940';
        $colle = $propre."    \t\n    ";

        // Avant : la regle `url` de Laravel s'appuie sur filter_var, qui refuse.
        $this->assertFalse((bool) filter_var($colle, FILTER_VALIDATE_URL), 'le collage brut doit bien etre refuse');

        $r = Request::create('/', 'POST', ['original_url' => $colle]);
        $this->sujet->nettoie($r);

        $this->assertSame($propre, $r->input('original_url'));
        $this->assertNotFalse(filter_var($r->input('original_url'), FILTER_VALIDATE_URL));
    }

    /**
     * LE PIEGE : trim() de PHP ne retire PAS l'espace insecable U+00A0, ni les
     * autres blancs Unicode. Un correctif base sur trim() passerait le test
     * precedent tout en laissant CELUI-CI rouge.
     */
    public function test_les_blancs_unicode_invisibles_sont_retires(): void
    {
        $propre = 'https://exemple.com/page';

        $cas = [
            'insecable U+00A0' => $propre."\u{00A0}\u{00A0}",
            'espace fin U+2009' => $propre."\u{2009}",
            'zero-width U+200B' => $propre."\u{200B}",
            'BOM U+FEFF en tete' => "\u{FEFF}".$propre,
            'espaces des deux cotes' => '  '.$propre.'  ',
            'retour chariot Windows' => $propre."\r\n",
        ];

        foreach ($cas as $nom => $valeur) {
            // Preuve que trim() seul NE SUFFIT PAS sur les blancs Unicode.
            $r = Request::create('/', 'POST', ['url' => $valeur]);
            $this->sujet->nettoie($r);

            $this->assertSame($propre, $r->input('url'), "cas non couvert : {$nom}");
            $this->assertNotFalse(filter_var($r->input('url'), FILTER_VALIDATE_URL), "url invalide apres nettoyage : {$nom}");
        }
    }

    /** Les 4 champs d'URL du module sont couverts, pas seulement original_url. */
    public function test_les_quatre_champs_url_sont_nettoyes(): void
    {
        $propre = 'https://exemple.com/img.png';

        $r = Request::create('/', 'POST', [
            'url' => $propre." \t",
            'original_url' => $propre."\u{00A0}",
            'og_image' => '  '.$propre,
            'thumbnail' => $propre."\n",
        ]);
        $this->sujet->nettoie($r);

        foreach (['url', 'original_url', 'og_image', 'thumbnail'] as $champ) {
            $this->assertSame($propre, $r->input($champ), "champ non nettoye : {$champ}");
        }
    }

    /** Garde-fou : on n'a pas le droit de reecrire l'INTERIEUR de l'adresse. */
    public function test_un_espace_interne_n_est_jamais_touche(): void
    {
        $avecEspaceInterne = 'https://exemple.com/a b';

        $r = Request::create('/', 'POST', ['original_url' => $avecEspaceInterne]);
        $this->sujet->nettoie($r);

        $this->assertSame($avecEspaceInterne, $r->input('original_url'));
    }

    /** Un champ absent ou non textuel ne doit pas etre invente ni casser. */
    public function test_un_champ_absent_reste_absent(): void
    {
        $r = Request::create('/', 'POST', ['original_url' => 'https://exemple.com']);
        $this->sujet->nettoie($r);

        $this->assertNull($r->input('og_image'));
        $this->assertFalse($r->has('thumbnail'));
    }
}
