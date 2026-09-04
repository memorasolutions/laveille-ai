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

// Groupe "smoke" (ticket #2095) : sas rapide et bloquant avant déploiement. Ce fichier couvre la
// SEULE porte d'écriture bornée de la composition d'actualités (voir docblock ci-dessus) - liste
// blanche du payload, double protection anti-écrasement, mode --publish : exactement la zone où
// deux régressions réelles ont atteint la production le 2026-08-26 et le 2026-08-28 (résumé riche
// effacé par un payload partiel).
uses(Tests\TestCase::class, RefreshDatabase::class)->group('smoke');

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

// ── Correctif todo #1984 (2026-08-28) : validation PAR PAIRE INDÉPENDANTE - avant ce correctif,
// normalizeProofPairs() arrêtait sa boucle et rejetait TOUT le tableau dès la première paire
// invalide (mesuré : 2 paires invalides sur 15 soumises faisaient échouer les 15). Chaque paire
// est désormais acceptée ou refusée pour elle-même. ────────────────────────────────────────────

it('applies the valid pairs of a batch and rejects only the invalid ones, instead of failing the whole batch (todo #1984)', function () {
    $sourceText = 'Le ministère a confirmé un investissement de 12 millions de dollars pour ce projet.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'editorial_proof_pairs' => [
            ['statement' => 'MARQUEUR-VALIDE-UN', 'excerpt' => 'un investissement de 12 millions de dollars', 'type' => 'fact'],
            ['statement' => 'MARQUEUR-INVALIDE', 'excerpt' => 'ceci ne figure nulle part dans la source', 'type' => 'fact'],
            ['statement' => 'MARQUEUR-VALIDE-DEUX', 'excerpt' => 'Le projet est ambitieux.', 'type' => 'analysis'],
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful()
        ->expectsOutputToContain('MARQUEUR-INVALIDE');

    $pairs = $article->fresh()->editorial_proof_pairs;
    $statements = array_column($pairs, 'statement');
    expect($pairs)->toHaveCount(2)
        ->and($statements)->toContain('MARQUEUR-VALIDE-UN')
        ->and($statements)->toContain('MARQUEUR-VALIDE-DEUX')
        ->and($statements)->not->toContain('MARQUEUR-INVALIDE');
});

it('still fails the whole command when EVERY pair of the batch is invalid - nothing to apply, nothing is written (zero regression)', function () {
    $sourceText = 'Texte source qui ne contient aucun des extraits soumis ci-dessous.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'editorial_proof_pairs' => [
            ['statement' => 'Premier.', 'excerpt' => 'extrait absent numéro un', 'type' => 'fact'],
            ['statement' => 'Deuxième.', 'excerpt' => 'extrait absent numéro deux', 'type' => 'fact'],
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->editorial_proof_pairs ?? [])->toBeEmpty();
});

// ── Retrait explicite d'une paire de preuve (mandat 2026-08-28 : une donnée de santé sur une
// personne nommée s'est retrouvée publiée dans une paire, sans AUCUN mécanisme pour la retirer -
// editorial_proof_pairs n'acceptait que l'ajout, refusait null (échec is_array) et un tableau
// vide ne faisait rien). Convention reprise TELLE QUELLE de fact_check plus bas : la clé
// PRÉSENTE avec la valeur `null` retire ; la clé ABSENTE du payload ne touche à rien. Les trois
// intentions (absent / remplace / retire) doivent être distinguables sans ambiguïté - c'est le
// coeur du correctif, prouvé par les deux tests suivants. ──────────────────────────────────────

it('editorial_proof_pairs à null retire explicitement TOUTES les paires existantes (mécanisme de retrait)', function () {
    $article = nacArticle([
        'internal_source_text' => 'Texte source pour le retrait de paire.',
        'source_content_hash' => hash('sha256', 'Texte source pour le retrait de paire.'),
        'editorial_proof_pairs' => [
            [
                'id' => 'pair-sensible',
                'statement' => 'MARQUEUR-DONNEE-SENSIBLE-A-RETIRER',
                'excerpt' => 'extrait sensible',
                'type' => 'analysis',
                'created_at' => now()->toIso8601String(),
            ],
            [
                'id' => 'pair-legitime',
                'statement' => 'Une paire légitime, sans lien avec le retrait.',
                'excerpt' => 'extrait légitime',
                'type' => 'analysis',
                'created_at' => now()->toIso8601String(),
            ],
        ],
    ]);
    expect($article->editorial_proof_pairs)->toHaveCount(2);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'editorial_proof_pairs' => null,
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->editorial_proof_pairs)->toBe([]);
});

