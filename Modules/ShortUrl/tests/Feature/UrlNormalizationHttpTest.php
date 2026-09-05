<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\ShortUrl\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ShortUrl\Models\ShortUrl;
use Tests\TestCase;

/**
 * Ce que ce fichier prouve, et que NormalizesPastedUrlsTest ne prouvait PAS
 * (revue adversariale Codex, 2026-09-05) : que le trait est REELLEMENT CABLE
 * sur la route, pas seulement qu'il fonctionne isolement.
 *
 * La distinction n'est pas theorique. Pendant l'implementation du 2026-09-05,
 * les six appels ont ete poses SANS que le trait soit importe dans les classes
 * (l'expression de recherche exigeait « class X extends Y », or ces controleurs
 * n'heritent de rien) : les tests du trait isole restaient verts alors que la
 * page aurait plante. Un test HTTP l'aurait vu tout de suite.
 *
 * A LIRE AVANT DE S'APPUYER SUR CES TESTS - mesure du 2026-09-05, par temoin
 * rouge (trait neutralise dans le controleur, suite relancee) : UN SEUL des
 * quatre tests rougit sans le trait, celui des blancs Unicode les moins
 * visibles. Les trois autres passent quand meme, parce que le middleware
 * TrimStrings de Laravel appelle Str::trim(), dont le motif couvre deja
 * l'espace ASCII, l'insecable U+00A0, la largeur nulle U+200B, le trait mou
 * U+00AD et le mongol U+180E (Str.php:1613, constante INVISIBLE_CHARACTERS).
 *
 * Ces trois tests gardent leur valeur - ils verrouillent le COMPORTEMENT de
 * bout en bout, qui doit rester vrai meme si Laravel change son motif un jour -
 * mais ils ne prouvent PAS le trait. Ne jamais conclure de leur vert que la
 * normalisation maison fonctionne : seul le troisieme test le demontre.
 */
class UrlNormalizationHttpTest extends TestCase
{
    use RefreshDatabase;

    /** Adresse propre attendue en base, quelle que soit la salete envoyee. */
    private const PROPRE = 'https://docs.google.com/spreadsheets/d/ABC123/export?format=pdf&range=A1:z52';

    public function test_la_porte_publique_nettoie_les_blancs_colles_autour_de_l_adresse(): void
    {
        // Le cas REEL de Stephane : espaces + saut de ligne colles par Google Sheets.
        $sale = self::PROPRE."    \n    ";

        $reponse = $this->postJson(route('shorturl.store'), ['url' => $sale]);

        $reponse->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('short_urls', ['original_url' => self::PROPRE]);
        $this->assertDatabaseMissing('short_urls', ['original_url' => $sale]);
    }

    public function test_la_porte_publique_nettoie_l_espace_insecable_que_le_navigateur_laisse_passer(): void
    {
        // MESURE du 2026-09-05 : <input type="url"> declare cette valeur VALIDE
        // et l'envoie telle quelle. Sans normalisation serveur, la validation
        // « url » la refuse et l'utilisateur voit un refus incomprehensible.
        $sale = "\u{00A0}".self::PROPRE."\u{00A0}";

        $reponse = $this->postJson(route('shorturl.store'), ['url' => $sale]);

        $reponse->assertOk();
        $this->assertDatabaseHas('short_urls', ['original_url' => self::PROPRE]);
    }

    public function test_la_porte_publique_nettoie_les_blancs_unicode_les_moins_visibles(): void
    {
        // LE SEUL TEST DISCRIMINANT du fichier (temoin rouge du 2026-09-05) :
        // U+2066 est le seul de la serie que Str::trim de Laravel ne retire pas.
        // Sans le trait, cette adresse est refusee en 422 - mesure, pas suppose.
        $sale = "\u{2066}".self::PROPRE."\u{200B}";

        $reponse = $this->postJson(route('shorturl.store'), ['url' => $sale]);

        $reponse->assertOk();
        $this->assertDatabaseHas('short_urls', ['original_url' => self::PROPRE]);
    }

    public function test_une_adresse_deja_propre_traverse_sans_etre_modifiee(): void
    {
        $reponse = $this->postJson(route('shorturl.store'), ['url' => self::PROPRE]);

        $reponse->assertOk();
        $this->assertSame(
            self::PROPRE,
            ShortUrl::query()->latest('id')->first()?->original_url,
            "Une adresse propre ne doit subir AUCUNE reecriture."
        );
    }
}
