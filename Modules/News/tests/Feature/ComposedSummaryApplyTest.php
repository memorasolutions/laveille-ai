<?php

declare(strict_types=1);

/**
 * Richesse v1.188.0 - structure fixe composée (panel de 5 IA, 2026-08-17 soir, design doc "Actus
 * - composition manuelle assistée" 2026-08-15, section "Richesse v1.188.0"). Couvre la nouvelle
 * clé de payload `composed_summary` de Modules\News\Console\NewsApplyCommand : liste blanche
 * stricte des huit sous-clés (hook, key_points, why_important, key_number, quote, angle_qc_ca,
 * action_concrete, reperes_dates), bornes de longueur, et le comportement de REMPLACEMENT de
 * structured_summary (au lieu de l'effacement à null appliqué aux autres clés de contenu de ce
 * mode) - marqueur `composed: true` écrit par NewsArticle::hasComposedSummary().
 *
 * Fichier dédié, distinct de NewsApplyCommandTest.php (couverture générique de la commande, hors
 * périmètre ici) - helpers locaux préfixés `cs` (Composed Summary), autonomes.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Spatie\ResponseCache\Facades\ResponseCache;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Cs pour éviter tout conflit inter-fichiers) ────────────────

function csSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source composed_summary',
        'url' => 'https://cs-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function csArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();
    $source = csSource();

    return NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => "Article composed_summary {$i}",
        'guid' => "guid-cs-{$suffix}",
        'url' => "https://exemple.com/cs-{$suffix}",
        'description' => '',
        'summary' => "Résumé initial {$i}",
        'slug' => "article-cs-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => false,
        'seo_status' => 'index',
        'internal_source_text' => 'Texte source de test.',
        'source_content_hash' => hash('sha256', 'Texte source de test.'),
    ], $overrides));
}

function csPayloadFile(array $data): string
{
    $path = sys_get_temp_dir().'/cs-payload-'.uniqid().'.json';
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $path;
}

function csFreshMeta(NewsArticle $article): array
{
    $article = $article->fresh();

    return [
        'expected_source_hash' => $article->source_content_hash,
        'expected_updated_at' => $article->updated_at?->toIso8601String(),
    ];
}

function csValidComposedSummary(): array
{
    return [
        'hook' => 'Une accroche de test autonome.',
        'key_points' => ['Premier point clé attribué.', 'Deuxième point clé attribué.'],
        'why_important' => 'Cette annonce compte parce que le test le dit.',
        'key_number' => '12 millions de dollars, annoncés le 2026-08-17 (ministère).',
        'quote' => ['text' => 'Ceci est une citation de test.', 'author' => 'Jeanne Tremblay, porte-parole'],
        'angle_qc_ca' => 'Au Québec, ce test change quelque chose.',
        'action_concrete' => 'Consultez le site officiel. Inscrivez-vous avant vendredi.',
        'reperes_dates' => [
            ['date' => '2026-06-01', 'texte' => 'Premier jalon.'],
            ['date' => '2026-08-17', 'texte' => 'Deuxième jalon.', 'url' => 'https://exemple.com/jalon'],
        ],
    ];
}

// ── Application valide : REMPLACE structured_summary (jamais un effacement à null) ─────

it('applies a valid composed_summary payload: stored in structured_summary with the composed:true marker', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => csValidComposedSummary(),
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $article->refresh();
    expect($article->structured_summary)->not->toBeNull()
        ->and($article->structured_summary['composed'])->toBeTrue()
        ->and($article->structured_summary['hook'])->toBe('Une accroche de test autonome.')
        ->and($article->structured_summary['key_points'])->toHaveCount(2)
        ->and($article->structured_summary['why_important'])->toBe('Cette annonce compte parce que le test le dit.')
        ->and($article->structured_summary['key_number'])->toBe('12 millions de dollars, annoncés le 2026-08-17 (ministère).')
        ->and($article->structured_summary['quote'])->toBe(['text' => 'Ceci est une citation de test.', 'author' => 'Jeanne Tremblay, porte-parole'])
        ->and($article->structured_summary['angle_qc_ca'])->toBe('Au Québec, ce test change quelque chose.')
        ->and($article->structured_summary['action_concrete'])->toBe('Consultez le site officiel. Inscrivez-vous avant vendredi.')
        ->and($article->structured_summary['reperes_dates'])->toHaveCount(2)
        ->and($article->is_published)->toBeFalse();
});

it('composed_summary REPLACES structured_summary rather than erasing it to null, unlike the other content keys of this mode', function () {
    $article = csArticle([
        'structured_summary' => ['hook' => 'MARQUEUR-RESUME-MACHINE-A-REMPLACER'],
    ]);
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['hook' => 'MARQUEUR-RESUME-COMPOSE'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $article->refresh();
    expect($article->structured_summary)->not->toBeNull()
        ->and($article->structured_summary['hook'])->toBe('MARQUEUR-RESUME-COMPOSE')
        ->and($article->structured_summary['composed'])->toBeTrue();
});

it('logs the previous machine structured_summary value before replacing it with the composed one', function () {
    $logPath = storage_path('logs/composition-'.now()->format('Y-m-d').'.log');
    @unlink($logPath);

    $article = csArticle([
        'structured_summary' => ['hook' => 'MARQUEUR-ANCIEN-RESUME-MACHINE'],
    ]);
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['hook' => 'Nouveau résumé composé.'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect(file_exists($logPath))->toBeTrue();
    $content = file_get_contents($logPath);
    expect($content)->toContain('MARQUEUR-ANCIEN-RESUME-MACHINE');

    @unlink($logPath);
});

it('refuses to apply composed_summary to an already-published article', function () {
    $article = csArticle(['is_published' => true]);
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => csValidComposedSummary(),
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->structured_summary)->toBeNull();
});

// ── Liste blanche stricte des sous-clés ─────────────────────────────────────────────

it('refuses a composed_summary containing an unknown sub-key', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['hook' => 'Test.', 'sous_titre' => 'Clé non autorisée.'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->structured_summary)->toBeNull();
});

it('refuses a composed_summary that is not an object', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => 'ceci devrait être un objet',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();
});

// ── Bornes de longueur ───────────────────────────────────────────────────────────────

it('refuses a composed_summary.hook exceeding 600 characters', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['hook' => str_repeat('a', 601)],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->structured_summary)->toBeNull();
});

it('accepts a composed_summary.hook of exactly 600 characters', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['hook' => str_repeat('a', 600)],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();
});

it('refuses composed_summary.key_points exceeding 5 items', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['key_points' => ['a', 'b', 'c', 'd', 'e', 'f']],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();
});

it('refuses a composed_summary.key_points item exceeding 300 characters', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['key_points' => [str_repeat('a', 301)]],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();
});

it('refuses composed_summary.quote without a text', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['quote' => ['author' => 'Untel']],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();
});

it('refuses a composed_summary.quote containing an unknown sub-key', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['quote' => ['text' => 'Citation.', 'source' => 'https://exemple.com']],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();
});

it('refuses a composed_summary.quote.text exceeding 400 characters', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['quote' => ['text' => str_repeat('a', 401)]],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();
});

it('refuses a composed_summary.quote.author exceeding 120 characters', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['quote' => ['text' => 'Citation valide.', 'author' => str_repeat('a', 121)]],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();
});

it('accepts a composed_summary.quote without an author (facultatif)', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['quote' => ['text' => 'Citation sans auteur.']],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->structured_summary['quote'])->toBe(['text' => 'Citation sans auteur.']);
});

it('refuses composed_summary.reperes_dates exceeding 4 items', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['reperes_dates' => [
            ['date' => '2026-01-01', 'texte' => 'a'],
            ['date' => '2026-02-01', 'texte' => 'b'],
            ['date' => '2026-03-01', 'texte' => 'c'],
            ['date' => '2026-04-01', 'texte' => 'd'],
            ['date' => '2026-05-01', 'texte' => 'e'],
        ]],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();
});

it('refuses a composed_summary.reperes_dates entry missing date or texte', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['reperes_dates' => [['date' => '2026-01-01']]],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();
});

it('refuses a composed_summary.reperes_dates entry with an invalid url', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['reperes_dates' => [['date' => '2026-01-01', 'texte' => 'Jalon.', 'url' => 'pas-une-url']]],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();
});

it('accepts a composed_summary.reperes_dates entry without url (facultative)', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'composed_summary' => ['reperes_dates' => [['date' => '2026-01-01', 'texte' => 'Jalon sans lien.']]],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->structured_summary['reperes_dates'][0])
        ->toBe(['date' => '2026-01-01', 'texte' => 'Jalon sans lien.']);
});

// ── composed_summary vide/absent : n'est jamais forcé, cohabite avec les autres clés ──

it('applies composed_summary alongside seo_title/summary in the same payload without conflict', function () {
    $article = csArticle();
    $payload = csPayloadFile(array_merge(csFreshMeta($article), [
        'seo_title' => 'Titre composé de test',
        'summary' => 'Résumé court composé de test.',
        'composed_summary' => ['hook' => 'Accroche.'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $article->refresh();
    expect($article->seo_title)->toBe('Titre composé de test')
        ->and($article->summary)->toBe('Résumé court composé de test.')
        ->and($article->structured_summary['composed'])->toBeTrue();
});

// ── Second payload PARTIEL : la composition survit (defaut trouve en production le 2026-08-26) ──
//
// Trouve en corrigeant le titre d'une fiche deja composee : le payload correctif ne portait que
// `title` + `seo_title`, et la commande a repondu « payload texte applique (title, slug,
// seo_title, structured_summary) ». L'effacement inconditionnel de structured_summary, correct
// pour le resume MACHINE de la collecte, detruisait la composition riche ecrite juste avant.
//
// Le meme garde-fou existait deja dans NewsCompositionController::publish() - il n'avait jamais
// ete porte dans la porte de l'agent. hasComposedSummary() reste le point UNIQUE de la
// distinction machine/compose (DRY) : ce test verrouille les DEUX cotes.

it('preserves a composed structured_summary when a SECOND partial payload only fixes the title', function () {
    $article = csArticle();

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => csPayloadFile(
        array_merge(csFreshMeta($article), ['composed_summary' => csValidComposedSummary()])
    )])->assertSuccessful();

    $article->refresh();
    expect($article->hasComposedSummary())->toBeTrue('Prerequis : la fiche doit porter une composition.');

    // Second passage : uniquement une correction de titre, aucune cle composed_summary.
    $this->artisan('news:apply', ['article' => $article->id, '--payload' => csPayloadFile(
        array_merge(csFreshMeta($article), [
            'title' => 'Titre corrige apres revision adversariale',
            'seo_title' => 'Titre corrige',
        ])
    )])->assertSuccessful();

    $article->refresh();
    expect($article->structured_summary)->not->toBeNull(
        'Un payload partiel ne doit JAMAIS detruire la composition riche deja appliquee.'
    );
    expect($article->structured_summary['hook'])->toBe('Une accroche de test autonome.');
    expect($article->structured_summary['key_points'])->toHaveCount(2);
    expect($article->hasComposedSummary())->toBeTrue();
    expect($article->title)->toBe('Titre corrige apres revision adversariale');
});

// Non-regression du comportement d'ORIGINE : un resume MACHINE, lui, doit toujours etre efface
// par un payload de contenu - sinon il continuerait de primer sur la composition a l'affichage.
it('still erases a MACHINE structured_summary when a content payload carries no composed_summary', function () {
    $article = csArticle([
        'structured_summary' => ['hook' => 'RESUME-MACHINE-QUI-DOIT-DISPARAITRE'],
    ]);

    expect($article->hasComposedSummary())->toBeFalse('Prerequis : resume machine, sans marqueur composed.');

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => csPayloadFile(
        array_merge(csFreshMeta($article), ['summary' => 'Un chapo compose a la main.'])
    )])->assertSuccessful();

    $article->refresh();
    expect($article->structured_summary)->toBeNull(
        'Le resume machine prime sinon sur la composition cote page publique.'
    );
});

// ── Purge du cache apres une ecriture sur une fiche PUBLIEE (mesure du 2026-08-26) ──────
//
// --enrich existe pour corriger une fiche deja publiee. Une correction typographique appliquee
// avec succes ne paraissait pourtant pas sur le site : la page etait servie depuis le cache de
// reponse Spatie, dont la duree de vie est de sept jours. La commande ecrivait en base sans
// jamais invalider la page correspondante.
//
// La purge est CIBLEE sur l'URL de la fiche (NewsToolSyncAction::invalidatePublicCache, deja
// employe par le chemin related_tool_slugs) - jamais un clear() global qui viderait tout le site
// et renverrait chaque page en rendu a froid.

it('purge le cache de la page publique apres un --enrich sur une fiche publiee', function () {
    $article = csArticle(['is_published' => true, 'published_at' => now()]);

    // Double chainable : on eprouve la CHAINE COMPLETE (forUrls -> usingSuffix -> forget),
    // pas seulement le fait que selectCachedItems ait ete appele. Un simple spy laisserait
    // passer une purge construite puis jamais executee.
    // Le double porte la VRAIE classe : selectCachedItems() declare CacheItemSelector en
    // type de retour, un mock anonyme serait refuse par PHP avant meme d'atteindre l'assertion.
    $selecteur = Mockery::mock(\Spatie\ResponseCache\CacheItemSelector\CacheItemSelector::class);
    $selecteur->shouldReceive('forUrls')->once()->andReturnSelf();
    $selecteur->shouldReceive('usingSuffix')->once()->andReturnSelf();
    $selecteur->shouldReceive('forget')->once();
    ResponseCache::shouldReceive('selectCachedItems')->once()->andReturn($selecteur);

    $this->artisan('news:apply', [
        'article' => $article->id,
        '--payload' => csPayloadFile(array_merge(csFreshMeta($article), [
            'composed_summary' => ['hook' => 'Correction typographique appliquee apres publication.'],
        ])),
        '--enrich' => true,
    ])->assertSuccessful();
});

// Non-regression : sur un BROUILLON, il n'y a aucune page publique en cache a invalider.
// Purger la serait au mieux inutile, et masquerait le fait que la garde porte bien sur l'etat publie.
it('ne purge rien quand la fiche est encore un brouillon', function () {
    $article = csArticle();

    ResponseCache::spy();

    $this->artisan('news:apply', [
        'article' => $article->id,
        '--payload' => csPayloadFile(array_merge(csFreshMeta($article), [
            'composed_summary' => csValidComposedSummary(),
        ])),
    ])->assertSuccessful();

    ResponseCache::shouldNotHaveReceived('selectCachedItems');
});