it('un payload sans la clé editorial_proof_pairs laisse les paires existantes rigoureusement intactes (absent ne touche à rien)', function () {
    $pairesInitiales = [[
        'id' => 'pair-intacte',
        'statement' => 'Cette paire ne doit jamais bouger.',
        'excerpt' => 'extrait intact',
        'type' => 'analysis',
        'created_at' => now()->toIso8601String(),
    ]];
    $article = nacArticle([
        'internal_source_text' => 'Texte source pour la non-régression du retrait.',
        'source_content_hash' => hash('sha256', 'Texte source pour la non-régression du retrait.'),
        'editorial_proof_pairs' => $pairesInitiales,
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'image_credit' => 'Photo : source de test',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->editorial_proof_pairs)->toBe($pairesInitiales);
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

// ── Correctif 2026-08-28 (mandat conjoint avec le retrait de paire de preuve ci-dessus) : un
// payload PARTIEL qui ne touche NI 'summary' NI 'composed_summary' ne doit JAMAIS effacer un
// résumé machine existant. Avant ce correctif, la condition était `$updates !== []` :
// N'IMPORTE QUELLE clé de contenu (image_credit ici) effaçait structured_summary dès que la
// fiche ne portait pas déjà un résumé composé - ce qui a détruit le résumé riche d'environ 4400
// fiches d'avant /actu2 lors d'un enrichissement partiel sans rapport avec leur résumé. Règle
// absolue du projet : un champ ABSENT du payload signifie « je n'y touche pas », jamais
// « efface-le ». ──────────────────────────────────────────────────────────────────────────────

it('a payload touching only image_credit (no summary, no composed_summary) never erases an existing MACHINE structured_summary', function () {
    $article = nacArticle([
        'internal_source_text' => 'Texte source pour la non-régression du résumé.',
        'source_content_hash' => hash('sha256', 'Texte source pour la non-régression du résumé.'),
        'structured_summary' => ['hook' => 'MARQUEUR-RESUME-MACHINE-A-PRESERVER'],
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'image_credit' => 'Photo : agence de test',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $fresh = $article->fresh();
    expect($fresh->structured_summary)->not->toBeNull()
        ->and($fresh->structured_summary['hook'])->toBe('MARQUEUR-RESUME-MACHINE-A-PRESERVER')
        ->and($fresh->image_credit)->toBe('Photo : agence de test');
});

// Interaction JOINTE des deux défauts (raison du traitement conjoint du mandat) : retirer une
// paire de preuve sensible ne doit JAMAIS, en effet de bord, détruire le résumé riche existant
// de la même fiche.
it('retirer une paire de preuve (editorial_proof_pairs à null) ne détruit pas un résumé machine existant', function () {
    $article = nacArticle([
        'internal_source_text' => 'Texte source pour le retrait joint.',
        'source_content_hash' => hash('sha256', 'Texte source pour le retrait joint.'),
        'structured_summary' => ['hook' => 'MARQUEUR-RESUME-A-NE-PAS-PERDRE'],
        'editorial_proof_pairs' => [[
            'id' => 'pair-sensible-jointe',
            'statement' => 'MARQUEUR-DONNEE-SENSIBLE-JOINTE',
            'excerpt' => 'extrait sensible joint',
            'type' => 'analysis',
            'created_at' => now()->toIso8601String(),
        ]],
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'editorial_proof_pairs' => null,
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $fresh = $article->fresh();
    expect($fresh->editorial_proof_pairs)->toBe([])
        ->and($fresh->structured_summary)->not->toBeNull()
        ->and($fresh->structured_summary['hook'])->toBe('MARQUEUR-RESUME-A-NE-PAS-PERDRE');
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
        'image_credit' => 'Photo : source de test',
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
        'image_credit' => 'Photo : source de test',
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
        'image_credit' => 'Photo : source de test',
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

// ── Clé related_tool_slugs_remove (défaut 3, 2026-08-28) - contrepartie du retrait ─────
//
// Avant ce correctif, la porte savait ATTACHER un outil, jamais le DÉTACHER : un outil attaché
// à tort (fiche 38933, "Composer" attaché par un faux composé « Paragraph Composer ») restait
// irretirable par la porte officielle, seule alternative étant l'Eloquent/SQL/tinker interdit.

it('related_tool_slugs_remove détache uniquement l\'outil visé - un second outil attaché reste attaché', function () {
    $sourceText = 'Texte source pour le retrait d\'un outil lié.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);

    $aRetirer = nacTool('outil-nac-a-retirer');
    $aConserver = nacTool('outil-nac-a-conserver');
    $article->tools()->attach($aRetirer->id, ['source' => 'auto']);
    $article->tools()->attach($aConserver->id, ['source' => 'manual']);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'related_tool_slugs_remove' => ['outil-nac-a-retirer'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->expectsOutputToContain('1 outil(s) détaché(s)')
        ->assertSuccessful();

    $restants = $article->fresh()->tools()->get()->keyBy('id');
    expect($restants)->toHaveCount(1)
        ->and($restants->has($aConserver->id))->toBeTrue()
        ->and($restants->has($aRetirer->id))->toBeFalse();
});

it('un payload sans aucune clé d\'outils ne détache rien', function () {
    $sourceText = 'Texte source refus retrait implicite.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);

    $attache = nacTool('outil-nac-intouche');
    $article->tools()->attach($attache->id, ['source' => 'manual']);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'seo_title' => 'Un titre quelconque, sans rapport avec les outils',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->tools()->count())->toBe(1);
});

it('related_tool_slugs_remove signale un avertissement (jamais une erreur) pour un outil demandé mais non attaché à la fiche', function () {
    $sourceText = 'Texte source retrait outil non attache.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);

    nacTool('outil-nac-jamais-attache');

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'related_tool_slugs_remove' => ['outil-nac-jamais-attache'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->expectsOutputToContain('non attaché')
        ->assertSuccessful();

    expect($article->fresh()->tools()->count())->toBe(0);
});

it('related_tool_slugs_remove peut détacher un outil même dépublié depuis - sinon un outil mal attaché resterait à jamais irretirable', function () {
    $sourceText = 'Texte source retrait outil depublie.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);

    $outil = nacTool('outil-nac-depublie-apres-coup');
    $article->tools()->attach($outil->id, ['source' => 'auto']);
    $outil->update(['status' => 'draft']);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'related_tool_slugs_remove' => ['outil-nac-depublie-apres-coup'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->tools()->count())->toBe(0);
});

it('related_tool_slugs_remove non-tableau est refusé sans rien détacher', function () {
    $sourceText = 'Texte source refus retrait non-tableau.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);

    $attache = nacTool('outil-nac-refus-non-tableau');
    $article->tools()->attach($attache->id, ['source' => 'manual']);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'related_tool_slugs_remove' => 'pas-un-tableau',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->tools()->count())->toBe(1);
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

// ── Mode --enrich (chantier enrichissement AdSense, 2026-08-19) : SEULE exception au refus
// « fiche déjà publiée », réservée à la recomposition de contenu (composed_summary), jamais au
// titre/slug ni au statut de publication. ─────────────────────────────────────────────────────

it('--enrich applies a composed_summary payload to an already-published article without touching its slug or publication status', function () {
    $sourceText = 'Texte source pour la recomposition enrichie.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'is_published' => true,
        'published_at' => now()->subDays(30),
        'structured_summary' => null,
    ]);
    $ancienSlug = $article->slug;

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'composed_summary' => ['hook' => 'MARQUEUR-ENRICHISSEMENT-ADSENSE'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload, '--enrich' => true])
        ->assertSuccessful();

    $article = $article->fresh();
    expect($article->structured_summary['composed'] ?? null)->toBeTrue()
        ->and($article->structured_summary['hook'] ?? null)->toBe('MARQUEUR-ENRICHISSEMENT-ADSENSE')
        ->and($article->is_published)->toBeTrue()
        ->and($article->slug)->toBe($ancienSlug);
});

it('--enrich applies a corrected title to an already-published article, its slug staying strictly identical', function () {
    $sourceText = 'Texte source pour la correction de titre en enrich.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'is_published' => true,
        'published_at' => now()->subDays(30),
    ]);
    $ancienSlug = $article->slug;

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'title' => 'Titre corrigé après publication, adresse inchangée',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload, '--enrich' => true])
        ->assertSuccessful();

    $article = $article->fresh();
    expect($article->title)->toBe('Titre corrigé après publication, adresse inchangée')
        ->and($article->slug)->toBe($ancienSlug)
        ->and($article->is_published)->toBeTrue();
});

it('--enrich refuses a payload containing the slug key, even set to the article\'s own current value', function () {
    $sourceText = 'Texte source pour le refus de la clé slug en enrich.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'is_published' => true,
        'published_at' => now()->subDays(30),
    ]);
    $ancienSlug = $article->slug;

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'slug' => $ancienSlug,
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload, '--enrich' => true])
        ->assertFailed();

    $article = $article->fresh();
    expect($article->slug)->toBe($ancienSlug)
        ->and($article->is_published)->toBeTrue();
});

