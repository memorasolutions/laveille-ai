<?php

declare(strict_types=1);

/**
 * Protection des fiches curatées contre le pipeline machine (incident 2026-08-18 : la photo
 * générée de la fiche 33558 a été écrasée par la vignette de marque 20 minutes après sa
 * publication, par news:reprocess --unresolved-only planifié à :15 toutes les 2 heures).
 * Deux gardes testées : exclusion à la SÉLECTION (fiche manuelle / fiche composée) et
 * hasCuratedImage() (défense en profondeur, image avec crédit jamais régénérée).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function aipManualSource(): NewsSource
{
    return NewsSource::firstOrCreate(
        ['url' => 'manuel://soumission-directe'],
        ['name' => 'Soumission manuelle', 'language' => 'fr', 'active' => false]
    );
}

function aipArticle(int $sourceId, array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => "Fiche protection image {$i}",
        'guid' => "guid-aip-{$suffix}",
        'url' => "https://exemple.com/aip-{$suffix}",
        'description' => '',
        'summary' => 'Résumé.',
        'slug' => "aip-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

it('hasCuratedImage est vrai dès qu un crédit d image est présent', function () {
    $source = aipManualSource();
    $avec = aipArticle($source->id, ['image_credit' => 'Image : générée (Gemini)']);
    $sans = aipArticle($source->id);

    expect($avec->hasCuratedImage())->toBeTrue()
        ->and($sans->hasCuratedImage())->toBeFalse();
});

it('news:reprocess exclut les fiches manuelles et composées de sa sélection', function () {
    $manuelle = aipArticle(aipManualSource()->id, ['image_credit' => 'Image : générée (Gemini)']);

    $rss = NewsSource::create([
        'name' => 'Source RSS protection', 'url' => 'https://aip-rss.exemple.com/rss',
        'language' => 'fr', 'active' => true,
    ]);
    $composee = aipArticle($rss->id, ['structured_summary' => ['composed' => true, 'hook' => 'Accroche.']]);

    // dry-run : aucune écriture, mais la liste des articles traités est affichée.
    $this->artisan('news:reprocess', ['--unresolved-only' => true, '--dry-run' => true, '--limit' => 50])
        ->doesntExpectOutputToContain("[{$manuelle->id}]")
        ->doesntExpectOutputToContain("[{$composee->id}]")
        ->assertSuccessful();
});

/**
 * GARDE-FOU #2244 - une fiche ne doit plus pouvoir etre publiee sans vraie photo.
 *
 * Contexte : deux recidives en quelques jours (2026-08-31 puis 2026-09-03), ou des fiches sont
 * parties en production avec la CARTE DE REPLI generee par NewsImageService::generateFallbackImage()
 * - un degrade portant le titre, souvent en anglais - au lieu d'une photo. Le champ image_url etait
 * rempli dans les deux cas (le repli ecrit au MEME chemin), il ne distingue donc rien.
 *
 * Le signal retenu est hasCuratedImage() (image_credit rempli), et il n'est pas choisi au hasard :
 * c'est DEJA le predicat par lequel le projet reconnait une image posee deliberement, utilise par
 * RegenerateFallbackImagesCommand et ReprocessArticlesCommand pour ne jamais l'ecraser. Le garde-fou
 * ne cree pas une regle nouvelle, il ferme la porte de sortie d'une regle qui existait deja.
 */
it('refuse la publication d une fiche sans credit d image (carte de repli non detectee autrement)', function () {
    $source = aipManualSource();
    $paires = [[
        'statement' => 'Une affirmation editoriale.',
        'excerpt' => 'un extrait confirme a la source primaire',
        'type' => 'primary_fact',
        'source_url' => 'https://exemple.com/source-primaire',
    ]];

    $sansCredit = aipArticle($source->id, [
        'seo_title' => 'Un titre pour Google',
        'summary' => 'Un chapo complet.',
        'editorial_proof_pairs' => $paires,
    ]);

    $check = $sansCredit->publishReadinessCheck();

    expect($check['ready'])->toBeFalse()
        ->and($check['missing'])->toContain('image_credit');
});

it('laisse publier des que le credit d image est pose', function () {
    $source = aipManualSource();
    $paires = [[
        'statement' => 'Une affirmation editoriale.',
        'excerpt' => 'un extrait confirme a la source primaire',
        'type' => 'primary_fact',
        'source_url' => 'https://exemple.com/source-primaire',
    ]];

    $avecCredit = aipArticle($source->id, [
        'seo_title' => 'Un titre pour Google',
        'summary' => 'Un chapo complet.',
        'editorial_proof_pairs' => $paires,
        'image_credit' => 'Image : générée (Gemini)',
    ]);

    $check = $avecCredit->publishReadinessCheck();

    expect($check['ready'])->toBeTrue()
        ->and($check['missing'])->toBe([]);
});

/**
 * GARDE-FOU #2244, TROISIEME PORTE - trouvee par une revue adversariale de Codex (2026-09-04)
 * alors que j'avais ecrit, a tort, que publishReadinessCheck() couvrait « les deux seules portes ».
 * Mon relevé cherchait « is_published => true » en litteral et a rate les ecritures par variable.
 *
 * AdminNewsController::toggleArticle() (route PATCH admin/news/articles/{article}/toggle) appelait
 * publishAndPurgeSource() sans AUCUN controle. C'est un bouton d'administration bien reel : la
 * porte la plus plausible pour les deux recidives de fiches publiees sans vraie photo.
 */
it('la bascule rapide d administration refuse aussi de publier une fiche sans credit d image', function () {
    $source = aipManualSource();
    $article = aipArticle($source->id, [
        'seo_title' => 'Un titre pour Google',
        'summary' => 'Un chapo complet.',
        'editorial_proof_pairs' => [[
            'statement' => 'Une affirmation editoriale.',
            'excerpt' => 'un extrait confirme a la source primaire',
            'type' => 'primary_fact',
            'source_url' => 'https://exemple.com/source-primaire',
        ]],
    ]);

    // meme motif que smfAdmin() dans SourceMarkdownFetchPublishTest (le role doit exister)
    $admin = \App\Models\User::factory()->create();
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $admin->assignRole($role);

    $this->actingAs($admin)
        ->patch(route('admin.news.articles.toggle', $article))
        ->assertSessionHas('error');

    expect($article->fresh()->is_published)->toBeFalse();
});
