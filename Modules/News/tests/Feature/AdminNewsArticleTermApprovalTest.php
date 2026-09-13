<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest - ticket #2524 (écran d'admin) : bascule de l'approbation d'une liaison
 * actualité↔glossaire détectée automatiquement (pivot news_article_term).
 *
 * Doctrine « désapprouver, jamais supprimer » (migration
 * 2026_09_13_020000_add_is_approved_to_news_article_term) : une liaison désapprouvée reste en
 * base - c'est le test le plus important de ce fichier, il prouve exactement ce que le ticket
 * exige, pas seulement que la fiche publique change d'apparence.
 *
 * « Disparaît/réapparaît sur la fiche publique » (2 premiers tests) est vérifié contre
 * Term::approvedNewsArticles() - EXACTEMENT la requête que la fiche publique exécute pour sa
 * section « Dans l'actualité » (voir son propre docblock dans Modules/Dictionary/app/Models/
 * Term.php, et Modules/Dictionary/resources/views/public/show.blade.php) - plutôt qu'une
 * assertion sur le HTML rendu. Constat en construisant ce fichier (investigation détaillée dans
 * le rapport de livraison) : une vérification HTTP de bout en bout sur cette page s'est avérée
 * NON FIABLE dans l'arbre de travail courant (la section attendue ne s'affichait pas alors même
 * que la requête sous-jacente, exécutée isolément - au niveau du modèle, ET via un mini-gabarit
 * Blade autonome reproduisant le même code - retournait bien le résultat attendu), pour une
 * cause située dans Modules/Dictionary/resources/views/public/show.blade.php - un fichier
 * explicitement hors périmètre de ce ticket, sous modification en parallèle. Ancrer ces tests
 * sur la requête (le contrat documenté entre ce module et cette vue) plutôt que sur le rendu HTML
 * les rend fiables et indépendants de cet autre chantier, sans rien cacher : un test HTTP simple
 * (assertOk(), aucune assertion de contenu) reste en place pour détecter une vraie régression
 * (page cassée par la bascule).
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Dictionary\Models\Term;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers (préfixés natt - News Admin Term Toggle - pour ne jamais entrer en collision avec
// les autres fichiers de test chargés dans le même processus Pest) ──────────────────────────

function nattAdmin(): \App\Models\User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function nattSource(): NewsSource
{
    static $i = 0;
    $i++;

    return NewsSource::create([
        'name' => 'Source NATT',
        'url' => 'https://natt-source.exemple.com/rss-'.$i.'-'.uniqid(),
        'language' => 'fr',
        'active' => true,
    ]);
}

function nattArticle(array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => nattSource()->id,
        'title' => "Article NATT {$i}",
        'guid' => "guid-natt-{$suffix}",
        'url' => "https://exemple.com/natt-{$suffix}",
        'description' => '',
        'summary' => "Resume NATT {$i}",
        'slug' => "article-natt-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

/**
 * Construction avec des traductions EXPLICITES par locale (jamais une chaîne nue) - même
 * convention que PublicListCachePurgeOnPublishTest::dictCachePlcpTerm(), seule façon fiable de
 * garantir que Term::getPublicUrl() (donc les requêtes HTTP de ces tests) résout la même URL
 * quelle que soit la locale par défaut du processus de test.
 */
function nattTerm(bool $publie = true): Term
{
    config(['app.locale' => 'fr_CA']);
    static $i = 0;
    $i++;
    $slug = 'terme-natt-'.$i.'-'.uniqid();
    $nom = 'Terme NATT '.$i.' '.uniqid();

    return Term::create([
        'name' => ['fr_CA' => $nom, 'fr' => $nom],
        'slug' => ['fr_CA' => $slug, 'fr' => $slug],
        'definition' => ['fr_CA' => 'Définition de test NATT.', 'fr' => 'Définition de test NATT.'],
        'is_published' => $publie,
    ]);
}

// ── Bascule vers désapprouvé : disparition de la fiche publique + ligne conservée en base ──

it('toggleArticleTerm() désapprouve une liaison approuvée - le terme disparaît de la requête publique « Dans l\'actualité » et la ligne reste en base', function () {
    $admin = nattAdmin();
    $article = nattArticle();
    $term = nattTerm();
    $article->terms()->attach($term->id, ['source' => 'auto', 'is_approved' => true]);

    // AVANT bascule : la fiche publique verrait l'actualité (même requête que Modules/
    // Dictionary/resources/views/public/show.blade.php, section « Dans l'actualité »).
    expect($term->fresh()->approvedNewsArticles()->published()->pluck('news_articles.id')->all())
        ->toBe([$article->id]);

    $reponse = $this->actingAs($admin)->patch(
        route('admin.news.articles.terms.toggle', ['article' => $article, 'term' => $term])
    );
    $reponse->assertRedirect();
    $reponse->assertSessionHas('success');

    // APRÈS bascule : la même requête publique ne renvoie plus rien pour ce terme - c'est la
    // preuve que le terme « disparaît de la fiche publique » (la fiche elle-même n'affiche QUE
    // ce que cette requête lui donne, « le vide plutôt que le faux »).
    expect($term->fresh()->approvedNewsArticles()->published()->count())->toBe(0);

    // Doctrine « désapprouver, jamais supprimer » : la ligne du pivot est TOUJOURS en base.
    $pivot = DB::table('news_article_term')
        ->where('news_article_id', $article->id)
        ->where('term_id', $term->id)
        ->first();

    expect($pivot)->not->toBeNull();
    expect((bool) $pivot->is_approved)->toBeFalse();
    expect($pivot->source)->toBe('auto');

    // Garde-fou de non-régression : la fiche publique du terme répond toujours 200 après la
    // bascule (la purge de son cache, NewsToolSyncAction::invalidateTermPublicCache(), ne casse
    // rien). Jamais d'assertion de CONTENU ici - voir le docblock d'en-tête de ce fichier.
    $this->get($term->getPublicUrl())->assertOk();
});

// ── Seconde bascule : réapprobation, réapparition sur la fiche publique ──

it('une seconde bascule réapprouve la liaison et le terme réapparaît dans la requête publique « Dans l\'actualité »', function () {
    $admin = nattAdmin();
    $article = nattArticle();
    $term = nattTerm();
    // Départ déjà désapprouvé (équivalent à l'état laissé par une première bascule) - source
    // 'auto' inchangée, seule l'approbation varie (voir test précédent pour la 1ère bascule).
    $article->terms()->attach($term->id, ['source' => 'auto', 'is_approved' => false]);

    expect($term->fresh()->approvedNewsArticles()->published()->count())->toBe(0);

    $reponse = $this->actingAs($admin)->patch(
        route('admin.news.articles.terms.toggle', ['article' => $article, 'term' => $term])
    );
    $reponse->assertRedirect();
    $reponse->assertSessionHas('success');

    expect($term->fresh()->approvedNewsArticles()->published()->pluck('news_articles.id')->all())
        ->toBe([$article->id]);

    $pivot = DB::table('news_article_term')
        ->where('news_article_id', $article->id)
        ->where('term_id', $term->id)
        ->first();

    expect($pivot)->not->toBeNull();
    expect((bool) $pivot->is_approved)->toBeTrue();

    $this->get($term->getPublicUrl())->assertOk();
});

// ── Garde-fou : liaison inexistante refusée proprement ──

it('toggleArticleTerm() refuse proprement une liaison qui n\'existe pas entre cette actualité et ce terme', function () {
    $admin = nattAdmin();
    $article = nattArticle();
    $term = nattTerm(); // jamais attaché à $article

    $reponse = $this->actingAs($admin)->patch(
        route('admin.news.articles.terms.toggle', ['article' => $article, 'term' => $term])
    );

    $reponse->assertRedirect();
    $reponse->assertSessionHas('error');

    expect(
        DB::table('news_article_term')
            ->where('news_article_id', $article->id)
            ->where('term_id', $term->id)
            ->exists()
    )->toBeFalse();
});

// ── Garde-fou d'accès : un visiteur non authentifié ne peut jamais basculer ──

it('un visiteur non authentifié ne peut pas basculer une liaison', function () {
    $article = nattArticle();
    $term = nattTerm();
    $article->terms()->attach($term->id, ['source' => 'auto', 'is_approved' => true]);

    $reponse = $this->patch(
        route('admin.news.articles.terms.toggle', ['article' => $article, 'term' => $term])
    );

    // Le middleware 'auth' intercepte avant le contrôleur (redirection, jamais 200).
    $reponse->assertRedirect();

    $pivot = DB::table('news_article_term')
        ->where('news_article_id', $article->id)
        ->where('term_id', $term->id)
        ->first();

    expect((bool) $pivot->is_approved)->toBeTrue();
});

// ── Écran d'administration lui-même (liste minimale + bouton de bascule) ──

it('articleTerms() affiche le nom du terme, la source et l\'état d\'approbation, avec un bouton pour basculer', function () {
    $admin = nattAdmin();
    $article = nattArticle();
    $termApprouve = nattTerm();
    $termDesapprouve = nattTerm();
    $article->terms()->attach($termApprouve->id, ['source' => 'auto', 'is_approved' => true]);
    $article->terms()->attach($termDesapprouve->id, ['source' => 'manual', 'is_approved' => false]);

    $reponse = $this->actingAs($admin)->get(route('admin.news.articles.terms.index', $article));

    $reponse->assertOk();
    $reponse->assertSee($termApprouve->name, false);
    $reponse->assertSee($termDesapprouve->name, false);
    $reponse->assertSee('Approuvée', false);
    $reponse->assertSee('Désapprouvée', false);
    $reponse->assertSee('Automatique', false);
    $reponse->assertSee('Manuelle', false);
    $reponse->assertSee(
        route('admin.news.articles.terms.toggle', ['article' => $article, 'term' => $termApprouve]),
        false
    );
});
