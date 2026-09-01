<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * v1.244.15 - Régression double échappement HTML dans le balisage MACHINE d'une fiche.
 *
 * Deux points d'échappement distincts, corrigés à la source :
 *   1. Modules/News/resources/views/public/show.blade.php (meta llm:summary / llm:keywords) :
 *      `{{ e($x) }}` appliquait e() une première fois (droite devient `&#039;`), puis Blade
 *      échappait une seconde fois automatiquement (`&` devient `&amp;`) → `L&amp;#039;IA`.
 *   2. Modules/FrontTheme/resources/views/partials/breadcrumb.blade.php (JSON-LD
 *      BreadcrumbList) : `"name": "{{ $item }}"` plaçait une entité HTML (échappement Blade)
 *      À L'INTÉRIEUR d'une chaîne JSON, où elle n'est jamais décodée (le contenu d'un
 *      `<script>` n'est pas repassé par le parseur HTML) → le moteur qui lit ce JSON-LD reçoit
 *      littéralement les 6 caractères `&#039;` au lieu d'une apostrophe.
 *
 * Preuve exigée par la consigne de correctif : un titre contenant `<script>`, `&`, `"` et `'`
 * doit ressortir inoffensif ET FIDÈLE (round-trip exact) dans le HTML comme dans le JSON-LD.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Mme pour éviter tout conflit inter-fichiers) ──────────────

function mmeSource(string $name): NewsSource
{
    static $i = 0;
    $i++;

    return NewsSource::create([
        'name' => $name,
        'url' => "https://mme-source-{$i}.exemple.com/rss",
        'language' => 'fr',
        'active' => true,
    ]);
}

function mmeArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => mmeSource('Source test')->id,
        'title' => "Article échappement {$i}",
        'guid' => "guid-mme-{$suffix}",
        'url' => "https://exemple.com/mme-{$suffix}",
        'description' => '',
        // Garde-fou anti-corps-vide (design doc "Actus - zéro copie du texte source",
        // 2026-08-13, section 4.4) : une fiche publiée sans résumé n'est plus servie (404).
        'summary' => "Résumé de test échappement {$i}",
        'slug' => "article-mme-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

/**
 * Extrait tous les blocs <script type="application/ld+json">...</script> d'un HTML rendu et
 * les json_decode() en tableaux associatifs. Un JSON invalide fait échouer le test avec le
 * fragment fautif en message (jamais un simple false silencieux).
 *
 * @return array<int, array<string, mixed>>
 */
function mmeJsonLdBlocks(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
    $blocks = [];
    foreach ($matches[1] as $raw) {
        $decoded = json_decode($raw, true);
        expect(json_last_error())->toBe(JSON_ERROR_NONE, 'JSON-LD invalide : '.json_last_error_msg()." - fragment : {$raw}");
        $blocks[] = $decoded;
    }

    return $blocks;
}

/**
 * Retrouve, parmi une liste de schémas JSON-LD (chaque bloc <script> peut contenir un seul
 * schéma ou un tableau de schémas), le premier dont '@type' correspond.
 */
function mmeFindSchema(array $blocks, string $type): ?array
{
    foreach ($blocks as $block) {
        $candidates = array_is_list($block) ? $block : [$block];
        foreach ($candidates as $schema) {
            if (($schema['@type'] ?? null) === $type) {
                return $schema;
            }
        }
    }

    return null;
}

// ── Le titre dangereux, réutilisé par tous les tests de ce fichier ─────────────────────

function mmeDangerousTitle(): string
{
    return 'Les IA <script>alert(1)</script> "changent" tout & l\'avenir\'s';
}

// ── (a) meta llm:summary / llm:keywords : plus de double échappement ───────────────────

