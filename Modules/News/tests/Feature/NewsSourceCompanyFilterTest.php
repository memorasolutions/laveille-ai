<?php

declare(strict_types=1);

/**
 * Filtre "compagnie d'IA" dans l'écran de composition (2026-08-29, demande du fondateur). La
 * table news_sources n'avait aucun moyen de dire qu'une source est officielle ni de la rattacher
 * à une compagnie - c'était le seul vrai blocage, pas l'écran (voir
 * Modules\News\Http\Controllers\Admin\NewsCompositionController et
 * public/assets/admin/news-article-picker.js, qui savent déjà filtrer côté client dès que le
 * champ existe dans la charge utile JSON).
 *
 * Trois choses verrouillées ici, chacune choisie parce qu'un échec silencieux y ferait mal :
 *  - la migration ajoute is_official/company de façon RÉELLEMENT réversible (down() les retire,
 *    up() les restaure) sans perdre une ligne pré-existante - jamais une paire up()/down() vide
 *    qui donnerait une fausse impression de rollback disponible ;
 *  - le peuplement des 11 sources officielles est idempotent ET ne touche JAMAIS
 *    name/url/category/language/active d'une source déjà présente sous la même URL - seuls
 *    company/is_official sont posés dessus, qu'elle ait été ajoutée par ce lot ou avant lui ;
 *  - candidates() (le contrat qui alimente le filtre côté client) expose bien source_company et
 *    source_is_official pour chaque actualité, y compris la valeur neutre attendue quand la
 *    source n'est pas taggée.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\News\Database\Seeders\OfficialCompanySourcesSeeder;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function ocsAdmin(): \App\Models\User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

function ocsArticle(NewsSource $source, array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => "Titre ocs {$i}",
        'guid' => "guid-ocs-{$suffix}",
        'url' => "https://exemple.com/ocs-{$suffix}",
        'description' => '',
        'summary' => "Résumé ocs {$i}",
        'slug' => "ocs-{$suffix}",
        'pub_date' => now()->subMinutes($i),
        'is_published' => false,
        'seo_status' => 'index',
    ], $overrides));
}

// ── Migration : additive, réversible, jamais de perte ──────────────────────────────────────

it('ajoute is_official (booléen, défaut false) et company (chaîne, nullable) sur news_sources', function () {
    expect(Schema::hasColumn('news_sources', 'is_official'))->toBeTrue()
        ->and(Schema::hasColumn('news_sources', 'company'))->toBeTrue();

    $source = NewsSource::create([
        'name' => 'Source neutre',
        'url' => 'https://exemple.com/ocs-defaut',
        'language' => 'fr',
        'active' => true,
    ]);

    expect($source->fresh()->is_official)->toBeFalse()
        ->and($source->fresh()->company)->toBeNull();
});

it('est réversible : down() retire exactement ce qu\'up() a ajouté sans perdre la ligne existante, et up() restaure les deux colonnes', function () {
    $preexistante = NewsSource::create([
        'name' => 'Source avant rollback',
        'url' => 'https://exemple.com/ocs-avant-rollback',
        'category' => 'general',
        'language' => 'fr',
        'active' => true,
        'company' => 'Test',
        'is_official' => true,
    ]);

    $migration = require base_path('Modules/News/database/migrations/2026_08_29_010000_add_company_fields_to_news_sources.php');

    $migration->down();
    expect(Schema::hasColumn('news_sources', 'is_official'))->toBeFalse()
        ->and(Schema::hasColumn('news_sources', 'company'))->toBeFalse()
        ->and(NewsSource::find($preexistante->id))->not->toBeNull();

    $migration->up();
    expect(Schema::hasColumn('news_sources', 'is_official'))->toBeTrue()
        ->and(Schema::hasColumn('news_sources', 'company'))->toBeTrue();

    // La ligne d'origine et SES colonnes d'origine survivent intactes au rollback - c'est ce que
    // "n'efface aucune donnée existante" garantit. Les deux colonnes RAJOUTÉES par cette même
    // migration reviennent à leur défaut neutre : attendu, down() en a fait un vrai DROP COLUMN,
    // il n'y avait plus rien à restaurer pour up() - ce n'est pas une perte de donnée d'origine.
    $revenue = NewsSource::find($preexistante->id);
    expect($revenue)->not->toBeNull()
        ->and($revenue->name)->toBe('Source avant rollback')
        ->and($revenue->url)->toBe('https://exemple.com/ocs-avant-rollback')
        ->and($revenue->category)->toBe('general')
        ->and($revenue->active)->toBeTrue()
        ->and($revenue->is_official)->toBeFalse()
        ->and($revenue->company)->toBeNull();
});

// ── Peuplement : idempotent, jamais de doublon, jamais d'écrasement d'une source existante ──

// ONZE, pas douze : Qwen (Alibaba) a été écartée le 2026-08-29 après mesure réelle - son flux
// répond mais n'a rien publié depuis 340 jours. Le compte est volontairement codé en dur : il
// attrape un ajout silencieux, et surtout il oblige à relire cette note avant de le changer.
// Une source ne s'ajoute ici QU'APRÈS avoir été vérifiée par requête réelle (code, entrées, date).
it('peuple les 11 sources officielles vérifiées, avec leur compagnie', function () {
    (new OfficialCompanySourcesSeeder())->run();

    expect(NewsSource::where('is_official', true)->count())->toBe(11);

    $compagnies = NewsSource::where('is_official', true)->pluck('company')->sort()->values()->all();
    foreach (['OpenAI', 'Google DeepMind', 'Google AI', 'Hugging Face', 'EleutherAI', 'NIST'] as $attendue) {
        expect($compagnies)->toContain($attendue);
    }

    // Le flux muet ne doit jamais revenir par une réintroduction distraite.
    expect($compagnies)->not->toContain('Alibaba');
});

it('rejouer le peuplement une 2e fois ne crée aucun doublon (idempotent)', function () {
    (new OfficialCompanySourcesSeeder())->run();
    (new OfficialCompanySourcesSeeder())->run();

    expect(NewsSource::count())->toBe(11)
        ->and(NewsSource::where('is_official', true)->count())->toBe(11);
});

it('ne touche jamais name/url/category/language/active d\'une source déjà présente sous la même URL - seulement company/is_official', function () {
    $preexistante = NewsSource::create([
        'name' => 'Nom choisi avant ce lot',
        'url' => 'https://deepmind.google/blog/rss.xml',
        'category' => 'analysis',
        'language' => 'fr',
        'active' => false,
    ]);

    (new OfficialCompanySourcesSeeder())->run();

    $retaguee = $preexistante->fresh();
    expect($retaguee->name)->toBe('Nom choisi avant ce lot')
        ->and($retaguee->category)->toBe('analysis')
        ->and($retaguee->language)->toBe('fr')
        ->and($retaguee->active)->toBeFalse()
        ->and($retaguee->company)->toBe('Google DeepMind')
        ->and($retaguee->is_official)->toBeTrue();

    // Aucune ligne dupliquée pour cette URL : la source pré-existante a été retaguée, pas clonée.
    expect(NewsSource::where('url', 'https://deepmind.google/blog/rss.xml')->count())->toBe(1);
});

it('scopeOfficial() ne renvoie que les sources marquées officielles', function () {
    NewsSource::create(['name' => 'Off', 'url' => 'https://exemple.com/ocs-off', 'language' => 'en', 'active' => true, 'is_official' => true, 'company' => 'OpenAI']);
    NewsSource::create(['name' => 'Pas off', 'url' => 'https://exemple.com/ocs-pasoff', 'language' => 'en', 'active' => true]);

    expect(NewsSource::official()->count())->toBe(1);
});

// ── Écran de composition : le contrat qui alimente le filtre côté client ───────────────────

it('candidates() expose la compagnie et le drapeau officiel de la source de chaque actualité', function () {
    $officielle = NewsSource::create([
        'name' => 'Source officielle test',
        'url' => 'https://exemple.com/ocs-officielle',
        'language' => 'en',
        'active' => true,
        'company' => 'OpenAI',
        'is_official' => true,
    ]);
    $neutre = NewsSource::create([
        'name' => 'Source neutre test',
        'url' => 'https://exemple.com/ocs-neutre',
        'language' => 'fr',
        'active' => true,
    ]);

    $articleOfficiel = ocsArticle($officielle);
    $articleNeutre = ocsArticle($neutre);

    $admin = ocsAdmin();
    $reponse = $this->actingAs($admin)->getJson(route('admin.news.composition.candidates'));

    $reponse->assertOk();
    $items = collect($reponse->json('items'))->keyBy('id');

    expect($items[$articleOfficiel->id]['source_company'])->toBe('OpenAI')
        ->and($items[$articleOfficiel->id]['source_is_official'])->toBeTrue()
        ->and($items[$articleNeutre->id]['source_company'])->toBeNull()
        ->and($items[$articleNeutre->id]['source_is_official'])->toBeFalse();
});