it('without --enrich, applying a payload to an already-published article is still refused (non-regression)', function () {
    $sourceText = 'Texte source pour la non-régression du refus.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'is_published' => true,
        'published_at' => now()->subDays(30),
    ]);
    $ancienSlug = $article->slug;

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'composed_summary' => ['hook' => 'NE-DOIT-JAMAIS-ETRE-APPLIQUE'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    $article = $article->fresh();
    expect($article->slug)->toBe($ancienSlug)
        ->and($article->structured_summary)->toBeNull();
});

// ── Correctif todo #1984 (2026-08-28), volet texte source purgé : sur une fiche DÉJÀ PUBLIÉE,
// NewsArticle::publishAndPurgeSource() met internal_source_text à null (chantier « zéro copie »).
// Le contrôle « citation retrouvée dans la source » d'une paire "fact" ne peut alors plus
// s'exécuter (EditorialProofNormalizer::containsExact() contre une chaîne vide échoue toujours) -
// ce n'est pas un échec de validation, c'est un contrôle qui ne s'applique pas. La paire est
// acceptée mais marquée 'source_verified' => false, jamais en silence. ────────────────────────

it('--enrich accepts a "fact" proof pair on an already-published article whose source text is purged, marking it unverifiable rather than rejecting it (todo #1984)', function () {
    $article = nacArticle([
        // Purge réelle : c'est exactement l'état d'une fiche passée par publishAndPurgeSource().
        'internal_source_text' => null,
        'is_published' => true,
        'published_at' => now()->subDays(10),
    ]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'editorial_proof_pairs' => [
            ['statement' => 'MARQUEUR-FAIT-NON-VERIFIABLE', 'excerpt' => 'un extrait quelconque, invérifiable puisque la source est purgée', 'type' => 'fact'],
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload, '--enrich' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('SANS vérification possible');

    $pairs = $article->fresh()->editorial_proof_pairs;
    expect($pairs)->toHaveCount(1)
        ->and($pairs[0]['statement'])->toBe('MARQUEUR-FAIT-NON-VERIFIABLE')
        ->and($pairs[0]['source_verified'])->toBeFalse();
});

it('--enrich still rejects a "fact" pair whose excerpt is absent when the source text happens to still be present on a published article (only an ACTUALLY purged source text is exempted)', function () {
    $sourceText = 'Texte source encore présent sur cette fiche publiée, pour ce test précis.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'is_published' => true,
        'published_at' => now()->subDays(5),
    ]);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'editorial_proof_pairs' => [
            ['statement' => 'Affirmation.', 'excerpt' => 'un extrait qui ne figure nulle part dans ce texte source', 'type' => 'fact'],
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload, '--enrich' => true])
        ->assertFailed();

    expect($article->fresh()->editorial_proof_pairs ?? [])->toBeEmpty();
});

