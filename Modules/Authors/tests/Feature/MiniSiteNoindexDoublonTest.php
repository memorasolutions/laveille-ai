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
 * Le contrôle porte les DEUX sens, parce qu'un test qui ne vérifie que la présence de la
 * balise passerait aussi si on la posait sur TOUS les auteurs - ce qui désindexerait les
 * mini-sites d'auteurs tiers, qui eux n'ont aucun doublon.
 */
function creerProfilAuteurPourNoindex(string $slug): AuthorProfile
{
    $utilisateur = User::factory()->create([
        'email' => 'noindex-'.Str::random(6).'@example.com',
    ]);

    return AuthorProfile::create([
        'user_id' => $utilisateur->id,
        'slug' => $slug,
        'tier' => 'free',
    ]);
}

it('retire de l index le mini-site dont le slug a DEJA une page auteur dans le theme', function () {
    // Le fichier de traduction du thème déclare bien ce slug : c'est la source de vérité,
    // la même que lit FrontTheme\Http\Controllers\AuthorController::show().
    expect(isset(((array) trans('fronttheme::authors'))['stephane-lapointe']))->toBeTrue();

    creerProfilAuteurPourNoindex('stephane-lapointe');

    $reponse = $this->get('/@stephane-lapointe');

    $reponse->assertOk();
    $reponse->assertSee('<meta name="robots" content="noindex, follow">', false);
});

it('laisse indexable le mini-site d un auteur qui n a AUCUNE page dans le theme', function () {
    $slugSansDoublon = 'auteur-tiers-'.strtolower(Str::random(6));

    // Témoin négatif : ce slug n'existe pas côté thème, donc aucune page ne le concurrence.
    expect(isset(((array) trans('fronttheme::authors'))[$slugSansDoublon]))->toBeFalse();

    creerProfilAuteurPourNoindex($slugSansDoublon);

    $reponse = $this->get('/@'.$slugSansDoublon);

    $reponse->assertOk();
    $reponse->assertDontSee('name="robots"', false);
});
