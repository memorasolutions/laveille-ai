<?php

declare(strict_types=1);

/**
 * Tests admin : écran de composition manuelle d'une actualité (Phase A - design doc "Actus -
 * composition manuelle assistée", 2026-08-15). Couvre l'accès (invité / non-admin / admin), la
 * persistance du texte source interne, sa suppression dédiée, et surtout le garde-fou le plus
 * important du chantier : ce texte ne doit JAMAIS apparaître dans une vue publique (page fiche,
 * JSON-LD, index).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Crée un vrai fichier image (via GD) enveloppé en UploadedFile de test - même pattern que
// Modules/Directory/tests/Feature/DeriveMasterFromUploadTest.php::makeDeriveMasterTestUpload().
function ncbFakeImageUpload(string $tmpName, int $width, int $height, string $format = 'jpeg'): UploadedFile
{
    $tmpPath = sys_get_temp_dir().'/'.$tmpName;
    $img = imagecreatetruecolor($width, $height);
    $color = imagecolorallocate($img, 11, 114, 133);
    imagefilledrectangle($img, 0, 0, $width - 1, $height - 1, $color);
    if ($format === 'png') {
        imagepng($img, $tmpPath);
        $mime = 'image/png';
    } else {
        imagejpeg($img, $tmpPath, 90);
        $mime = 'image/jpeg';
    }
    imagedestroy($img);

    return new UploadedFile($tmpPath, $tmpName, $mime, null, true);
}

// ── Helpers locaux (préfixés Ncb pour éviter tout conflit inter-fichiers) ──────

function ncbSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source composition',
        'url' => 'https://ncb-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function ncbArticle(int $sourceId, array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => "Article composition {$i}",
        'guid' => "guid-ncb-{$suffix}",
        'url' => "https://exemple.com/ncb-{$suffix}",
        'description' => '',
        'summary' => "Résumé initial {$i}",
        'slug' => "article-ncb-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

function ncbAdmin(): \App\Models\User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function ncbRegularUser(): \App\Models\User
{
    return \App\Models\User::factory()->create(['email_verified_at' => now()]);
}

// ── Accès (mêmes droits que le Concentré : EnsureIsAdmin / view_admin_panel) ──

it('redirects guest to login', function () {
    $this->get(route('admin.news.composition.index'))->assertRedirect(route('login'));
});

it('blocks a regular user without view_admin_panel from the composition screen (403)', function () {
    $user = ncbRegularUser();

    $response = $this->actingAs($user)->get(route('admin.news.composition.index'));

    $response->assertStatus(403);
});

it('allows an admin to view the composition screen', function () {
    $admin = ncbAdmin();

    $response = $this->actingAs($admin)->get(route('admin.news.composition.index'));

    $response->assertOk();
    $response->assertSee('compositionBuilder(', false);
    $response->assertSee('news-article-picker.js', false);
});

// ── Écran minimal : édition manuelle repliée, bouton principal (panel "club des sages",
//    2026-08-15) ──────────────────────────────────────────────────────────────────────

it('the composition screen renders the minimal layout: manual-edition details and the save+generate button', function () {
    $admin = ncbAdmin();

    $response = $this->actingAs($admin)->get(route('admin.news.composition.index'));

    $response->assertOk();
    $response->assertSee('nc-manual-details', false);
    $response->assertSee('Édition manuelle (filet de secours)', false);
    $response->assertSee('Enregistrer et générer le prompt Claude Code', false);
    // Le texte source est désormais transmis dans la requête de génération elle-même, le clic
    // fonctionne donc sans passage préalable par le bouton « Enregistrer » replié.
    $response->assertSee('source_text: this.formSourceText', false);
});

it('the composition screen renders both variants of the public-fiche link, gated by is_published', function () {
    $admin = ncbAdmin();

    $response = $this->actingAs($admin)->get(route('admin.news.composition.index'));

    $response->assertOk();
    // Lien réel, affiché seulement si l'actualité est publiée.
    $response->assertSee('x-show="selectedArticle.is_published"', false);
    $response->assertSee('Voir la fiche publique', false);
    // Badge texte (jamais un lien), affiché seulement pour un brouillon jamais publié.
    $response->assertSee('x-show="!selectedArticle.is_published"', false);
    $response->assertSee('Brouillon - pas encore publié', false);
    // Régression du bug signalé : l'ancien lien inconditionnel (404 garanti sur un brouillon)
    // ne doit plus exister tel quel.
    $response->assertDontSee('<a :href="selectedArticle.site_url" target="_blank" rel="noopener">🔗 Voir la fiche publique</a>', false);
});

// ── Persistance du texte source ────────────────────────────────────────────────

it('an admin can save the internal source text of a selected article', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id);

    $response = $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'seo_title' => 'Titre composé de test',
        'summary' => 'Résumé composé de test.',
        'internal_source_text' => 'MARQUEUR-TEXTE-SOURCE-INTERNE-DE-TEST',
    ]);

    $response->assertOk()->assertJson(['success' => true]);
    $article->refresh();
    expect($article->internal_source_text)->toBe('MARQUEUR-TEXTE-SOURCE-INTERNE-DE-TEST')
        ->and($article->seo_title)->toBe('Titre composé de test')
        ->and($article->summary)->toBe('Résumé composé de test.')
        ->and($article->description)->toBe(''); // jamais réutilisée, purgée
});

it('a non-admin cannot save the internal source text (403), nothing is written', function () {
    $user = ncbRegularUser();
    $source = ncbSource();
    $article = ncbArticle($source->id);

    $response = $this->actingAs($user)->putJson(route('admin.news.composition.update', $article), [
        'internal_source_text' => 'ne doit jamais être écrit',
    ]);

    $response->assertStatus(403);
    expect($article->fresh()->internal_source_text)->toBeNull();
});

it('omitting a field on update does not null out the others (sometimes rule)', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, ['seo_title' => 'Titre original conservé']);

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'internal_source_text' => 'Texte source seul.',
    ])->assertOk();

    $article->refresh();
    expect($article->seo_title)->toBe('Titre original conservé')
        ->and($article->internal_source_text)->toBe('Texte source seul.');
});

it('an admin can delete the internal source text without touching the rest of the fiche', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, [
        'internal_source_text' => 'MARQUEUR-A-SUPPRIMER',
        'seo_title' => 'Titre qui doit survivre',
    ]);

    $response = $this->actingAs($admin)->deleteJson(route('admin.news.composition.destroy-source-text', $article));

    $response->assertOk()->assertJson(['success' => true]);
    $article->refresh();
    expect($article->internal_source_text)->toBeNull()
        ->and($article->seo_title)->toBe('Titre qui doit survivre');
});

it('a non-admin cannot delete the internal source text (403)', function () {
    $user = ncbRegularUser();
    $source = ncbSource();
    $article = ncbArticle($source->id, ['internal_source_text' => 'MARQUEUR-DOIT-SURVIVRE']);

    $response = $this->actingAs($user)->deleteJson(route('admin.news.composition.destroy-source-text', $article));

    $response->assertStatus(403);
    expect($article->fresh()->internal_source_text)->toBe('MARQUEUR-DOIT-SURVIVRE');
});

it('the show() endpoint returns the internal source text only to an admin', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, ['internal_source_text' => 'MARQUEUR-VISIBLE-ADMIN-SEULEMENT']);

    $response = $this->actingAs($admin)->getJson(route('admin.news.composition.show', $article));

    $response->assertOk()->assertJsonPath('internal_source_text', 'MARQUEUR-VISIBLE-ADMIN-SEULEMENT');
    // Lien vers l'article original chez l'éditeur (demande du propriétaire 2026-08-17).
    $response->assertJsonPath('source_url', $article->resolved_url ?: $article->url);
});

it('the candidates() endpoint truncates the summary and never includes the internal source text', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    ncbArticle($source->id, ['internal_source_text' => 'MARQUEUR-JAMAIS-DANS-LA-LISTE']);

    $response = $this->actingAs($admin)->getJson(route('admin.news.composition.candidates'));

    $response->assertOk();
    $payload = json_encode($response->json());
    expect($payload)->not->toContain('MARQUEUR-JAMAIS-DANS-LA-LISTE')
        ->and($payload)->not->toContain('internal_source_text');
});

// ── Garde-fou le plus important : jamais dans une vue publique ────────────────

it('the internal source text never appears on the public article page', function () {
    $source = ncbSource();
    $article = ncbArticle($source->id, [
        'internal_source_text' => 'MARQUEUR-TEXTE-SOURCE-JAMAIS-PUBLIC-XYZ',
        'summary' => 'Résumé public normal, sans rapport avec le texte source.',
    ]);

    $response = $this->get(route('news.show', $article->slug));

    $response->assertOk();
    $response->assertDontSee('MARQUEUR-TEXTE-SOURCE-JAMAIS-PUBLIC-XYZ', false);
});

it('the internal source text is absent from the JSON-LD of the public article page', function () {
    $source = ncbSource();
    $article = ncbArticle($source->id, [
        'internal_source_text' => 'MARQUEUR-JSONLD-JAMAIS-XYZ',
        'structured_summary' => [
            'hook' => 'Accroche visible du test JSON-LD.',
            'key_points' => ['Un point clé.'],
            'why_important' => 'Une explication.',
        ],
    ]);

    $response = $this->get(route('news.show', $article->slug));

    $response->assertOk();
    $html = $response->getContent();
    expect($html)->toContain('Accroche visible du test JSON-LD.')
        ->and($html)->not->toContain('MARQUEUR-JSONLD-JAMAIS-XYZ');
});

it('the internal source text never appears on the public news index (article-card)', function () {
    $source = ncbSource();
    ncbArticle($source->id, [
        'internal_source_text' => 'MARQUEUR-INDEX-JAMAIS-XYZ',
    ]);

    $response = $this->get(route('news.index'));

    $response->assertOk();
    $response->assertDontSee('MARQUEUR-INDEX-JAMAIS-XYZ', false);
});

// ── Journalisation : même garde-fou que 'description' (voir ActusZeroCopiePipelineTest) ──

it('NewsArticle::activitylogFields does not include internal_source_text', function () {
    $article = new NewsArticle();
    $fields = (fn () => $this->activitylogFields)->call($article);

    expect($fields)->not->toContain('internal_source_text')
        ->and($fields)->toContain('title'); // le tableau reste non vide, pas une régression de portée
});

it('updating internal_source_text alone creates no activity log entry (not dirty-loggable)', function () {
    $source = ncbSource();
    $article = ncbArticle($source->id);
    $countBefore = \Spatie\Activitylog\Models\Activity::count();

    $article->update(['internal_source_text' => 'MARQUEUR-JAMAIS-JOURNALISE']);

    expect(\Spatie\Activitylog\Models\Activity::count())->toBe($countBefore);
});

// ── Phase B : génération du prompt de rédaction (design doc 2026-08-15, sections 5.1 et 7) ──

it('the generated prompt contains the source text, the attribution rule and the "no source" permission', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, [
        'internal_source_text' => 'MARQUEUR-TEXTE-SOURCE-DANS-LE-PROMPT-XYZ',
        'seo_title' => 'Titre de travail composé',
    ]);

    $response = $this->actingAs($admin)->postJson(
        route('admin.news.composition.generate-prompt', $article),
        ['angle' => 'impact pour les PME québécoises']
    );

    $response->assertOk()->assertJson(['success' => true]);
    $prompt = $response->json('prompt');

    expect($prompt)->toContain('MARQUEUR-TEXTE-SOURCE-DANS-LE-PROMPT-XYZ')
        ->and($prompt)->toContain('impact pour les PME québécoises')
        ->and($prompt)->toContain('ATTRIBUTION DANS LA PHRASE')
        ->and($prompt)->toContain('je n\'ai eu accès à aucune source confirmant');
});

it('generating a prompt without a source text is rejected (422)', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, ['internal_source_text' => null]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.generate-prompt', $article));

    $response->assertStatus(422);
});

it('a non-admin cannot generate a prompt (403)', function () {
    $user = ncbRegularUser();
    $source = ncbSource();
    $article = ncbArticle($source->id, ['internal_source_text' => 'texte source']);

    $response = $this->actingAs($user)->postJson(route('admin.news.composition.generate-prompt', $article));

    $response->assertStatus(403);
});

// ── Révision 2026-08-17 : texte source EN LIGNE dans generatePrompt (corrige le blocage
// "Colle d'abord le texte source" quand l'admin colle le texte sans passer par Enregistrer) ──

it('generatePrompt with an inline source_text persists it then generates the prompt', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, ['internal_source_text' => null]);
    expect($article->source_content_hash)->toBeNull();

    $response = $this->actingAs($admin)->postJson(
        route('admin.news.composition.generate-prompt', $article),
        ['source_text' => 'MARQUEUR-TEXTE-SOURCE-EN-LIGNE-XYZ']
    );

    $response->assertOk()->assertJson(['success' => true]);
    expect($response->json('prompt'))->toContain('MARQUEUR-TEXTE-SOURCE-EN-LIGNE-XYZ');

    $article->refresh();
    expect($article->internal_source_text)->toBe('MARQUEUR-TEXTE-SOURCE-EN-LIGNE-XYZ')
        ->and($article->source_content_hash)->toBe(hash('sha256', 'MARQUEUR-TEXTE-SOURCE-EN-LIGNE-XYZ'))
        ->and($article->source_captured_at)->not->toBeNull();
});

it('generatePrompt is still rejected (422) when both the stored and the inline source text are blank', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, ['internal_source_text' => null]);

    $response = $this->actingAs($admin)->postJson(
        route('admin.news.composition.generate-prompt', $article),
        ['source_text' => '   ']
    );

    $response->assertStatus(422);
    expect($article->fresh()->internal_source_text)->toBeNull();
});

it('generatePrompt backfills a missing source_content_hash for an article already carrying a stored source text', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    // Simule une fiche dont le texte source aurait été inséré avant le complément de
    // conservation (section 5.2) ou par un chemin autre que update() : hash absent malgré un
    // texte source déjà présent.
    $article = ncbArticle($source->id, [
        'internal_source_text' => 'Texte source déjà présent, sans empreinte enregistrée.',
        'source_content_hash' => null,
        'source_captured_at' => null,
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.generate-prompt', $article));

    $response->assertOk();
    $expectedHash = hash('sha256', 'Texte source déjà présent, sans empreinte enregistrée.');
    expect($response->json('prompt'))->toContain($expectedHash);
    expect($article->fresh()->source_content_hash)->toBe($expectedHash);
});

// ── Révision 2026-08-17 : contenu du prompt d'orchestration Claude Code CLI ────────────────

it('the generated prompt contains the mission, the nonce-delimited source, the named prohibitions, the freshness metadata and the news:apply command', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, [
        'internal_source_text' => 'Texte source pour vérifier le contenu du prompt d\'orchestration.',
        'seo_title' => 'Titre de travail',
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.generate-prompt', $article));

    $response->assertOk();
    $prompt = $response->json('prompt');
    $article->refresh();

    expect($prompt)
        ->toContain('Tu es Claude Code CLI dans le projet laveille.ai')
        ->toContain("fiche d'actualité {$article->id}")
        ->toContain($article->slug)
        // Nonce à usage unique : les deux délimiteurs partagent le même suffixe aléatoire.
        ->toContain('<<<SOURCE-')
        ->toContain('<<<FIN-SOURCE-')
        // Interdictions nommées.
        ->toContain('is_published')
        ->toContain('published_at')
        ->toContain('.env')
        ->toContain('AUCUNE migration')
        ->toContain("Ne modifie AUCUNE autre fiche")
        // Métadonnées de fraîcheur.
        ->toContain('expected_source_hash')
        ->toContain('expected_updated_at')
        ->toContain($article->source_content_hash)
        // Les trois étapes et la commande bornée.
        ->toContain('ÉTAPE 1 - RÉDACTION')
        ->toContain('ÉTAPE 2 - PREUVE ÉDITORIALE')
        ->toContain('ÉTAPE 3 - ÉCRITURE BORNÉE')
        ->toContain('ÉTAPE 4 - IMAGE')
        ->toContain('php artisan news:apply')
        ->toContain('--payload=')
        ->toContain('--image=')
        ->toContain('POINT D\'ARRÊT')
        // Jamais d'Eloquent/SQL direct.
        ->toContain('N\'écris JAMAIS directement en base')
        // Le standard de rédaction antérieur reste intégralement présent.
        ->toContain('ATTRIBUTION DANS LA PHRASE')
        ->toContain('je n\'ai eu accès à aucune source confirmant');
});

it('each call to generatePrompt uses a fresh random nonce', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, ['internal_source_text' => 'Texte source stable pour comparer deux nonces.']);

    $first = $this->actingAs($admin)->postJson(route('admin.news.composition.generate-prompt', $article))->json('prompt');
    $second = $this->actingAs($admin)->postJson(route('admin.news.composition.generate-prompt', $article))->json('prompt');

    preg_match('/<<<SOURCE-([A-Za-z0-9]+)>>>/', $first, $firstMatch);
    preg_match('/<<<SOURCE-([A-Za-z0-9]+)>>>/', $second, $secondMatch);

    expect($firstMatch[1] ?? null)->not->toBeNull()
        ->and($secondMatch[1] ?? null)->not->toBeNull()
        ->and($firstMatch[1])->not->toBe($secondMatch[1]);
});

// ── Phase B : fiche de preuve éditoriale (design doc section 7) ─────────────────

it('a "fact" pair whose excerpt is NOT a substring of the source text is rejected', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, [
        'internal_source_text' => 'Le ministère a annoncé un budget de 12 millions de dollars.',
    ]);

    $response = $this->actingAs($admin)->postJson(route('admin.news.composition.proof-pairs.store', $article), [
        'statement' => 'Le gouvernement investit massivement.',
        'excerpt' => 'un budget de 20 millions de dollars', // chiffre différent, pas une sous-chaîne
        'type' => 'fact',
    ]);

    $response->assertStatus(422);
    expect($article->fresh()->editorial_proof_pairs ?? [])->toBeEmpty();
});

it('a "fact" pair with an exact excerpt is accepted, and an "analysis" pair is accepted without substring check', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, [
        'internal_source_text' => "Le ministère a annoncé un budget de 12 millions de dollars aujourd'hui.",
    ]);

    $factResponse = $this->actingAs($admin)->postJson(route('admin.news.composition.proof-pairs.store', $article), [
        'statement' => 'Le budget annoncé atteint 12 millions.',
        // Apostrophe typographique + espaces multiples volontaires : normalisation raisonnable
        // attendue (espaces, apostrophes typographiques) - EditorialProofNormalizer.
        'excerpt' => "un budget de 12 millions de dollars  aujourd’hui",
        'type' => 'fact',
    ]);
    $factResponse->assertOk()->assertJson(['success' => true]);

    $analysisResponse = $this->actingAs($admin)->postJson(route('admin.news.composition.proof-pairs.store', $article), [
        'statement' => 'Cette annonce confirme une tendance à la hausse des investissements publics.',
        'excerpt' => 'ce texte ne figure nulle part dans la source, et ce n\'est pas grave',
        'type' => 'analysis',
    ]);
    $analysisResponse->assertOk()->assertJson(['success' => true]);

    $pairs = $article->fresh()->editorial_proof_pairs;
    expect($pairs)->toHaveCount(2)
        ->and(collect($pairs)->pluck('type')->all())->toBe(['fact', 'analysis']);
});

it('a non-admin cannot add a proof pair (403), nothing is written', function () {
    $user = ncbRegularUser();
    $source = ncbSource();
    $article = ncbArticle($source->id, ['internal_source_text' => 'texte source disponible']);

    $response = $this->actingAs($user)->postJson(route('admin.news.composition.proof-pairs.store', $article), [
        'statement' => 'ne doit jamais être écrit',
        'excerpt' => 'texte source',
        'type' => 'fact',
    ]);

    $response->assertStatus(403);
    expect($article->fresh()->editorial_proof_pairs ?? [])->toBeEmpty();
});

it('proof pairs survive the deletion of the source text', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, [
        'internal_source_text' => 'Le rapport confirme une croissance de 8 pourcent.',
    ]);

    $this->actingAs($admin)->postJson(route('admin.news.composition.proof-pairs.store', $article), [
        'statement' => 'La croissance atteint 8 pourcent selon le rapport.',
        'excerpt' => 'une croissance de 8 pourcent',
        'type' => 'fact',
    ])->assertOk();

    expect($article->fresh()->editorial_proof_pairs)->toHaveCount(1);

    $this->actingAs($admin)->deleteJson(route('admin.news.composition.destroy-source-text', $article))->assertOk();

    $article->refresh();
    expect($article->internal_source_text)->toBeNull()
        ->and($article->editorial_proof_pairs)->toHaveCount(1)
        ->and($article->editorial_proof_pairs[0]['excerpt'])->toBe('une croissance de 8 pourcent');
});

it('an admin can remove a single proof pair without touching the others', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, [
        'internal_source_text' => 'Premier fait cité. Deuxième fait cité.',
    ]);

    $this->actingAs($admin)->postJson(route('admin.news.composition.proof-pairs.store', $article), [
        'statement' => 'Un premier passage.',
        'excerpt' => 'Premier fait cité',
        'type' => 'fact',
    ])->assertOk();
    $this->actingAs($admin)->postJson(route('admin.news.composition.proof-pairs.store', $article), [
        'statement' => 'Un second passage.',
        'excerpt' => 'Deuxième fait cité',
        'type' => 'fact',
    ])->assertOk();

    $pairs = $article->fresh()->editorial_proof_pairs;
    expect($pairs)->toHaveCount(2);
    $firstPairId = $pairs[0]['id'];

    $response = $this->actingAs($admin)->deleteJson(
        route('admin.news.composition.proof-pairs.destroy', ['article' => $article, 'pair' => $firstPairId])
    );

    $response->assertOk();
    $remaining = $article->fresh()->editorial_proof_pairs;
    expect($remaining)->toHaveCount(1)
        ->and($remaining[0]['statement'])->toBe('Un second passage.');
});

it('the editorial_proof_pairs column never appears in any public view or the candidates() listing', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, [
        'internal_source_text' => 'Texte source pour la fiche de preuve.',
        'editorial_proof_pairs' => [[
            'id' => 'test-pair-id',
            'statement' => 'MARQUEUR-PAIRE-JAMAIS-PUBLIC-XYZ',
            'excerpt' => 'MARQUEUR-EXTRAIT-JAMAIS-PUBLIC-XYZ',
            'type' => 'fact',
            'created_at' => now()->toIso8601String(),
        ]],
    ]);

    // Jamais sur la fiche publique.
    $publicResponse = $this->get(route('news.show', $article->slug));
    $publicResponse->assertOk();
    $publicResponse->assertDontSee('MARQUEUR-PAIRE-JAMAIS-PUBLIC-XYZ', false);
    $publicResponse->assertDontSee('MARQUEUR-EXTRAIT-JAMAIS-PUBLIC-XYZ', false);

    // Jamais dans l'index public.
    $indexResponse = $this->get(route('news.index'));
    $indexResponse->assertOk();
    $indexResponse->assertDontSee('MARQUEUR-PAIRE-JAMAIS-PUBLIC-XYZ', false);

    // Jamais dans candidates() (liste en vrac de l'admin) - même règle que internal_source_text.
    $candidatesResponse = $this->actingAs($admin)->getJson(route('admin.news.composition.candidates'));
    $candidatesResponse->assertOk();
    $payload = json_encode($candidatesResponse->json());
    expect($payload)->not->toContain('MARQUEUR-PAIRE-JAMAIS-PUBLIC-XYZ')
        ->and($payload)->not->toContain('editorial_proof_pairs');
});

it('NewsArticle::activitylogFields does not include editorial_proof_pairs', function () {
    $article = new NewsArticle();
    $fields = (fn () => $this->activitylogFields)->call($article);

    expect($fields)->not->toContain('editorial_proof_pairs');
});

// ── Phase D : prompt d'image (design doc section 5.3/5.4) ─────────────────────

it('the image prompt contains the site style and the title of the fiche', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, ['seo_title' => 'MARQUEUR-TITRE-PROMPT-IMAGE-XYZ']);

    $response = $this->actingAs($admin)->postJson(
        route('admin.news.composition.generate-image-prompt', $article),
        ['angle' => 'impact pour les PME québécoises']
    );

    $response->assertOk()->assertJson(['success' => true]);
    $prompt = $response->json('prompt');

    expect($prompt)->toContain('MARQUEUR-TITRE-PROMPT-IMAGE-XYZ')
        ->and($prompt)->toContain('impact pour les PME québécoises')
        ->and($prompt)->toContain('ISOMÉTRIQUE')
        ->and($prompt)->toContain('AUCUN texte');
});

it('a non-admin cannot generate an image prompt (403)', function () {
    $user = ncbRegularUser();
    $source = ncbSource();
    $article = ncbArticle($source->id);

    $response = $this->actingAs($user)->postJson(route('admin.news.composition.generate-image-prompt', $article));

    $response->assertStatus(403);
});

// ── Phase D : dépôt manuel de l'image, validation automatique (section 5.3/5.4) ──

it('a valid image deposit produces the 1200x630 social JPEG AND a WebP variant, replacing the fallback', function () {
    Storage::fake('public');
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id);

    $upload = ncbFakeImageUpload('depot-valide.jpg', 1600, 900);

    $response = $this->actingAs($admin)->post(
        route('admin.news.composition.upload-image', $article),
        ['image' => $upload],
        ['Accept' => 'application/json']
    );

    $response->assertOk()->assertJson(['success' => true]);

    Storage::disk('public')->assertExists("news/images/{$article->id}.jpg");
    Storage::disk('public')->assertExists("news/images/{$article->id}.webp");

    [$w, $h] = getimagesizefromstring(Storage::disk('public')->get("news/images/{$article->id}.jpg"));
    expect($w)->toBe(1200)->and($h)->toBe(630);
});

it('a file with the wrong real MIME type is rejected even with a correct .jpg extension', function () {
    Storage::fake('public');
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id);

    // Extension .jpg et Content-Type déclaré "image/jpeg", mais contenu réel = texte brut :
    // la validation 'image'/'mimes' de Laravel inspecte le CONTENU, pas seulement l'extension
    // ou le Content-Type déclaré par le client (spoofable).
    $tmpPath = sys_get_temp_dir().'/pas-une-image.jpg';
    file_put_contents($tmpPath, str_repeat('ceci n\'est pas une image, seulement du texte. ', 50));
    $upload = new UploadedFile($tmpPath, 'pas-une-image.jpg', 'image/jpeg', null, true);

    $response = $this->actingAs($admin)->post(
        route('admin.news.composition.upload-image', $article),
        ['image' => $upload],
        ['Accept' => 'application/json']
    );

    $response->assertStatus(422);
    Storage::disk('public')->assertMissing("news/images/{$article->id}.jpg");
});

it('an image below the minimum dimensions is rejected', function () {
    Storage::fake('public');
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id);

    $upload = ncbFakeImageUpload('trop-petite.jpg', 100, 60);

    $response = $this->actingAs($admin)->post(
        route('admin.news.composition.upload-image', $article),
        ['image' => $upload],
        ['Accept' => 'application/json']
    );

    $response->assertStatus(422);
    Storage::disk('public')->assertMissing("news/images/{$article->id}.jpg");
});

it('a non-admin cannot upload an image (403)', function () {
    Storage::fake('public');
    $user = ncbRegularUser();
    $source = ncbSource();
    $article = ncbArticle($source->id);

    $upload = ncbFakeImageUpload('non-admin.jpg', 1600, 900);

    $response = $this->actingAs($user)->post(
        route('admin.news.composition.upload-image', $article),
        ['image' => $upload]
    );

    $response->assertStatus(403);
    Storage::disk('public')->assertMissing("news/images/{$article->id}.jpg");
});

// ── Complément de conservation (design doc section 5.2) ────────────────────────

it('pasting a source text fills source_captured_at and source_content_hash', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id);
    expect($article->source_captured_at)->toBeNull()
        ->and($article->source_content_hash)->toBeNull();

    $text = 'MARQUEUR-TEXTE-SOURCE-PROVENANCE-XYZ';
    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'internal_source_text' => $text,
    ])->assertOk();

    $article->refresh();
    expect($article->source_captured_at)->not->toBeNull()
        ->and($article->source_content_hash)->toBe(hash('sha256', $text));
});

it('deleting the source text preserves the capture date and the content hash', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id);

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'internal_source_text' => 'Texte source à supprimer ensuite.',
    ])->assertOk();

    $article->refresh();
    $capturedAt = $article->source_captured_at;
    $hash = $article->source_content_hash;
    expect($capturedAt)->not->toBeNull()->and($hash)->not->toBeNull();

    $this->actingAs($admin)->deleteJson(route('admin.news.composition.destroy-source-text', $article))->assertOk();

    $article->refresh();
    expect($article->internal_source_text)->toBeNull()
        ->and($article->source_captured_at->toIso8601String())->toBe($capturedAt->toIso8601String())
        ->and($article->source_content_hash)->toBe($hash);
});

it('re-saving the same source text does not change an already-recorded capture date', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id);
    $text = 'Texte source stable, non modifié entre les deux sauvegardes.';

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'internal_source_text' => $text,
    ])->assertOk();
    $capturedAt = $article->fresh()->source_captured_at;

    $this->actingAs($admin)->putJson(route('admin.news.composition.update', $article), [
        'seo_title' => 'Simple retouche du titre, texte source identique',
        'internal_source_text' => $text,
    ])->assertOk();

    expect($article->fresh()->source_captured_at->toIso8601String())->toBe($capturedAt->toIso8601String());
});

it('neither source_content_hash nor source_captured_at appear in any public view or candidates()', function () {
    $admin = ncbAdmin();
    $source = ncbSource();
    $article = ncbArticle($source->id, [
        'internal_source_text' => 'Texte source pour vérifier la non-exposition publique.',
        'source_captured_at' => now(),
        'source_content_hash' => hash('sha256', 'MARQUEUR-EMPREINTE-JAMAIS-PUBLIQUE-XYZ'),
    ]);

    $publicResponse = $this->get(route('news.show', $article->slug));
    $publicResponse->assertOk();
    $publicResponse->assertDontSee($article->source_content_hash, false);

    $indexResponse = $this->get(route('news.index'));
    $indexResponse->assertOk();
    $indexResponse->assertDontSee($article->source_content_hash, false);

    $candidatesResponse = $this->actingAs($admin)->getJson(route('admin.news.composition.candidates'));
    $candidatesResponse->assertOk();
    $payload = json_encode($candidatesResponse->json());
    expect($payload)->not->toContain($article->source_content_hash)
        ->and($payload)->not->toContain('source_content_hash')
        ->and($payload)->not->toContain('source_captured_at');
});
