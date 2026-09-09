<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Authors\Models\AuthorProfile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Ticket #2380 - le mini-site /@{slug} ne doit pas concurrencer la page auteur du thème.
 *
 * CE TEST A DÉJÀ ÉTÉ VERT SUR UN CAS QUI N'EXISTE PAS. En v1.257.3, il fabriquait un profil
 * au slug « stephane-lapointe » - la clé du fichier lang du thème - alors que la production
 * porte le slug « stephane ». Le test passait, et la balise était pourtant ABSENTE de
 * https://laveille.ai/@stephane, mesurée sur 20 essais après déploiement. D'où la règle que
 * ce fichier applique désormais : les données du test sont celles de la PRODUCTION, relevées,
 * jamais celles qui rendent l'assertion commode.
 *
 * Le contrôle porte les DEUX sens, parce qu'un test qui ne vérifie que la présence de la
 * balise passerait aussi si on la posait sur TOUS les auteurs - ce qui désindexerait les
 * mini-sites d'auteurs tiers, qui eux n'ont aucun doublon.
 */
function creerProfilAuteurPourNoindex(string $slug, string $nom): AuthorProfile
{
    $utilisateur = User::factory()->create([
        'name' => $nom,
        'email' => 'noindex-'.Str::random(6).'@example.com',
    ]);

    return AuthorProfile::create([
        'user_id' => $utilisateur->id,
        'slug' => $slug,
        'tier' => 'free',
    ]);
}

/** Le nom déclaré par le thème pour la seule page auteur qu'il publie. Relevé, pas supposé. */
function nomDeLAuteurDuTheme(): string
{
    $pages = (array) trans('fronttheme::authors');

    expect($pages['stephane-lapointe']['name'] ?? null)->toBeString();

    return $pages['stephane-lapointe']['name'];
}

it('retire de l index le mini-site dont la PERSONNE a DEJA une page auteur dans le theme', function () {
    // Le couple réel de production, mesuré le 2026-09-09 : le mini-site est servi sous le slug
    // « stephane », tandis que la page du thème vit sous la clé « stephane-lapointe ». Les deux
    // identifiants d'URL DIFFÈRENT ; seule la personne est commune.
    creerProfilAuteurPourNoindex('stephane', nomDeLAuteurDuTheme());

    $reponse = $this->get('/@stephane');

    $reponse->assertOk();
    $reponse->assertSee('<meta name="robots" content="noindex, follow">', false);
});

it('mord meme quand le nom du profil est saisi SANS ses accents', function () {
    // La base a porté « Stephane Lapointe » sans accent jusqu'au 2026-09-09. La donnée est
    // corrigée, mais un nom se ressaisit : la comparaison ne doit pas dépendre de l'accent.
    creerProfilAuteurPourNoindex('stephane', 'stephane   LAPOINTE');

    $reponse = $this->get('/@stephane');

    $reponse->assertOk();
    $reponse->assertSee('<meta name="robots" content="noindex, follow">', false);
});

it('laisse indexable le mini-site d un auteur qui n a AUCUNE page dans le theme', function () {
    $slugSansDoublon = 'auteur-tiers-'.strtolower(Str::random(6));
    $nomSansDoublon = 'Alpha Preuve '.Str::random(6);

    // Témoin négatif : personne de ce nom n'a de page côté thème.
    $nomsDuTheme = collect((array) trans('fronttheme::authors'))
        ->filter(fn ($page) => is_array($page) && isset($page['name']))
        ->map(fn ($page) => $page['name'])
        ->values()
        ->all();
    expect($nomsDuTheme)->not->toContain($nomSansDoublon);

    creerProfilAuteurPourNoindex($slugSansDoublon, $nomSansDoublon);

    $reponse = $this->get('/@'.$slugSansDoublon);

    $reponse->assertOk();
    $reponse->assertDontSee('name="robots"', false);
});
