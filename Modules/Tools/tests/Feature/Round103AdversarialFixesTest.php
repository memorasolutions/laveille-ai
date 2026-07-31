<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 103 (2026-07-27) : passe adversariale fraîche après le lot round 102 (danger conditionnel
// sur la branche alpineClick + branche <a> de action-menu.blade.php). 1 manque réel corrigé :
//
// Modules/Core/resources/views/components/action-menu.blade.php - les 4 branches (alpineClick,
// wireClick, formulaire POST/DELETE, lien <a>) codaient toutes #DC2626 pour tout item danger=true.
// Ce même contraste (~4,83:1 sur blanc, AA seulement) avait déjà été identifié et corrigé à 3
// reprises dans ce même périmètre de fichiers (round 82 : icône favori ; round 88 : liens "Effacer
// les filtres" ; round 89 : astérisques requis), systématiquement remplacé par #991B1B (~8,3:1,
// AAA - token établi charte.css:1009 .alert-danger). Le round 102 a modifié ce fichier précis
// (branche alpineClick) mais uniquement pour appliquer la logique conditionnelle danger déjà
// présente ailleurs - sans jamais réévaluer si #DC2626 lui-même respecte le seuil AAA 7:1 exigé
// par la charte du projet. Résultat concret : l'action "Supprimer" de /user/prompts (Mes prompts)
// affichait un rouge sous le seuil AAA. Fixé : les 4 branches utilisent désormais #991B1B (texte)
// + #FEF2F2 (fond survol, même paire que .alert-danger) au lieu de #DC2626/#FEF2F2.

it('uses the AAA-contrast #991B1B token (not #DC2626) for danger items in all 4 action-menu branches (round 103)', function () {
    $blade = file_get_contents(base_path('Modules/Core/resources/views/components/action-menu.blade.php'));

    expect($blade)->not->toContain('#DC2626');
    expect(substr_count($blade, "'#991B1B' : '#374151'"))->toBe(4);
});

it('renders /user/prompts (Mes prompts) correctly after the round 103 contrast fix (real page, no regression)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/user/prompts')->assertOk();
});

it('renders the constructeur-prompts page correctly after the round 103 fix (real page, no regression)', function () {
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