// ── Clé related_article_slugs / related_article_slugs_remove (« Article de blogue lié »,
// 2026-08-29) - jumeau EXACT de related_tool_slugs / related_tool_slugs_remove ci-dessus, SEULE
// différence : plafond strict de 1 article lié par fiche (MAX_RELATED_ARTICLES), défendu à deux
// niveaux distincts, testés séparément ci-dessous : (a) la FORME du payload (un tableau de 2
// slugs et plus est refusé avant toute résolution) et (b) l'ÉTAT réel en base (un second appel
// qui viserait un article DIFFÉRENT alors qu'un premier est déjà lié est refusé). Le test
// « slug inconnu » de related_tool_slugs mélangeait un slug connu et un slug inconnu dans le
// MÊME tableau - impossible ici (le plafond de 1 refuserait la forme avant toute résolution) :
// adapté en deux payloads distincts sur la même fiche, chacun avec un seul slug.

function nacBlogArticle(string $slug, bool $published = true): \Modules\Blog\Models\Article
{
    $title = 'Article blogue nac '.$slug;
    $factory = \Modules\Blog\Models\Article::factory();
    $factory = $published ? $factory->published() : $factory->draft();

    // Tableau associatif (PAS une chaîne simple) pour que Spatie fixe la traduction sur 'fr_CA'
    // de façon déterministe, indépendamment de la locale courante au moment du test - même
    // convention que nacTool() (Tool::create avec slug/name en tableau).
    return $factory->create([
        'title' => ['fr_CA' => $title],
        'slug' => ['fr_CA' => $slug],
    ]);
}

