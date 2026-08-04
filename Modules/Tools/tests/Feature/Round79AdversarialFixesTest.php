<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 79 (2026-07-27) : passe adversariale fraîche après le lot round 78 (JS cartes perso +
// JSON-LD SoftwareApplication). 1 manque réel corrigé :
//
// 1. public/assets/tools/constructeur-prompts/constructeur-prompts-core.js:224-242 (get
//    promptSummary), affiché dans constructeur-prompts.blade.php:564-567 - le résumé "Voici ce
//    qui sera envoyé à l'IA :" (prévisualisation en langage courant lue par l'HUMAIN, distincte
//    du get prompt()/get metaPrompt() qui restent en français par design car injectés dans le
//    texte envoyé au LLM) construisait ses phrases entièrement en français codé en dur, jamais
//    pontées via window.promptBuilderConfig.i18n. Fixé en ajoutant 8 clés i18n (summaryRole,
//    summaryRoleArticle, summaryAction, summarySubject, summaryAudience, summaryTone,
//    summaryFormat, summaryLength) + repli français côté JS, même pattern que rounds 76-78.
//
// Rappel des 2 findings CONNUS et délibérément hors-scope (ne pas re-signaler, voir en-tête
// Round78AdversarialFixesTest.php) : modèle Tool non traduisible (17 lignes, gap architectural
// site-wide préexistant) et 'inLanguage' => 'fr-CA' en dur (convention appliquée site-wide).
//
// Round 156 (2026-08-03, simulation E2E) : "Elle va " + verbe déjà à l'impératif donnait une
// faute d'accord ("Elle va rédige"). Le libellé source devient "Tâche demandée : " (n'exige plus
// de conjuguer le verbe) - clé i18n renommée en conséquence, assertions ci-dessous mises à jour.

it('has English translations for the 8 promptSummary phrase fragments (round 79)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $expected = [
        "L'IA va se comporter comme " => 'The AI will act as ',
        'un(e) ' => '',
        'Tâche demandée : ' => 'Task: ',
        'Sujet : ' => 'Subject: ',
        'Le résultat sera adapté pour : ' => 'The result will be tailored for: ',
        'Ton : ' => 'Tone: ',
        'Présenté sous forme de : ' => 'Presented as: ',
        'Longueur visée : ' => 'Target length: ',
    ];

    foreach ($expected as $fr => $en_expected) {
        expect($en)->toHaveKey($fr);
        expect($en[$fr])->toBe($en_expected);
    }
});

it('the JS file falls back to window.promptBuilderConfig.i18n for the promptSummary fragments (round 79)', function () {
    $js = file_get_contents(public_path('assets/tools/constructeur-prompts/constructeur-prompts-core.js'));

    expect($js)->toContain("i18nSummary.summaryRole || 'L\\'IA va se comporter comme '");
    expect($js)->toContain("i18nSummary.summaryAction || 'Tâche demandée : '");
    expect($js)->toContain("i18nSummary.summarySubject || 'Sujet : '");
    expect($js)->toContain("i18nSummary.summaryAudience || 'Le résultat sera adapté pour : '");
    expect($js)->toContain("i18nSummary.summaryTone || 'Ton : '");
    expect($js)->toContain("i18nSummary.summaryFormat || 'Présenté sous forme de : '");
    expect($js)->toContain("i18nSummary.summaryLength || 'Longueur visée : '");
});

it('injects the 8 promptSummary i18n keys translated on the real page in EN locale (round 79)', function () {
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

    expect($html)->toContain('summaryRole: "The AI will act as "');
    expect($html)->toContain('summaryAction: "Task: "');
    expect($html)->toContain('summarySubject: "Subject: "');
    expect($html)->toContain('summaryAudience: "The result will be tailored for: "');
    expect($html)->toContain('summaryTone: "Tone: "');
    expect($html)->toContain('summaryFormat: "Presented as: "');
    expect($html)->toContain('summaryLength: "Target length: "');
});
