<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Analytics\Services\ContentUrlResolver;
use Modules\Dictionary\Models\Term;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Tests\TestCase;

class ContentUrlResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolver(): ContentUrlResolver
    {
        return new ContentUrlResolver;
    }

    /** Registre fictif : dossier AVANT actualités, comme dans la vraie config. */
    private function fictitiousRegistry(bool $dossierFirst = true): array
    {
        $dossier = ['prefix' => '/actualites/dossier/', 'type' => 'dossier', 'resolver' => null];
        $news = ['prefix' => '/actualites/', 'type' => 'news_article', 'resolver' => fn (string $slug): ?int => 42];

        return $dossierFirst ? [$dossier, $news] : [$news, $dossier];
    }

    public function test_la_normalisation_donne_le_meme_resultat_pour_toutes_les_formes_d_url(): void
    {
        config(['analytics.content_resolvers' => $this->fictitiousRegistry()]);

        $attendu = ['content_id' => 42, 'content_type' => 'news_article'];

        foreach ([
            'https://laveille.ai/actualites/mon-article',
            '/actualites/mon-article',
            '/actualites/mon-article?utm_source=x&a=1',
            '/actualites/mon-article/',
            'https://laveille.ai/actualites/mon-article/?q=1#ancre',
            'actualites/mon-article',
        ] as $url) {
            $this->assertSame($attendu, $this->resolver()->resolve($url), $url);
        }
    }

    public function test_normalize_ne_force_pas_la_casse_et_garde_la_racine(): void
    {
        $this->assertSame('/Actualites/Mon-Article', $this->resolver()->normalize('https://x.ca/Actualites/Mon-Article/?a=1'));
        $this->assertSame('/', $this->resolver()->normalize('https://x.ca'));
        $this->assertSame('/', $this->resolver()->normalize('/'));
    }

    public function test_l_ordre_des_prefixes_fait_gagner_le_dossier(): void
    {
        config(['analytics.content_resolvers' => $this->fictitiousRegistry(true)]);

        $this->assertSame(
            ['content_id' => null, 'content_type' => 'dossier'],
            $this->resolver()->resolve('/actualites/dossier/openai')
        );
    }

    public function test_l_ordre_inverse_change_le_resultat_ce_qui_prouve_que_l_ordre_compte(): void
    {
        config(['analytics.content_resolvers' => $this->fictitiousRegistry(false)]);

        $this->assertSame(
            ['content_id' => 42, 'content_type' => 'news_article'],
            $this->resolver()->resolve('/actualites/dossier/openai')
        );
    }

    public function test_chemin_inconnu_config_vide_et_slug_introuvable_donnent_null(): void
    {
        config(['analytics.content_resolvers' => $this->fictitiousRegistry()]);
        $this->assertSame(['content_id' => null, 'content_type' => null], $this->resolver()->resolve('/inconnu/page'));
        $this->assertSame(['content_id' => null, 'content_type' => null], $this->resolver()->resolve('/'));

        config(['analytics.content_resolvers' => [
            ['prefix' => '/x/', 'type' => 't', 'resolver' => fn (string $slug): ?int => null],
        ]]);
        $this->assertSame(['content_id' => null, 'content_type' => null], $this->resolver()->resolve('/x/absent'));

        config(['analytics.content_resolvers' => []]);
        $this->assertSame(['content_id' => null, 'content_type' => null], $this->resolver()->resolve('/actualites/a'));
    }

    public function test_la_config_livree_declare_le_dossier_avant_les_actualites(): void
    {
        $prefixes = array_column(config('analytics.content_resolvers'), 'prefix');

        $this->assertLessThan(
            array_search('/actualites/', $prefixes, true),
            array_search('/actualites/dossier/', $prefixes, true)
        );
        $this->assertSame(
            ['/actualites/dossier/', '/actualites/', '/glossaire/', '/blog/', '/outils/', '/annuaire/'],
            $prefixes
        );
    }

    public function test_integration_reelle_une_actualite_publiee_se_resout_et_une_depubliee_non(): void
    {
        $source = NewsSource::firstOrCreate(
            ['url' => 'https://analytics-test.exemple.com/rss'],
            ['name' => 'Source analytics test', 'language' => 'fr', 'active' => true]
        );

        $make = fn (string $slug, bool $published) => NewsArticle::create([
            'news_source_id' => $source->id,
            'title' => 'Titre '.$slug,
            'guid' => 'guid-'.$slug,
            'url' => 'https://exemple.com/'.$slug,
            'description' => '',
            'summary' => 'Résumé '.$slug,
            'slug' => $slug,
            'pub_date' => now(),
            'is_published' => $published,
            'seo_status' => 'index',
        ]);

        $publie = $make('article-publie', true);
        $brouillon = $make('article-brouillon', false);

        $this->assertSame(
            ['content_id' => $publie->id, 'content_type' => 'news_article'],
            $this->resolver()->resolve('https://laveille.ai/actualites/article-publie/?utm=1')
        );
        $this->assertSame(['content_id' => null, 'content_type' => null], $this->resolver()->resolve('/actualites/article-brouillon'));
        $this->assertSame(['content_id' => null, 'content_type' => null], $this->resolver()->resolve('/actualites/inexistant'));
        $this->assertSame(['content_id' => null, 'content_type' => 'dossier'], $this->resolver()->resolve('/actualites/dossier/openai'));
        $this->assertNotNull($brouillon->id);
    }

    public function test_integration_reelle_un_terme_au_slug_traduisible_se_resout(): void
    {
        config(['app.locale' => 'fr_CA']);

        $terme = Term::create([
            'name' => ['fr_CA' => 'Terme analytics', 'fr' => 'Terme analytics'],
            'slug' => ['fr_CA' => 'terme-analytics', 'fr' => 'terme-analytics'],
            'definition' => ['fr_CA' => 'Définition de test.', 'fr' => 'Définition de test.'],
            'is_published' => true,
        ]);

        $this->assertSame(
            ['content_id' => $terme->id, 'content_type' => 'term'],
            $this->resolver()->resolve('/glossaire/terme-analytics')
        );

        $terme->update(['is_published' => false]);
        $this->assertSame(['content_id' => null, 'content_type' => null], $this->resolver()->resolve('/glossaire/terme-analytics'));
    }

    public function test_un_modele_absent_ne_casse_pas_la_resolution(): void
    {
        config(['analytics.content_resolvers' => [
            ['prefix' => '/z/', 'type' => 'z', 'resolver' => ['model' => 'Modules\\Inexistant\\Models\\Nope']],
        ]]);

        $this->assertSame(['content_id' => null, 'content_type' => null], $this->resolver()->resolve('/z/abc'));
    }
}