it('related_article_slugs lie un article de blogue publié, et son lien apparaît sur la fiche publique', function () {
    $sourceText = 'Texte source pour la curation de l\'article de blogue lié.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);
    $blogArticle = nacBlogArticle('article-blogue-nac-connu');

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'related_article_slugs' => ['article-blogue-nac-connu'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $article = $article->fresh();
    $linked = $article->blogArticles()->get();
    expect($linked)->toHaveCount(1)
        ->and((int) $linked->first()->id)->toBe($blogArticle->id)
        ->and($linked->first()->pivot->source)->toBe('auto');

    // news:apply refuse d'écrire sur une fiche déjà publiée (hors --enrich) : la curation se
    // fait donc pendant que la fiche est encore brouillon, exactement comme le reste de cette
    // suite - la fiche est publiée APRÈS, directement (même geste que natFrontArticle()), pour
    // vérifier le rendu public.
    $article->update(['is_published' => true, 'published_at' => now()->subHour()]);

    $response = $this->get(route('news.show', $article->slug));

    $response->assertOk()
        ->assertSee('Article blogue nac article-blogue-nac-connu', false)
        ->assertSee(route('blog.show', 'article-blogue-nac-connu'), false);
});

it('related_article_slugs signale un slug inconnu sans faire échouer le reste du payload', function () {
    $sourceText = 'Texte source pour le slug d\'article inconnu.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'related_article_slugs' => ['slug-article-inconnu-xyz'],
        'seo_title' => 'Titre corrigé malgré le slug inconnu',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->expectsOutputToContain('slug-article-inconnu-xyz')
        ->assertSuccessful();

    expect($article->fresh()->seo_title)->toBe('Titre corrigé malgré le slug inconnu')
        ->and($article->fresh()->blogArticles()->count())->toBe(0);
});

it('related_article_slugs refuse de lier un article de blogue en brouillon', function () {
    $sourceText = 'Texte source pour le refus d\'un brouillon.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);
    nacBlogArticle('article-blogue-nac-brouillon', published: false);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'related_article_slugs' => ['article-blogue-nac-brouillon'],
    ]));

    // Résolu exactement comme un slug inconnu (published() ne le voit jamais) : la commande
    // réussit, mais rien n'est attaché - point 3 du mandat ("filtre published()").
    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->blogArticles()->count())->toBe(0);
});

