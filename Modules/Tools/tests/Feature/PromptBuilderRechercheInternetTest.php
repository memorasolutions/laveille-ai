<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Tâche 2026-08-12 : 3 nouveaux verbes de recherche + date du jour injectée par le serveur +
// champ Zones géographiques (comportement réel couvert côté JS, voir
// tests/js/constructeur-prompts-recherche-internet.test.cjs - style d'inspection de source, même
// convention que le reste de la suite constructeur-prompts, cf. PromptBuilderContextVariablesGuestHistoryTest.php).

function makeRechercheInternetTool(): void
{
    Tool::query()->updateOrInsert(
        ['slug' => 'constructeur-prompts'],
        [
            'name' => 'Constructeur de prompts',
            'description' => 'Test',
            'icon' => '✨',
            'is_active' => true,
            'is_under_construction' => false,
            'category' => 'productivite',
        ]
    );
}

it('injects the 3 new search verbs into window.promptBuilderConfig.verbs, after the 14 existing verbs, none removed or reordered', function () {
    makeRechercheInternetTool();
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    preg_match('/verbs:\s*(\[.*?\]),\s*\n/s', $html, $matches);
    expect($matches)->toHaveCount(2);
    $verbs = json_decode($matches[1], true);
    expect($verbs)->not->toBeNull();

    $expectedExisting = ['Rédige', 'Analyse', 'Crée', 'Génère', 'Explique', 'Compare', 'Résume', 'Traduis', 'Optimise', 'Évalue', 'Développe', 'Conçois', 'Planifie', 'Diagnostique'];
    $expectedNew = ['Recherche', 'Recherche sur Internet, en priorisant les sites officiels et pertinents', 'Recherche en profondeur, Internet inclus'];

    expect(array_slice($verbs, 0, 14))->toBe($expectedExisting);
    expect(array_slice($verbs, 14))->toBe($expectedNew);
    expect($verbs)->toHaveCount(17);
});

it('exposes today.long and today.iso in window.promptBuilderConfig, rendered by the server via format_date()', function () {
    makeRechercheInternetTool();
    $user = User::factory()->create();

    $this->travelTo(\Carbon\Carbon::parse('2026-08-12 10:00:00', 'America/Toronto'));

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('today: { long:');
    preg_match('/today:\s*\{\s*long:\s*"([^"]*)",\s*iso:\s*"([^"]*)"\s*\}/', $html, $matches);
    expect($matches)->toHaveCount(3);
    // json_encode echappe les caracteres accentues en \uXXXX (JSON_UNESCAPED_UNICODE non applique
    // a ce endroit precis du Blade, meme comportement que personas/verbs/audiences juste au-dessus
    // - voir le test round 74 equivalent) : on decode la valeur extraite plutot que de chercher le
    // texte accentue litteral dans le HTML brut.
    $long = json_decode('"'.$matches[1].'"');
    $iso = json_decode('"'.$matches[2].'"');
    expect($iso)->toBe('2026-08-12');
    expect($long)->toContain('août');
    expect($long)->toContain('2026');

    $this->travelBack();
});

it('renders the conditional Zones geographic field markup (chip pattern reused, never a new chip style)', function () {
    makeRechercheInternetTool();
    $user = User::factory()->create();

    $html = $this->actingAs($user)->get('/outils/constructeur-prompts')->assertOk()->getContent();

    expect($html)->toContain('id="cpZoneInput"');
    expect($html)->toContain('x-model="zoneInput"');
    expect($html)->toContain('x-show="isSearchVerbActive"');
    expect($html)->toContain('addZoneFromInput()');
    expect($html)->toContain('handleZonePaste($event)');
    expect($html)->toContain('@keydown.enter.prevent="addZoneFromInput()"');
    expect($html)->toContain('removeZone(zIdx)');
    // Le champ réutilise le pattern .ct-chip déjà établi (Format de sortie / Audience) - jamais un
    // 3e style de pastille créé pour cette fonctionnalité.
    expect(substr_count($html, 'class="ct-chip-row"'))->toBeGreaterThanOrEqual(3);
});

it('does NOT translate the 3 new search verbs - they remain raw French values injected into the always-French generated prompt template (lock-in, same rule as round 74)', function () {
    makeRechercheInternetTool();
    $user = User::factory()->create();

    $html = $this->actingAs($user)->withSession(['locale' => 'en'])->get('/outils/constructeur-prompts')->assertOk()->getContent();

    preg_match('/verbs:\s*(\[.*?\]),\s*\n/s', $html, $matches);
    expect($matches)->toHaveCount(2);
    $verbs = json_decode($matches[1], true);
    expect($verbs)->toContain('Recherche');
    expect($verbs)->toContain('Recherche sur Internet, en priorisant les sites officiels et pertinents');
    expect($verbs)->toContain('Recherche en profondeur, Internet inclus');
});
