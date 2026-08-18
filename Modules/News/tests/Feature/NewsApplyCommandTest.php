<?php

declare(strict_types=1);

/**
 * Tests de la commande news:apply - SEULE porte d'écriture bornée pour l'agent Claude Code CLI
 * de l'écran de composition (design doc "Actus - composition manuelle assistée" 2026-08-15,
 * section "Révision 2026-08-17 - prompt d'orchestration Claude Code CLI"). Couvre : le refus sur
 * une fiche publiée, la liste blanche stricte des clés du payload, la double protection
 * anti-écrasement (empreinte + updated_at), l'application d'un payload valide, la fusion des
 * paires de preuve, et le dépôt d'image local (mêmes validations que le dépôt web).
 *
 * Ajout (note datée 2026-08-17, fin de journée - "l'agent publie lui-même via
 * news:apply --publish") : le mode --publish, seul autre endroit du code (avec
 * NewsCompositionController::publish()) autorisé à écrire is_published/published_at. Mêmes
 * prérequis que le bouton manuel, délégués à NewsArticle::publishReadinessCheck() - voir la
 * section "── Mode --publish ──" plus bas.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Nac pour éviter tout conflit inter-fichiers) ──────────────

function nacSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source news:apply',
        'url' => 'https://nac-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function nacArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();
    $source = nacSource();

    return NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => "Article news:apply {$i}",
        'guid' => "guid-nac-{$suffix}",
        'url' => "https://exemple.com/nac-{$suffix}",
        'description' => '',
        'summary' => "Résumé initial {$i}",
        'slug' => "article-nac-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

function nacPayloadFile(array $data): string
{
    $path = sys_get_temp_dir().'/nac-payload-'.uniqid().'.json';
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    return $path;
}

function nacFakeImageFile(string $tmpName, int $width, int $height): string
{
    $tmpPath = sys_get_temp_dir().'/'.$tmpName;
    $img = imagecreatetruecolor($width, $height);
    $color = imagecolorallocate($img, 11, 114, 133);
    imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, $color);
    imagejpeg($img, $tmpPath, 90);
    imagedestroy($img);

    return $tmpPath;
}

function nacFreshMeta(NewsArticle $article): array
{
    $article = $article->fresh();

    return [
        'expected_source_hash' => $article->source_content_hash,
        'expected_updated_at' => $article->updated_at?->toIso8601String(),
    ];
}

// ── Refus sur fiche publiée ──────────────────────────────────────────────────────────

it('refuses to apply a payload to an already-published article', function () {
    $article = nacArticle([
        'is_published' => true,
        'internal_source_text' => 'Texte source.',
        'source_content_hash' => hash('sha256', 'Texte source.'),
    ]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), ['seo_title' => 'Nouveau titre']));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->seo_title)->not->toBe('Nouveau titre');
});

it('refuses when the article does not exist', function () {
    $this->artisan('news:apply', ['article' => 999999, '--payload' => nacPayloadFile(['expected_source_hash' => 'x', 'expected_updated_at' => 'x'])])
        ->assertFailed();
});

it('refuses when neither --payload nor --image is given', function () {
    $article = nacArticle();

    $this->artisan('news:apply', ['article' => $article->id])->assertFailed();
});

// ── Liste blanche stricte des clés du payload ───────────────────────────────────────

it('refuses a payload containing a key outside the whitelist (e.g. is_published)', function () {
    $article = nacArticle(['internal_source_text' => 'Texte source.', 'source_content_hash' => hash('sha256', 'Texte source.')]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'seo_title' => 'Titre tenté',
        'is_published' => true,
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    $article->refresh();
    expect($article->is_published)->toBeFalse()
        ->and($article->seo_title)->not->toBe('Titre tenté');
});

it('refuses a payload containing published_at', function () {
    $article = nacArticle(['internal_source_text' => 'Texte source.', 'source_content_hash' => hash('sha256', 'Texte source.')]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), ['published_at' => now()->toIso8601String()]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();
});

// ── Double protection anti-écrasement ───────────────────────────────────────────────

it('refuses a payload with a stale expected_source_hash', function () {
    $article = nacArticle(['internal_source_text' => 'Texte source.', 'source_content_hash' => hash('sha256', 'Texte source.')]);
    $payload = nacPayloadFile([
        'expected_source_hash' => 'empreinte-perimee',
        'expected_updated_at' => $article->updated_at->toIso8601String(),
        'seo_title' => 'Nouveau titre',
    ]);

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->seo_title)->not->toBe('Nouveau titre');
});

it('refuses a payload with a stale expected_updated_at', function () {
    $article = nacArticle(['internal_source_text' => 'Texte source.', 'source_content_hash' => hash('sha256', 'Texte source.')]);
    $payload = nacPayloadFile([
        'expected_source_hash' => $article->source_content_hash,
        'expected_updated_at' => now()->subDays(3)->toIso8601String(),
        'seo_title' => 'Nouveau titre',
    ]);

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->seo_title)->not->toBe('Nouveau titre');
});

// ── Application d'un payload valide ─────────────────────────────────────────────────

it('applies a valid payload: seo_title, summary and editorial_proof_pairs, never touching is_published', function () {
    $article = nacArticle([
        'internal_source_text' => 'Le ministère a annoncé un budget de 12 millions de dollars.',
        'source_content_hash' => hash('sha256', 'Le ministère a annoncé un budget de 12 millions de dollars.'),
        'is_published' => false,
    ]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'seo_title' => 'MARQUEUR-TITRE-APPLIQUE',
        'summary' => 'MARQUEUR-RESUME-APPLIQUE',
        'editorial_proof_pairs' => [
            ['statement' => 'Le budget atteint 12 millions.', 'excerpt' => 'un budget de 12 millions de dollars', 'type' => 'fact'],
            ['statement' => 'Cette annonce est significative.', 'excerpt' => 'ne figure pas dans la source', 'type' => 'analysis'],
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $article->refresh();
    expect($article->seo_title)->toBe('MARQUEUR-TITRE-APPLIQUE')
        ->and($article->summary)->toBe('MARQUEUR-RESUME-APPLIQUE')
        ->and($article->editorial_proof_pairs)->toHaveCount(2)
        ->and($article->is_published)->toBeFalse();
});

it('refuses a payload whose "fact" excerpt is not an exact substring of the source text', function () {
    $article = nacArticle([
        'internal_source_text' => 'Le ministère a annoncé un budget de 12 millions de dollars.',
        'source_content_hash' => hash('sha256', 'Le ministère a annoncé un budget de 12 millions de dollars.'),
    ]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'editorial_proof_pairs' => [
            ['statement' => 'Le budget atteint 20 millions.', 'excerpt' => 'un budget de 20 millions de dollars', 'type' => 'fact'],
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->editorial_proof_pairs ?? [])->toBeEmpty();
});

it('merges new proof pairs with existing ones rather than replacing them', function () {
    $article = nacArticle([
        'internal_source_text' => 'Premier fait cité. Deuxième fait cité.',
        'source_content_hash' => hash('sha256', 'Premier fait cité. Deuxième fait cité.'),
        'editorial_proof_pairs' => [[
            'id' => 'existant-1',
            'statement' => 'Paire déjà présente.',
            'excerpt' => 'Premier fait cité',
            'type' => 'fact',
            'created_at' => now()->toIso8601String(),
        ]],
    ]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'editorial_proof_pairs' => [
            ['statement' => 'Nouvelle paire.', 'excerpt' => 'Deuxième fait cité', 'type' => 'fact'],
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->editorial_proof_pairs)->toHaveCount(2);
});

it('refuses a payload with an empty JSON object (no whitelisted content key)', function () {
    $article = nacArticle(['internal_source_text' => 'Texte source.', 'source_content_hash' => hash('sha256', 'Texte source.')]);
    $payload = nacPayloadFile(nacFreshMeta($article));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();
});

it('refuses a payload file that is not valid JSON', function () {
    $article = nacArticle();
    $path = sys_get_temp_dir().'/nac-invalid-'.uniqid().'.json';
    file_put_contents($path, 'ceci n\'est pas du JSON');

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $path])
        ->assertFailed();
});

// ── Addendum 2026-08-17 : structured_summary (résumé machine) effacé au profit de la
// composition manuelle - la fiche publique affiche structured_summary EN PRIORITÉ sur summary,
// donc il doit disparaître dès qu'un payload de contenu est appliqué (Modules\News\resources\
// views\public\show.blade.php, bloc @if($ss) ... @elseif($article->summary)). ─────────────────

it('applying a valid payload also clears structured_summary (machine summary), logging the old value first', function () {
    $logPath = storage_path('logs/composition-'.now()->format('Y-m-d').'.log');
    @unlink($logPath);

    $article = nacArticle([
        'internal_source_text' => 'Texte source pour la fiche.',
        'source_content_hash' => hash('sha256', 'Texte source pour la fiche.'),
        'structured_summary' => ['hook' => 'MARQUEUR-RESUME-MACHINE-A-EFFACER'],
    ]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'seo_title' => 'Titre composé',
        'summary' => 'Résumé composé.',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->structured_summary)->toBeNull();

    expect(file_exists($logPath))->toBeTrue();
    $content = file_get_contents($logPath);
    expect($content)->toContain('MARQUEUR-RESUME-MACHINE-A-EFFACER');

    @unlink($logPath);
});

it('applying a payload when structured_summary is already null does not error and stays null', function () {
    $article = nacArticle([
        'internal_source_text' => 'Texte source pour la fiche.',
        'source_content_hash' => hash('sha256', 'Texte source pour la fiche.'),
        'structured_summary' => null,
    ]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), ['seo_title' => 'Titre composé']));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->structured_summary)->toBeNull();
});

// ── Dépôt d'image local (--image) ───────────────────────────────────────────────────

it('applies a valid local image: produces the 1200x630 social JPEG and a WebP variant', function () {
    Storage::fake('public');
    $article = nacArticle();
    $imagePath = nacFakeImageFile('nac-valide.jpg', 1600, 900);

    $this->artisan('news:apply', ['article' => $article->id, '--image' => $imagePath])
        ->assertSuccessful();

    Storage::disk('public')->assertExists("news/images/{$article->id}.jpg");
    Storage::disk('public')->assertExists("news/images/{$article->id}.webp");

    [$w, $h] = getimagesizefromstring(Storage::disk('public')->get("news/images/{$article->id}.jpg"));
    expect($w)->toBe(1200)->and($h)->toBe(630);
});

it('refuses a local image below the minimum dimensions', function () {
    Storage::fake('public');
    $article = nacArticle();
    $imagePath = nacFakeImageFile('nac-trop-petite.jpg', 100, 60);

    $this->artisan('news:apply', ['article' => $article->id, '--image' => $imagePath])
        ->assertFailed();

    Storage::disk('public')->assertMissing("news/images/{$article->id}.jpg");
});

it('refuses a local image file whose real content is not an image, despite a .jpg extension', function () {
    Storage::fake('public');
    $article = nacArticle();
    $path = sys_get_temp_dir().'/nac-pas-une-image.jpg';
    file_put_contents($path, str_repeat('ceci n\'est pas une image. ', 50));

    $this->artisan('news:apply', ['article' => $article->id, '--image' => $path])
        ->assertFailed();

    Storage::disk('public')->assertMissing("news/images/{$article->id}.jpg");
});

it('refuses to apply an image to an already-published article', function () {
    Storage::fake('public');
    $article = nacArticle(['is_published' => true]);
    $imagePath = nacFakeImageFile('nac-publiee.jpg', 1600, 900);

    $this->artisan('news:apply', ['article' => $article->id, '--image' => $imagePath])
        ->assertFailed();

    Storage::disk('public')->assertMissing("news/images/{$article->id}.jpg");
});

// ── Journalisation (canal dédié 'composition') ──────────────────────────────────────

it('applying a valid payload writes to the dedicated composition log file', function () {
    $logPath = storage_path('logs/composition-'.now()->format('Y-m-d').'.log');
    @unlink($logPath);

    $article = nacArticle([
        'internal_source_text' => 'Texte source pour vérifier la journalisation.',
        'source_content_hash' => hash('sha256', 'Texte source pour vérifier la journalisation.'),
    ]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), ['seo_title' => 'Titre journalisé']));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect(file_exists($logPath))->toBeTrue();
    $content = file_get_contents($logPath);
    expect($content)->toContain((string) $article->id);

    @unlink($logPath);
});

// ── Mode --publish (note datée 2026-08-17, fin de journée) ─────────────────────────

it('applies --publish: article published, source text purged, public link in the output, provenance/pairs survive', function () {
    $sourceText = 'Le ministère a confirmé un investissement de 12 millions de dollars pour ce projet.';
    $article = nacArticle([
        'seo_title' => 'Titre publié prêt',
        'summary' => 'Résumé publié prêt.',
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'source_captured_at' => now(),
        'editorial_proof_pairs' => [[
            'id' => 'pair-1',
            'statement' => 'Le ministère investit 12 millions.',
            'excerpt' => 'un investissement de 12 millions de dollars',
            'type' => 'fact',
            'created_at' => now()->toIso8601String(),
        ]],
        'is_published' => false,
    ]);

    $this->artisan('news:apply', ['article' => $article->id, '--publish' => true])
        ->assertSuccessful()
        ->expectsOutputToContain(url('/actualites/'.$article->slug));

    $article->refresh();
    expect($article->is_published)->toBeTrue()
        ->and($article->published_at)->not->toBeNull()
        ->and($article->internal_source_text)->toBeNull()
        // Provenance et paires SURVIVENT à la purge - même garde-fou que le bouton manuel
        // (NewsCompositionController::publish(), voir SourceMarkdownFetchPublishTest.php).
        ->and($article->source_content_hash)->toBe(hash('sha256', $sourceText))
        ->and($article->source_captured_at)->not->toBeNull()
        ->and($article->editorial_proof_pairs)->toHaveCount(1);
});

it('refuses --publish when prerequisites are missing (seo_title/summary/editorial_proof_pairs), nothing published', function () {
    $article = nacArticle([
        'seo_title' => null,
        'summary' => null,
        'editorial_proof_pairs' => [],
        'is_published' => false,
    ]);

    $this->artisan('news:apply', ['article' => $article->id, '--publish' => true])
        ->assertFailed();

    expect($article->fresh()->is_published)->toBeFalse();
});

it('refuses --publish when a "fact" pair is no longer an exact substring of the current source text - nothing published, nothing purged', function () {
    $article = nacArticle([
        'seo_title' => 'Titre publié prêt',
        'summary' => 'Résumé publié prêt.',
        'internal_source_text' => 'Le texte source a changé depuis la création de la paire de preuve.',
        'editorial_proof_pairs' => [[
            'id' => 'pair-1',
            'statement' => 'Une affirmation appuyée par une citation.',
            'excerpt' => 'un extrait qui ne figure plus dans le texte source actuel',
            'type' => 'fact',
            'created_at' => now()->toIso8601String(),
        ]],
        'is_published' => false,
    ]);

    $this->artisan('news:apply', ['article' => $article->id, '--publish' => true])
        ->assertFailed();

    $article->refresh();
    expect($article->is_published)->toBeFalse()
        ->and($article->internal_source_text)->not->toBeNull()
        ->and($article->editorial_proof_pairs)->toHaveCount(1);
});

it('refuses --publish on an already-published article', function () {
    $article = nacArticle([
        'seo_title' => 'Déjà en ligne',
        'summary' => 'Déjà en ligne.',
        'editorial_proof_pairs' => [['id' => 'p1', 'statement' => 's', 'excerpt' => 'e', 'type' => 'analysis', 'created_at' => now()->toIso8601String()]],
        'is_published' => true,
        'published_at' => now()->subDay(),
    ]);

    $this->artisan('news:apply', ['article' => $article->id, '--publish' => true])
        ->assertFailed();
});

// ── Bonification panel 2026-08-17 (soir) : 3e type de paire "primary_fact" ─────────

it('applies a "primary_fact" pair with a valid source_url, without revalidating its excerpt as a substring', function () {
    $article = nacArticle([
        'internal_source_text' => 'Un texte source secondaire qui ne contient pas la citation exacte.',
        'source_content_hash' => hash('sha256', 'Un texte source secondaire qui ne contient pas la citation exacte.'),
    ]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'editorial_proof_pairs' => [
            [
                'statement' => 'Le ministre a confirmé la mesure.',
                'excerpt' => 'citation exacte tirée du communiqué original, absente du texte collé',
                'type' => 'primary_fact',
                'source_url' => 'https://exemple-officiel.com/communique',
            ],
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $pairs = $article->fresh()->editorial_proof_pairs;
    expect($pairs)->toHaveCount(1)
        ->and($pairs[0]['type'])->toBe('primary_fact')
        ->and($pairs[0]['source_url'])->toBe('https://exemple-officiel.com/communique');
});

it('refuses a "primary_fact" pair without a source_url', function () {
    $article = nacArticle([
        'internal_source_text' => 'Texte source.',
        'source_content_hash' => hash('sha256', 'Texte source.'),
    ]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'editorial_proof_pairs' => [
            ['statement' => 'Affirmation.', 'excerpt' => 'Citation originale.', 'type' => 'primary_fact'],
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->editorial_proof_pairs ?? [])->toBeEmpty();
});

it('refuses a "primary_fact" pair whose source_url is not a valid http/https URL', function () {
    $article = nacArticle([
        'internal_source_text' => 'Texte source.',
        'source_content_hash' => hash('sha256', 'Texte source.'),
    ]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'editorial_proof_pairs' => [
            ['statement' => 'Affirmation.', 'excerpt' => 'Citation originale.', 'type' => 'primary_fact', 'source_url' => 'ceci-n-est-pas-une-url'],
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->editorial_proof_pairs ?? [])->toBeEmpty();
});

// ── Bonification panel 2026-08-17 (soir) : primary_sources / image_credit ──────────

it('applies primary_sources via payload, persisted as label/url/note', function () {
    $article = nacArticle();
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'primary_sources' => [
            ['label' => 'Communiqué officiel', 'url' => 'https://exemple-officiel.com/communique', 'note' => 'Source du chiffre cité'],
            ['label' => 'Rapport PDF', 'url' => 'https://exemple-officiel.com/rapport.pdf'],
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $sources = $article->fresh()->primary_sources;
    expect($sources)->toHaveCount(2)
        ->and($sources[0]['label'])->toBe('Communiqué officiel')
        ->and($sources[0]['url'])->toBe('https://exemple-officiel.com/communique')
        ->and($sources[0]['note'])->toBe('Source du chiffre cité')
        ->and($sources[1]['note'])->toBeNull();
});

it('refuses primary_sources containing an invalid URL, persisting nothing', function () {
    $article = nacArticle();
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'primary_sources' => [
            ['label' => 'Source douteuse', 'url' => 'pas-une-url-valide'],
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->primary_sources ?? [])->toBeEmpty();
});

it('applies image_credit via payload, persisted', function () {
    $article = nacArticle();
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'image_credit' => 'Photo : Untel, Unsplash',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->image_credit)->toBe('Photo : Untel, Unsplash');
});

// ── Bonification panel 2026-08-17 (soir) : primary_sources SURVIT à la publication-purge,
// même garde-fou que editorial_proof_pairs (voir le test --publish ci-dessus). ─────────────

it('applying --publish preserves primary_sources across the publish-and-purge transaction', function () {
    $sourceText = 'Le ministère a confirmé un investissement de 12 millions de dollars pour ce projet.';
    $article = nacArticle([
        'seo_title' => 'Titre publié prêt',
        'summary' => 'Résumé publié prêt.',
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'primary_sources' => [
            ['label' => 'Communiqué officiel', 'url' => 'https://exemple-officiel.com/communique', 'note' => null],
        ],
        'image_credit' => 'Photo : Untel, Unsplash',
        'editorial_proof_pairs' => [[
            'id' => 'pair-1',
            'statement' => 'Le ministère investit 12 millions.',
            'excerpt' => 'un investissement de 12 millions de dollars',
            'type' => 'fact',
            'created_at' => now()->toIso8601String(),
        ]],
        'is_published' => false,
    ]);

    $this->artisan('news:apply', ['article' => $article->id, '--publish' => true])
        ->assertSuccessful();

    $article->refresh();
    expect($article->is_published)->toBeTrue()
        ->and($article->internal_source_text)->toBeNull()
        ->and($article->primary_sources)->toHaveCount(1)
        ->and($article->primary_sources[0]['url'])->toBe('https://exemple-officiel.com/communique')
        ->and($article->image_credit)->toBe('Photo : Untel, Unsplash');
});

it('applying --publish writes to the dedicated composition log file', function () {
    $logPath = storage_path('logs/composition-'.now()->format('Y-m-d').'.log');
    @unlink($logPath);

    $sourceText = 'Texte source pour vérifier la journalisation de la publication.';
    $article = nacArticle([
        'seo_title' => 'Titre journalisé publié',
        'summary' => 'Résumé journalisé publié.',
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'editorial_proof_pairs' => [['id' => 'p1', 'statement' => 's', 'excerpt' => 'e', 'type' => 'analysis', 'created_at' => now()->toIso8601String()]],
        'is_published' => false,
    ]);

    $this->artisan('news:apply', ['article' => $article->id, '--publish' => true])
        ->assertSuccessful();

    expect(file_exists($logPath))->toBeTrue();
    $content = file_get_contents($logPath);
    expect($content)->toContain((string) $article->id);

    @unlink($logPath);
});

// ── Clé related_tool_slugs (intégration « Outils liés », 2026-08-17 soir) ──────────────

function nacTool(string $slug): \Modules\Directory\Models\Tool
{
    $name = 'Outil nac '.$slug;

    // Tableau associatif (PAS json_encode) pour que Spatie appelle setTranslations() correctement.
    return \Modules\Directory\Models\Tool::withoutEvents(fn () => \Modules\Directory\Models\Tool::create([
        'name' => ['fr_CA' => $name, 'en' => $name],
        'slug' => ['fr_CA' => $slug, 'en' => $slug],
        'status' => 'published',
        'pricing' => 'free',
    ]));
}

it('related_tool_slugs attache les outils publiés, signale les slugs inconnus et préserve les liaisons manuelles', function () {
    $sourceText = 'Texte source pour la curation des outils liés.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'structured_summary' => ['composed' => true, 'hook' => 'Accroche composée conservée.'],
    ]);

    $known = nacTool('outil-nac-connu');
    $manual = nacTool('outil-nac-manuel');
    $article->tools()->attach($manual->id, ['source' => 'manual']);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'related_tool_slugs' => ['outil-nac-connu', 'slug-inconnu-xyz'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->expectsOutputToContain('slug-inconnu-xyz')
        ->assertSuccessful();

    $article = $article->fresh();
    $pivots = $article->tools()->get()->keyBy('id');

    expect($pivots)->toHaveCount(2)
        ->and($pivots[$known->id]->pivot->source)->toBe('auto')
        ->and($pivots[$manual->id]->pivot->source)->toBe('manual')
        // Un payload outils-seulement ne doit JAMAIS effacer le résumé composé.
        ->and($article->structured_summary['hook'] ?? null)->toBe('Accroche composée conservée.');
});

it('related_tool_slugs non-tableau est refusé sans rien lier', function () {
    $sourceText = 'Texte source refus outils.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'related_tool_slugs' => 'pas-un-tableau',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->tools()->count())->toBe(0);
});

// ── Clé title (correctif systémique : titre + slug par la porte, 2026-08-17 soir) ──────

it('la clé title applique le titre et régénère le slug par la méthode canonique', function () {
    $sourceText = 'Texte source pour le titre.';
    $article = nacArticle([
        'title' => 'Fiche créée depuis un lien - à composer',
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'title' => 'Un vrai titre décidé par le cycle',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $article = $article->fresh();
    expect($article->title)->toBe('Un vrai titre décidé par le cycle')
        ->and($article->slug)->toStartWith('un-vrai-titre-decide-par-le-cycle');
});

it('un title vide ou trop long est refusé sans écriture', function () {
    $sourceText = 'Texte source refus titre.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);
    $ancienSlug = $article->slug;

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), ['title' => '   ']));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->slug)->toBe($ancienSlug);
});

// ── Clé entities (connexes par entités partagées, 2026-08-18) ──────────────────────────

it('la clé entities enregistre les entités normalisées et remplace les précédentes', function () {
    $sourceText = 'Texte source pour les entités.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);
    $article->syncEntities(['Ancienne Entité']);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'entities' => ['Université d\'Arizona', 'ChatGPT', 'ChatGPT'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $slugs = $article->fresh()->entities()->pluck('entity_slug')->sort()->values()->all();
    expect($slugs)->toBe(['chatgpt', 'universite-darizona']);
});

it('entities non-tableau ou trop nombreux est refusé sans écriture', function () {
    $sourceText = 'Texte source refus entités.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), ['entities' => 'pas-un-tableau']));
    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])->assertFailed();

    $payload2 = nacPayloadFile(array_merge(nacFreshMeta($article), ['entities' => array_fill(0, 11, 'Entité')]));
    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload2])->assertFailed();

    expect($article->fresh()->entities()->count())->toBe(0);
});

it('les articles connexes priorisent les entités partagées puis complètent par la catégorie', function () {
    $a = nacArticle(['is_published' => true, 'category_tag' => 'ia-generative']);
    // Réutiliser la même source (nacSource crée une URL fixe, unique en base).
    $clone = function (array $overrides) use ($a) {
        static $i = 0;
        $i++;
        $suffix = 'rel-'.$i.'-'.uniqid();

        return \Modules\News\Models\NewsArticle::create(array_merge([
            'news_source_id' => $a->news_source_id,
            'title' => "Article connexe {$suffix}",
            'guid' => "guid-{$suffix}",
            'url' => "https://exemple.com/{$suffix}",
            'description' => '',
            'summary' => 'Résumé connexe.',
            'slug' => "article-{$suffix}",
            'pub_date' => now()->subDay(),
            'is_published' => true,
            'seo_status' => 'index',
        ], $overrides));
    };
    $memeEntites = $clone(['category_tag' => 'autre-categorie', 'pub_date' => now()->subDays(9)]);
    $memeCategorie = $clone(['category_tag' => 'ia-generative', 'pub_date' => now()->subDays(2)]);
    $horsTout = $clone(['category_tag' => 'zzz', 'pub_date' => now()]);

    $a->syncEntities(['Anthropic', 'Claude Code']);
    $memeEntites->syncEntities(['Anthropic', 'Claude Code']);
    $horsTout->syncEntities(['Mistral']);

    $related = \Modules\News\Models\NewsArticle::relatedFor($a->fresh(), 3);

    expect($related->first()->id)->toBe($memeEntites->id)
        ->and($related->pluck('id')->all())->toContain($memeCategorie->id)
        ->and($related->pluck('id')->all())->not->toContain($horsTout->id);
});