it('llm:summary et llm:keywords ne double-échappent plus un titre/source à caractères dangereux', function () {
    $dangerous = mmeDangerousTitle();
    $dangerousSource = 'Source & Médias "Test" - l\'info';

    $source = mmeSource($dangerousSource);
    $article = mmeArticle([
        'news_source_id' => $source->id,
        'title' => $dangerous,
        'seo_title' => $dangerous,
        'meta_description' => $dangerous,
    ]);

    $response = $this->get(route('news.show', $article->slug));
    $response->assertStatus(200);
    $html = $response->getContent();

    // Signature EXACTE du bug rapporté : jamais de double échappement visible.
    expect($html)->not->toContain('&amp;#039;');
    expect($html)->not->toContain('&amp;#39;');
    expect($html)->not->toContain('&amp;amp;');

    // Jamais de script injecté tel quel nulle part sur la page.
    expect($html)->not->toContain('<script>alert(1)</script>');

    // Extraction stricte du contenu de l'attribut content="" du meta llm:summary.
    preg_match('/<meta name="llm:summary" content="(.*?)">/s', $html, $m);
    expect($m)->toHaveCount(2, 'meta llm:summary introuvable dans le HTML rendu');
    $decodedSummary = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');

    // Round-trip exact : un SEUL décodage HTML doit restituer le titre ET la description
    // ET le nom de source tels que saisis - ni entité résiduelle (sous-échappement),
    // ni caractère détruit (sur-échappement).
    expect($decodedSummary)->toBe("{$dangerous} - {$dangerous} ({$dangerousSource})");

    preg_match('/<meta name="llm:keywords" content="(.*?)">/s', $html, $mk);
    expect($mk)->toHaveCount(2, 'meta llm:keywords introuvable dans le HTML rendu');
    $decodedKeywords = html_entity_decode($mk[1], ENT_QUOTES, 'UTF-8');
    expect($decodedKeywords)->toBe("actualité IA, {$dangerousSource}, intelligence artificielle, francophone, Québec");

    // Contrepartie de sécurité : l'attribut HTML reste correctement échappé une fois (jamais
    // de guillemet brut qui romprait l'attribut content="").
    expect($m[1])->not->toContain('"changent"');
    expect($m[1])->toContain('&quot;changent&quot;');
});

// ── (b) JSON-LD BreadcrumbList : plus d'entité HTML dans une chaîne JSON ────────────────

it('le BreadcrumbList JSON-LD restitue le titre exact (round-trip), jamais une entité HTML', function () {
    $dangerous = mmeDangerousTitle();
    $article = mmeArticle([
        'title' => $dangerous,
        'seo_title' => $dangerous,
    ]);

    $response = $this->get(route('news.show', $article->slug));
    $response->assertStatus(200);
    $html = $response->getContent();

    $blocks = mmeJsonLdBlocks($html);
    $breadcrumb = mmeFindSchema($blocks, 'BreadcrumbList');
    expect($breadcrumb)->not->toBeNull('Aucun schéma BreadcrumbList trouvé dans le JSON-LD rendu');

    $items = $breadcrumb['itemListElement'];
    expect($items)->not->toBeEmpty();
    $last = end($items);

    // Le dernier maillon du fil d'Ariane est la fiche elle-même : son "name" doit être
    // l'apostrophe et les caractères dangereux INTACTS, jamais "&#039;" ni "&amp;#039;".
    expect($last['name'])->toBe($dangerous);
    expect($last['name'])->not->toContain('&#039;');
    expect($last['name'])->not->toContain('&amp;');
    expect($last['name'])->not->toContain('&quot;');

    // Contrôle : le schéma NewsArticle (déjà construit via json_encode côté JsonLdService)
    // doit lui aussi restituer le titre exact - preuve que le mécanisme correct produit un
    // résultat identique des deux côtés.
    $newsArticleSchema = mmeFindSchema($blocks, 'NewsArticle');
    expect($newsArticleSchema)->not->toBeNull();
    expect($newsArticleSchema['headline'])->toBe($dangerous);
});

// ── (c) Fil d'Ariane visuel (hors JSON-LD) : le HTML visible reste protégé XSS ──────────

it('le fil d\'Ariane visuel échappe toujours correctement le titre dangereux en HTML', function () {
    $dangerous = mmeDangerousTitle();
    $article = mmeArticle([
        'title' => $dangerous,
        'seo_title' => $dangerous,
    ]);

    $response = $this->get(route('news.show', $article->slug));
    $response->assertStatus(200);
    $html = $response->getContent();

    // Le <span> visuel du fil d'Ariane (breadcrumb.blade.php ligne ~43) doit échapper le HTML
    // normalement (comportement INCHANGÉ par le correctif, qui ne touche que le <script> JSON-LD).
    expect($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
});

// ── (d) Non-régression : un titre SANS caractère spécial reste identique à avant ────────

it('un titre sans caractère spécial ressort identique (non-régression)', function () {
    $article = mmeArticle([
        'title' => 'Un titre parfaitement ordinaire sans piège',
        'seo_title' => null,
        'meta_description' => 'Une description ordinaire.',
    ]);

    $response = $this->get(route('news.show', $article->slug));
    $response->assertStatus(200);
    $html = $response->getContent();

    expect($html)->toContain('Un titre parfaitement ordinaire sans piège');

    $blocks = mmeJsonLdBlocks($html);
    $breadcrumb = mmeFindSchema($blocks, 'BreadcrumbList');
    $last = end($breadcrumb['itemListElement']);
    expect($last['name'])->toBe('Un titre parfaitement ordinaire sans piège');
});