it('related_article_slugs_remove détache l\'article de blogue lié', function () {
    $sourceText = 'Texte source pour le retrait d\'un article de blogue lié.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);
    $blogArticle = nacBlogArticle('article-blogue-nac-a-retirer');
    $article->blogArticles()->attach($blogArticle->id, ['source' => 'manual']);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'related_article_slugs_remove' => ['article-blogue-nac-a-retirer'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->expectsOutputToContain('article(s) de blogue détaché')
        ->assertSuccessful();

    expect($article->fresh()->blogArticles()->count())->toBe(0);
});

it('related_article_slugs refuse plus de 1 slug dans un même appel (plafond de forme)', function () {
    $sourceText = 'Texte source pour le refus du plafond de forme.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);
    nacBlogArticle('article-blogue-nac-plafond-1');
    nacBlogArticle('article-blogue-nac-plafond-2');

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'related_article_slugs' => ['article-blogue-nac-plafond-1', 'article-blogue-nac-plafond-2'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->blogArticles()->count())->toBe(0);
});

it('related_article_slugs refuse un second article différent quand la fiche en a déjà un lié (plafond cumulatif en base)', function () {
    $sourceText = 'Texte source pour le refus du plafond cumulatif.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);
    $premier = nacBlogArticle('article-blogue-nac-cumul-1');
    $article->blogArticles()->attach($premier->id, ['source' => 'manual']);
    nacBlogArticle('article-blogue-nac-cumul-2');

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'related_article_slugs' => ['article-blogue-nac-cumul-2'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->expectsOutputToContain('plafond de 1 article(s)')
        ->assertFailed();

    $restants = $article->fresh()->blogArticles()->get();
    expect($restants)->toHaveCount(1)
        ->and((int) $restants->first()->id)->toBe($premier->id);
});

// ── Tiret cadratin : retiré de la prose composée par le site, JAMAIS de la citation verbatim
// (fiche freecore, 2026-08-30 - le HTML servi portait 8 lignes de cadratin, dont la moitié
// était la citation anglaise « I maintain FreeCORE... model—FreeBSD...WebUI—but need... »,
// rendue deux fois - blockquote visible + articleBody JSON-LD. Aucun mécanisme ne nettoyait la
// prose composée AVANT cette clé : ni au rendu (show.blade.php ne traverse pas lv_typo_fr sur
// structured_summary), ni à l'écriture (aucune des normalizeComposed*() ne le faisait). Le
// rattrapage v1.233.1 n'avait touché QUE le code source statique (vues/PHP/lang), jamais les
// données. lv_strip_em_dash() (app/Helpers/typo.php) ferme ce trou à la SEULE porte d'écriture
// de composed_summary - jamais branchée sur normalizeComposedQuote(), qui reste intacte. ────

it('composed_summary : un cadratin dans hook/why_important/key_number/angle_qc_ca/action_concrete devient un trait d\'union en base', function () {
    $sourceText = 'Texte source pour le retrait du cadratin en prose composée.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'composed_summary' => [
            'hook' => 'Le projet reprend le code là où il a été laissé — sans lien avec l’éditeur.',
            'why_important' => 'Les utilisateurs gratuits — plusieurs milliers — restaient sans correctif.',
            'key_number' => 'Deux versions majeures — 13.3 à 15.1 — en une seule mise à jour.',
            'angle_qc_ca' => 'Un cas fréquent ici — petites entreprises sur du matériel amorti.',
            'action_concrete' => 'Sauvegarder la configuration — étape obligatoire — avant la mise à niveau.',
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $summary = $article->fresh()->structured_summary;
    expect($summary['hook'])->toBe('Le projet reprend le code là où il a été laissé - sans lien avec l’éditeur.')
        ->and($summary['why_important'])->toBe('Les utilisateurs gratuits - plusieurs milliers - restaient sans correctif.')
        ->and($summary['key_number'])->toBe('Deux versions majeures - 13.3 à 15.1 - en une seule mise à jour.')
        ->and($summary['angle_qc_ca'])->toBe('Un cas fréquent ici - petites entreprises sur du matériel amorti.')
        ->and($summary['action_concrete'])->toBe('Sauvegarder la configuration - étape obligatoire - avant la mise à niveau.');

    foreach (['hook', 'why_important', 'key_number', 'angle_qc_ca', 'action_concrete'] as $key) {
        expect($summary[$key])->not->toContain('—');
    }
});

it('composed_summary.key_points : un cadratin dans une puce devient un trait d\'union en base', function () {
    $sourceText = 'Texte source pour le retrait du cadratin dans key_points.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'composed_summary' => [
            'key_points' => [
                'FreeCORE fait passer FreeBSD 13.3 à 15.1 — deux versions majeures d’un coup.',
                'La télémétrie est retirée — ainsi que les fonctions Entreprise.',
            ],
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $points = $article->fresh()->structured_summary['key_points'];
    expect($points[0])->toBe('FreeCORE fait passer FreeBSD 13.3 à 15.1 - deux versions majeures d’un coup.')
        ->and($points[1])->toBe('La télémétrie est retirée - ainsi que les fonctions Entreprise.')
        ->and($points[0])->not->toContain('—')
        ->and($points[1])->not->toContain('—');
});

it('composed_summary.reperes_dates : un cadratin dans texte ou date devient un trait d\'union en base', function () {
    $sourceText = 'Texte source pour le retrait du cadratin dans reperes_dates.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'composed_summary' => [
            'reperes_dates' => [
                ['date' => '13 août 2024', 'texte' => 'Maintenance minimale — sustaining engineering.'],
            ],
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $repere = $article->fresh()->structured_summary['reperes_dates'][0];
    expect($repere['texte'])->toBe('Maintenance minimale - sustaining engineering.')
        ->and($repere['texte'])->not->toContain('—');
});

it('composed_summary.quote : le cadratin d\'une citation verbatim n\'est JAMAIS retiré (régression critique)', function () {
    $sourceText = 'Texte source pour la non-régression sur la citation verbatim.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);

    $citationExacte = 'I maintain FreeCORE, an independent continuation of TrueNAS CORE 13.3. '
        .'I started it because there are still users who want the CORE appliance model—FreeBSD, '
        .'OpenZFS, iocage jails, bhyve, and the familiar middleware/WebUI—but need a maintained '
        .'path beyond the point where upstream CORE development ended.';

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'composed_summary' => [
            'quote' => ['text' => $citationExacte, 'author' => 'Mainteneur du projet FreeCORE'],
        ],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    // Byte pour byte : la citation traverse news:apply SANS AUCUNE modification, cadratins
    // compris - contrairement à hook/why_important/etc. ci-dessus, ce champ ne passe JAMAIS
    // par lv_strip_em_dash(). Un seul octet différent falsifierait une source anglophone.
    $quote = $article->fresh()->structured_summary['quote'];
    expect($quote['text'])->toBe($citationExacte)
        ->and(substr_count($quote['text'], '—'))->toBe(2);
});

it('title/seo_title/summary : un cadratin devient un trait d\'union en base', function () {
    $sourceText = 'Texte source pour le retrait du cadratin dans title/seo_title/summary.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'title' => 'FreeCORE — la communauté reprend TrueNAS CORE',
        'seo_title' => 'FreeCORE — reprise communautaire de TrueNAS CORE',
        'summary' => 'Un projet communautaire — sans lien avec iXsystems — reprend le code.',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $article = $article->fresh();
    expect($article->title)->toBe('FreeCORE - la communauté reprend TrueNAS CORE')
        ->and($article->seo_title)->toBe('FreeCORE - reprise communautaire de TrueNAS CORE')
        ->and($article->summary)->toBe('Un projet communautaire - sans lien avec iXsystems - reprend le code.')
        ->and($article->title)->not->toContain('—')
        ->and($article->seo_title)->not->toContain('—')
        ->and($article->summary)->not->toContain('—');
});

// ── Clé meta_description (correctif 2026-08-30, tâche #1942 - « la description que Google
// affiche garde les anciennes valeurs ») - la balise publique <meta name="description"> était
// figée pour toujours dès qu'elle était posée une fois : cette porte ne pouvait jamais la
// corriger, même en --enrich. Deux volets testés séparément : (a) la clé s'applique comme
// n'importe quel champ texte simple (même garde que seo_title) ; (b) une correction de
// summary/composed_summary qui NE fournit PAS de nouvelle meta_description la remet
// automatiquement à null - jamais de valeur figée qui survit à la correction qui la rend
// fausse. ─────────────────────────────────────────────────────────────────────────────────────

it('applies meta_description like any simple text field, em dash stripped, without touching summary', function () {
    $sourceText = 'Texte source pour la correction de meta_description.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'meta_description' => 'Ancienne description périmée.',
    ]);
    $summaryAvant = $article->summary;

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'meta_description' => 'Nouvelle description — corrigée.',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $article = $article->fresh();
    expect($article->meta_description)->toBe('Nouvelle description - corrigée.')
        ->and($article->meta_description)->not->toContain('—')
        ->and($article->summary)->toBe($summaryAvant);
});

