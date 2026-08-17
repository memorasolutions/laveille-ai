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
