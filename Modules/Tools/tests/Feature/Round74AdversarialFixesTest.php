<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 74 (2026-07-27) : passe adversariale fraîche, périmètre transitif complet du graphe de
// constructeur-prompts.blade.php + Article::getPublicUrl(). 2 manques réels confirmés
// INDÉPENDAMMENT (jamais sur la seule parole du sous-agent) :
//
// 1. P0 site-wide (pas seulement constructeur-prompts) : Modules/FrontTheme/resources/views/
//    partials/header.blade.php (rendu sur CHAQUE page publique via le layout) faisait
//    route('blog.show', $latestArticle->slug) sans vérifier que le slug existe pour la locale
//    courante. Reproduit via tinker : Article::latest('published_at')->first()->getTranslations
//    ('slug') === ['fr_CA' => '...'] (aucune clé 'en') → app()->setLocale('en') →
//    $article->slug === '' → route('blog.show', '') lève UrlGenerationException (500).
//    Confirmé indépendamment sur home.blade.php (11 occurrences du même pattern, hero1-4,
//    highlight, recent, sponsored). Fixé via Article::getPublicUrl() (repli manuel locale
//    courante -> 'fr_CA' -> 1re traduction disponible, MÊME pattern que Tool::getPublicUrl(),
//    P0 2026-07-19, Modules/Directory - voir ToolPublicUrlLocaleFallbackTest.php) + migration des
//    12 sites d'appel (header.blade.php + 11 dans home.blade.php) vers $article->getPublicUrl().
//
// 2. PARTIELLEMENT confirmé (le sous-agent a sur-généralisé) : les 9 cartes d'objectif de l'étape
//    1 ($defaultTaskCards) avaient 'label'/'description' en dur, jamais passés par __(). CES DEUX
//    champs sont du texte d'affichage PUR (jamais injectés dans le prompt généré - vérifié via
//    grep : selectedTaskLabel et c.description ne sont utilisés que côté UI, x-text). Fixés.
//    En revanche $defaultPersonas[].label, $defaultAudiences[].label et $defaultVerbs NE sont
//    PAS des manques - ce sont des valeurs injectées BRUTES dans le gabarit du prompt généré
//    (this.personaText → "Tu es un(e) [label] avec une expertise...", this.verb → "Ta tâche :
//    [verb] ...", vérifié dans constructeur-prompts-core.js lignes ~227/247/251). Le gabarit du
//    prompt généré est TOUJOURS en français, quel que soit le locale du site (le champ
//    "language" du wizard ajoute une instruction de langue de RÉPONSE, il ne traduit pas le
//    gabarit) - les traduire casserait le prompt généré (grammaire mixte FR/EN). Un test négatif
//    ci-dessous verrouille cette décision pour qu'un futur round ne "corrige" pas ça en régression.

it('Article::getPublicUrl() ne plante pas quand le slug n\'existe que pour une autre locale (round 74)', function () {
    // Locale FR_CA à la création : Article::boot() auto-génère le slug pour la locale COURANTE
    // uniquement (creating() : if empty($model->slug) -> Str::slug($model->title)) - on ne bascule
    // en EN qu'APRÈS la création, pour reproduire fidèlement "aucune traduction 'en' du tout".
    config(['app.locale' => 'fr_CA']);
    $article = Article::factory()->published()->create();

    config(['app.locale' => 'en']);

    expect($article->getTranslation('slug', 'en', false))->toBe('');
    expect($article->getPublicUrl())->toContain($article->getTranslation('slug', 'fr_CA', false));
});

it('home page does not 500 in EN locale when the latest article has no EN slug translation (round 74)', function () {
    $article = Article::factory()->published()->create();
    $article->setTranslation('slug', 'fr_CA', 'article-home-fallback-round74');
    $article->save();

    $response = $this->withSession(['locale' => 'en'])->get('/');

    $response->assertOk();
});

it('constructeur-prompts page does not 500 in EN locale via the shared header partial (round 74)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $article = Article::factory()->published()->create();
    $article->setTranslation('slug', 'fr_CA', 'article-cp-fallback-round74');
    $article->save();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->withSession(['locale' => 'en'])->get('/outils/constructeur-prompts');

    $response->assertOk();
});

it('has English translations for the 9 task-card labels and descriptions (round 74)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        'Rédiger un texte', 'Un article, un courriel, une publication...',
        'Résumer un contenu', 'Condenser un texte, un rapport, une réunion...',
        'Trouver des idées', 'Brainstormer des angles, des options, des titres...',
        'Analyser ou comparer', 'Étudier des données, comparer des options...',
        'Apprendre ou comprendre', 'Faire expliquer un sujet clairement, étape par étape...',
        'Traduire un texte', "Passer d'une langue à une autre...",
        'Planifier ou organiser', 'Un projet, une stratégie, un horaire...',
        'Écrire ou déboguer du code', 'Créer, corriger ou expliquer du code...',
        'Autre chose', 'Je préfère tout choisir moi-même',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($en[$key])->not->toBe($key);
    }
});

it('renders task-card labels and descriptions translated in EN locale on the constructeur-prompts page (round 74)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $html = $this->actingAs($user)->withSession(['locale' => 'en'])->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('Write a text');
    expect($html)->toContain('An article, an email, a post...');
    expect($html)->not->toContain('Rédiger un texte');
});

it('does NOT translate persona/verb/audience labels - they are raw values injected into the always-French generated prompt template (round 74, lock-in negative test)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $html = $this->actingAs($user)->withSession(['locale' => 'en'])->get('/outils/constructeur-prompts')->assertOk()->getContent();

    // personas/verbs sont injectés via @json() dans window.promptBuilderConfig - json_encode()
    // échappe les caractères accentués en \uXXXX par défaut (pas de JSON_UNESCAPED_UNICODE), donc
    // on extrait et décode chaque tableau JSON individuellement (l'objet JS englobant n'est PAS du
    // JSON valide - clés non quotées) plutôt que de chercher la chaîne accentuée littérale dans le
    // HTML brut. Ces valeurs doivent rester en français même en locale EN - les traduire casserait
    // le gabarit du prompt généré ("Tu es un(e) Rédacteur web professionnel...", "Ta tâche : Rédige...").
    preg_match('/personas:\s*(\[.*?\]),\s*\n\s*verbs:\s*(\[.*?\]),/s', $html, $matches);
    expect($matches)->toHaveCount(3);

    $personas = json_decode($matches[1], true);
    $verbs = json_decode($matches[2], true);
    expect($personas)->not->toBeNull();
    expect($verbs)->not->toBeNull();
    expect(collect($personas)->pluck('label'))->toContain('Rédacteur web professionnel');
    expect($verbs)->toContain('Rédige');
});