it('refuses a non-string, non-null meta_description', function () {
    $article = nacArticle(['meta_description' => 'Description existante.']);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'meta_description' => ['pas' => 'une chaîne'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->meta_description)->toBe('Description existante.');
});

it('refuses a meta_description longer than 255 characters', function () {
    $article = nacArticle(['meta_description' => 'Description existante.']);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'meta_description' => str_repeat('x', 256),
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertFailed();

    expect($article->fresh()->meta_description)->toBe('Description existante.');
});

it('accepts a meta_description of exactly 255 characters (boundary)', function () {
    $article = nacArticle(['meta_description' => 'Description existante.']);
    $borne = str_repeat('x', 255);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'meta_description' => $borne,
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->meta_description)->toBe($borne);
});

it('an explicit null meta_description clears an existing value back to the automatic cascade', function () {
    $article = nacArticle(['meta_description' => 'Description à effacer.']);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'meta_description' => null,
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->meta_description)->toBeNull();
});

it('an explicit empty-string meta_description is treated as null (never publishes an empty <meta> tag)', function () {
    $article = nacArticle(['meta_description' => 'Description à effacer.']);
    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'meta_description' => '',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->meta_description)->toBeNull();
});

it('a payload touching only image_credit (no summary, no composed_summary) never erases an existing meta_description', function () {
    $article = nacArticle([
        'internal_source_text' => 'Texte source pour la non-régression de meta_description.',
        'source_content_hash' => hash('sha256', 'Texte source pour la non-régression de meta_description.'),
        'meta_description' => 'MARQUEUR-DESCRIPTION-A-PRESERVER',
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'image_credit' => 'Photo : agence de test',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->meta_description)->toBe('MARQUEUR-DESCRIPTION-A-PRESERVER');
});

// ── Le coeur du correctif (tâche #1942) : une correction de fond qui ne rafraîchit pas
// meta_description ne doit plus jamais laisser une valeur périmée en ligne. ────────────────────

it('correcting summary WITHOUT providing meta_description resets the stale meta_description to null (recidive fix)', function () {
    $article = nacArticle([
        'internal_source_text' => 'Le produit coûte 12 dollars.',
        'source_content_hash' => hash('sha256', 'Le produit coûte 12 dollars.'),
        'meta_description' => 'MARQUEUR-DESCRIPTION-PERIMEE-20-DOLLARS',
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'summary' => 'Le produit coûte en réalité 12 dollars, pas 20.',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $article = $article->fresh();
    expect($article->summary)->toBe('Le produit coûte en réalité 12 dollars, pas 20.')
        ->and($article->meta_description)->toBeNull();
});

it('correcting composed_summary WITHOUT providing meta_description also resets the stale meta_description to null', function () {
    $article = nacArticle([
        'internal_source_text' => 'Texte source pour la composition.',
        'source_content_hash' => hash('sha256', 'Texte source pour la composition.'),
        'meta_description' => 'MARQUEUR-DESCRIPTION-PERIMEE-COMPOSED',
        'structured_summary' => null,
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'composed_summary' => ['hook' => 'Nouveau fait corrigé.'],
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    expect($article->fresh()->meta_description)->toBeNull();
});

it('correcting summary WHILE providing a fresh meta_description in the SAME payload keeps the explicit value (never overwritten by the auto-reset)', function () {
    $article = nacArticle([
        'internal_source_text' => 'Le produit coûte 12 dollars.',
        'source_content_hash' => hash('sha256', 'Le produit coûte 12 dollars.'),
        'meta_description' => 'MARQUEUR-DESCRIPTION-PERIMEE-20-DOLLARS',
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'summary' => 'Le produit coûte en réalité 12 dollars, pas 20.',
        'meta_description' => 'MARQUEUR-NOUVELLE-DESCRIPTION-12-DOLLARS',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload])
        ->assertSuccessful();

    $article = $article->fresh();
    expect($article->summary)->toBe('Le produit coûte en réalité 12 dollars, pas 20.')
        ->and($article->meta_description)->toBe('MARQUEUR-NOUVELLE-DESCRIPTION-12-DOLLARS');
});

it('--enrich corrects meta_description on an already-published article, without touching its slug or publication status (real-world scenario, task #1942)', function () {
    $sourceText = 'Texte source pour la correction de description en enrich.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'is_published' => true,
        'published_at' => now()->subDays(15),
        'meta_description' => 'MARQUEUR-ANCIENNE-DESCRIPTION-PUBLIEE',
    ]);
    $ancienSlug = $article->slug;

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'meta_description' => 'MARQUEUR-DESCRIPTION-CORRIGEE-EN-LIGNE',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload, '--enrich' => true])
        ->assertSuccessful();

    $article = $article->fresh();
    expect($article->meta_description)->toBe('MARQUEUR-DESCRIPTION-CORRIGEE-EN-LIGNE')
        ->and($article->slug)->toBe($ancienSlug)
        ->and($article->is_published)->toBeTrue();
});

it('--enrich correcting summary on an already-published article WITHOUT a fresh meta_description resets it to null, letting the public page fall back to a description synchronous with the corrected content', function () {
    $sourceText = 'Texte source pour le scénario reel de la tâche #1942.';
    $article = nacArticle([
        'internal_source_text' => $sourceText,
        'source_content_hash' => hash('sha256', $sourceText),
        'is_published' => true,
        'published_at' => now()->subDays(8),
        'summary' => 'Ancien résumé publié, avec un chiffre faux.',
        'meta_description' => 'MARQUEUR-DESCRIPTION-AVEC-LE-MEME-CHIFFRE-FAUX',
    ]);

    $payload = nacPayloadFile(array_merge(nacFreshMeta($article), [
        'summary' => 'Résumé corrigé, avec le bon chiffre.',
    ]));

    $this->artisan('news:apply', ['article' => $article->id, '--payload' => $payload, '--enrich' => true])
        ->assertSuccessful();

    $article = $article->fresh();
    expect($article->summary)->toBe('Résumé corrigé, avec le bon chiffre.')
        ->and($article->meta_description)->toBeNull()
        // La cascade publique (show.blade.php) part maintenant de displayExcerpt(), qui lit
        // le résumé qui vient d'être corrigé - jamais l'ancien chiffre faux.
        ->and($article->displayExcerpt(155))->toContain('bon chiffre')
        ->and($article->displayExcerpt(155))->not->toContain('chiffre faux');
});
