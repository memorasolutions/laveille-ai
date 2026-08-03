<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 134 (2026-07-30) : le wizard renvoyait vers la mauvaise page.
//
// La barre de sauvegarde du wizard affiche, aux utilisateurs connectés, une seule invitation à
// retrouver leur travail. Elle pointait vers /user/saved, la page GÉNÉRIQUE tous-outils, qui pour
// un prompt n'expose qu'un nom, un aperçu de 80 caractères et une date (UserSavedController).
//
// Or c'est /user/prompts qui est la bibliothèque dédiée construite par cette refonte : recherche,
// étiquettes, favoris, duplication, réutilisation, panneau « Mon profil ». Le lien la contournait.
//
// Ce n'était pas une hésitation de conception : 665 lignes plus bas, DANS LE MÊME FICHIER, le
// commentaire de la section Historique dit « visible seulement pour les non-connectés, les
// connectés ont "Mes prompts" ». L'intention était donc déjà écrite ; seul le lien n'avait pas
// suivi. Et aucun autre lien vers /user/prompts n'existait dans le wizard - le seul se trouvait
// dans le menu global du compte, une zone d'écran distincte, non contextuelle.
//
// Correctif : le lien pointe vers la bibliothèque dédiée, et son libellé NOMME la page d'arrivée
// (« Mes prompts »), au lieu du vague « vos sauvegardes » qui décrivait justement l'autre page.

it('points the wizard to the dedicated library, not the generic saved page (round 134)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain('<a href="{{ route(\'user.prompts.index\') }}"');
    // L'ancien lien générique ne doit plus exister dans ce fichier.
    expect($blade)->not->toContain("route('user.saved') }}?type=prompt");
});

it('names the destination in the link label (round 134)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->toContain("{{ __('Retrouvez-les dans') }} <a href=\"{{ route('user.prompts.index') }}\"");
    expect($blade)->toContain(">{{ __('Mes prompts') }}</a>");
});

it('has both new strings translated (round 134)', function () {
    $fr = json_decode(file_get_contents(lang_path('fr.json')), true);
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    expect($fr)->toHaveKey('Retrouvez-les dans');
    expect($en)->toHaveKey('Retrouvez-les dans');
    expect($en['Retrouvez-les dans'])->toBe('Find them in');

    // Invariant du round 117 : toute clé française doit exister en anglais.
    expect(array_diff_key($fr, $en))->toBeEmpty();
});

it('reaches a real, working library page (round 134)', function () {
    $user = User::factory()->create();

    // Le lien doit mener à une page qui répond vraiment - pas seulement à une route déclarée.
    $this->actingAs($user)->get(route('user.prompts.index'))->assertOk();
});

it('renders the wizard after the round 134 fix (real page)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk();
});
