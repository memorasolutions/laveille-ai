<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 89 (2026-07-27) : passe adversariale fraîche après le lot round 88 (5 checkboxes + 2 liens
// "Effacer les filtres"). 1 manque réel corrigé :
//
// Les 3 astérisques de champ requis (label "Sur quoi porte votre demande ?", en-têtes des sections
// "Réglages avancés : rôle de l'IA" et "Réglages avancés : verbe d'action") utilisaient encore
// #DC2626 sur du texte normal (ni gros texte AA ni gras ≥18.66px) - contraste ~4,55-4,83:1 selon le
// fond, échouant le seuil AAA 7:1 déjà appliqué au reste de la charte (le round 88 avait corrigé le
// même pattern de couleur mais uniquement dans user/prompts/index.blade.php, en oubliant ces 3
// occurrences du fichier principal du constructeur). Fixé : #DC2626 → #991B1B (~8,3:1 AAA, même
// token déjà établi au round 88) aux 3 emplacements.

it('has WCAG AAA-contrast required-field asterisks in constructeur-prompts (round 89)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    expect($blade)->not->toContain('color: #DC2626');
    expect(substr_count($blade, '<span style="color: #991B1B;">*</span>'))->toBe(3);
});

it('renders the required-field asterisks with AAA contrast on the real page (round 89)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->not->toContain('color: #DC2626');
    expect(substr_count($html, '<span style="color: #991B1B;">*</span>'))->toBe(3);
});
