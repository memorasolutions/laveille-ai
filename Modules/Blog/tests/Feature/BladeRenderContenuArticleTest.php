<?php

declare(strict_types=1);

use Modules\Blog\Models\Article;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// Audit du 2026-08-25. Un audit de sécurité a signalé une exécution de code à distance :
// PublicPostController::show() passe le contenu de l'article à Blade::render() dès qu'il contient
// « <x- », et UserArticleController::store() enregistre ce contenu SANS purification, derrière le
// seul middleware `auth`, avec un statut « published » choisi par l'utilisateur.
//
// Ces tests existent pour trancher par la preuve plutôt que par la lecture, et pour verrouiller
// ce qui protège réellement le site. Ils décrivent DEUX faits distincts :
//   1. la page publique est hors de portée d'un utilisateur ordinaire (la chaîne est rompue) ;
//   2. si elle cessait de l'être, le contenu SERAIT exécuté (le danger est réel, seulement latent).
//
// La charge utile est volontairement inoffensive : elle multiplie deux nombres. Si le produit
// apparaît dans la sortie, c'est que du code a été exécuté - aucune commande système n'est
// employée, ici comme ailleurs.

const MARQUEUR_EXECUTION = 'ZZTOP9271';
// Le declencheur du rendu est la simple presence de « <x- » dans le contenu. Un composant
// INEXISTANT ferait lever Blade::render(), le try/catch avalerait l'erreur et le contenu
// resterait intact : la charge doit donc rester compilable pour mesurer le vrai risque.
const CHARGE_BLADE = "<x-\n@php echo 'ZZTOP'.'9271'; @endphp";

it('ne rend PAS publiquement un article publie par un utilisateur ordinaire', function () {
    $utilisateur = \App\Models\User::factory()->create();

    // Exactement ce que fait UserArticleController::store() : aucun published_at n'est fourni.
    $article = Article::create([
        'user_id' => $utilisateur->id,
        'title' => 'Article de controle audit',
        'slug' => 'article-de-controle-audit',
        'content' => CHARGE_BLADE,
        'status' => 'published',
    ]);

    expect($article->published_at)->toBeNull(
        'Si published_at devient renseigné automatiquement, la protection actuelle disparaît '
        .'et le contenu non purifié atteint Blade::render(). Voir le second test.'
    );

    $reponse = $this->get('/blog/article-de-controle-audit');

    // 404 attendu : scopePublished() exige published_at <= now(), et NULL ne satisfait jamais
    // cette comparaison. C'est CE champ, et lui seul, qui protège aujourd'hui la page.
    expect($reponse->status())->toBe(404);
    expect($reponse->getContent())->not->toContain(MARQUEUR_EXECUTION);
});

// Seconde garde, indépendante de la première. Même rendu publiable, la page n'exécute PAS le
// contenu : le modèle expose safeContent() qui applique Purifier::clean(..., 'article')
// (Article.php:211-217). Vérifié en conditions réelles le 2026-08-25.
//
// À retenir si ce test casse un jour : Blade::render() exécute RÉELLEMENT le @php quand on
// l'appelle hors de ce contexte (mesuré : un contenu « <x- » suivi de @php produit bien sa
// sortie). Ce ne sont donc pas les gabarits qui sont inoffensifs, ce sont ces deux gardes qui
// tiennent. Si l'une saute, du contenu soumis par un utilisateur devient exécutable.
it('n execute pas le contenu meme quand l article est publiable', function () {
    $utilisateur = \App\Models\User::factory()->create();

    Article::create([
        'user_id' => $utilisateur->id,
        'title' => 'Article de controle audit publiable',
        'slug' => 'article-de-controle-audit-publiable',
        'content' => CHARGE_BLADE,
        'status' => 'published',
        'published_at' => now()->subMinute(),
    ]);

    $reponse = $this->get('/blog/article-de-controle-audit-publiable');

    expect($reponse->status())->toBe(200);
    expect($reponse->getContent())->not->toContain(MARQUEUR_EXECUTION);
});
