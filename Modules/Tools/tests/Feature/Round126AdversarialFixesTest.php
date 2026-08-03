<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 126 (2026-07-30) : Mistral était une destination sans issue.
//
// Le sélecteur « Destination » propose 4 cibles (Canvas ChatGPT, Artefact Claude, Canvas Gemini,
// Espace de travail Mistral) et le prompt généré nomme la cible choisie en toutes lettres
// (core.js:293, canvasNames). Mais la rangée « Ouvrir dans » n'offrait que ChatGPT / Claude /
// Perplexity / Gemini : l'utilisateur qui choisissait Mistral obtenait un prompt disant « crée un
// nouveau espace de travail de Mistral » sans aucun moyen d'y aller en un clic. openIn('mistral')
// serait de surcroît tombé dans `default: return;` - sortie parfaitement silencieuse.
//
// Objection examinée et écartée : « les deux listes sont des concepts différents, Perplexity est
// dans les boutons sans être une destination ». C'est vrai dans ce sens-là (on peut exécuter un
// prompt ailleurs que dans un canvas), mais l'inverse ne tient pas : TOUTES les autres destinations
// avaient leur bouton. Mistral était la seule exception, sans explication ni repli dans l'interface.
//
// Objection examinée et écartée : « Mistral ne pré-remplit pas par URL ». Gemini non plus, et il a
// pourtant son bouton depuis toujours (core.js : baseUrl sans paramètre + message « colle-le »).
// Ce n'était donc pas le motif de l'absence. Mistral suit exactement le même patron.

it('offers Mistral in both open-in rows (round 126)', function () {
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    // Rangée du méta-prompt (BYOA) et rangée du prompt principal.
    expect($blade)->toContain("openIn('mistral', metaPrompt)");
    expect($blade)->toContain("openIn('mistral')");
    expect(substr_count($blade, '>Mistral</button>'))->toBe(2);
});

it('routes mistral to its chat instead of falling through to the silent default (round 126)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain("case 'mistral':");
    expect($js)->toContain("baseUrl = 'https://chat.mistral.ai/chat';");
});

it('tells the user to paste rather than claiming the prompt is already there (round 126)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));
    $blade = file_get_contents(base_path('Modules/Tools/resources/views/public/tools/constructeur-prompts.blade.php'));

    // Sans ce cas, Mistral aurait hérité de openInGeneric (« ouverture de la conversation… »), qui
    // laisserait croire que le prompt est déjà chargé alors qu'il faut le coller.
    expect($js)->toContain("target === 'mistral'");
    expect($js)->toContain('i18n.openInMistral');
    expect($blade)->toContain('openInMistral: @json(');
});

it('never puts the prompt in the mistral URL (round 126)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    // Même contrat que Gemini : pas de pré-remplissage par URL, donc la branche mistral doit
    // précéder le `else if (encodedPrompt.length <= 4000)` qui concatène le prompt à l'URL.
    $posMistral = strpos($js, "} else if (target === 'mistral') {");
    $posConcat = strpos($js, 'url += encodedPrompt;');

    expect($posMistral)->not->toBeFalse();
    expect($posConcat)->not->toBeFalse();
    expect($posMistral)->toBeLessThan($posConcat);
});

it('keeps the four original destinations wired (round 126 non-regression)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain("baseUrl = 'https://chatgpt.com/?q=';");
    expect($js)->toContain("baseUrl = 'https://claude.ai/new?q=';");
    expect($js)->toContain("baseUrl = 'https://www.perplexity.ai/search?q=';");
    expect($js)->toContain("baseUrl = 'https://gemini.google.com/app';");
    // La cible inconnue doit toujours sortir sans rien ouvrir.
    expect($js)->toContain('default:');
});

it('renders the wizard after the round 126 fix (real page)', function () {
    // Même amorçage que les rounds précédents : l'outil doit exister en base et être hors
    // construction, sinon la route répond 503 (gate) au lieu de rendre la vue testée.
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
