<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 75 (2026-07-27) : passe adversariale fraîche. Découverte clé : constructeur-prompts est
// ACTUELLEMENT gaté en mode "révision" (is_under_construction=true, construction_mode='revision',
// commit 39cf170f du 2026-07-26) - ce qu'un visiteur non-superadmin voit RÉELLEMENT en ce moment
// à /outils/constructeur-prompts est Modules/Tools/resources/views/public/under-construction.blade.php,
// absent de la liste de périmètre initiale mais bel et bien dans le graphe transitif réel de la
// page publique. 3 manques réels confirmés indépendamment :
//
// 1. Modules/Tools/resources/views/components/octopus.blade.php : $defaultLabels (aria-label de la
//    mascotte, role="img") 100% en dur en français, jamais __(). Composant DRY réutilisé site-wide
//    (pages d'erreur, under-construction, etc.) - portée bien plus large que ce round.
// 2. Modules/Tools/resources/views/public/under-construction.blade.php : @section('title')/
//    @section('meta_description') concatènent $tool->name avec des suffixes FR en dur (jamais
//    __()). Plusieurs aria-label dupliquaient en dur le texte déjà __()-wrappé (aria-label prime
//    sur le texte visible pour le nom accessible, WCAG 4.1.2). En creusant plus loin : TOUT le
//    contenu de cette page (déjà passé par __() dans le Blade) n'avait AUCUNE traduction EN dans
//    lang/en.json - la page entière restait 100% française quelle que soit la locale.
// 3. lang/en.json : 4 aria-label du header partagé (Menu Outils/Annuaire/Apprendre, Ouvrir la
//    recherche) + 1 de search-palette.blade.php (Fermer la recherche) étaient bien enveloppés
//    __() côté Blade mais absents de lang/en.json - retombaient donc sur le FR même en locale EN,
//    sur TOUTE page publique du site.

it('has English translations for the octopus mascot aria-labels (round 75)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        "Octopus curieux, mascotte des Sentiers de l'IA",
        "Octopus, mascotte des Sentiers de l'IA",
        'Octopus célèbre une réussite',
        'Octopus explore avec des outils',
        'Octopus partage une découverte',
        'Octopus, prêt pour le défi',
        'Octopus perplexe, cherche dans les courants',
        'Octopus réfléchit',
        'Octopus se repose',
        'Octopus surpris',
        'Octopus content',
        'Octopus avec des yeux en cœur',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($en[$key])->not->toBe($key);
    }
});

it('renders the octopus mascot aria-label translated in EN locale (round 75)', function () {
    app()->setLocale('en');

    expect(__('Octopus réfléchit'))->toBe('Octopus thinking');
    expect(__('Octopus, prêt pour le défi'))->toBe('Octopus, ready for the challenge');
});

it('has English translations for the under-construction page title/meta/aria-label/content (round 75)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        ':name fait peau neuve · La veille de Stef',
        ':name : en construction · La veille de Stef',
        ':name est temporairement hors ligne le temps d\'une mise à jour importante. Vos prompts sauvegardés sont intacts.',
        ':name est en construction. Nous travaillons activement à son lancement public sur laveille.ai.',
        'Outil temporairement hors ligne pour mise à jour',
        'Mise à jour en cours',
        'Le :name fait peau neuve',
        'Vos prompts déjà sauvegardés sont intacts et vous seront accessibles dès le retour de l\'outil.',
        'Outil en construction',
        'En construction',
        'Cet outil est en construction. Nous travaillons activement à son lancement public.',
        'Étapes du développement',
        'Avancement prévu',
        'Voir tous les outils disponibles',
        'Retour aux outils',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($en[$key])->not->toBe($key);
    }
});

it('renders the under-construction (revision mode) page translated in EN locale for a real HTTP request (round 75)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts-r75'], [
        'name' => 'Test Tool R75',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => true,
        'construction_mode' => 'revision',
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();

    $html = $this->actingAs($user)->withSession(['locale' => 'en'])->get('/outils/constructeur-prompts-r75')->assertOk()->getContent();

    expect($html)->toContain('<title>Test Tool R75 gets a fresh look · La veille de Stef</title>');
    expect($html)->toContain('Update in progress');
    expect($html)->toContain('aria-label="Discover our other tools"');
    expect($html)->not->toContain('Mise à jour en cours');
    expect($html)->not->toContain('aria-label="Outil temporairement hors ligne pour mise à jour"');
});

it('has English translations for the shared header/search aria-labels (round 75)', function () {
    $en = json_decode(file_get_contents(lang_path('en.json')), true);

    $keys = [
        'Menu Outils',
        'Menu Annuaire',
        'Menu Apprendre',
        'Ouvrir la recherche (Ctrl+K)',
        'Fermer la recherche',
    ];

    foreach ($keys as $key) {
        expect($en)->toHaveKey($key);
        expect($en[$key])->not->toBe($key);
    }
});

it('renders the shared header aria-labels translated in EN locale on the home page (round 75)', function () {
    $html = $this->withSession(['locale' => 'en'])->get('/')->assertOk()->getContent();

    expect($html)->toContain('aria-label="Tools menu"');
    expect($html)->toContain('aria-label="Directory menu"');
    expect($html)->toContain('aria-label="Learn menu"');
    expect($html)->toContain('aria-label="Open search (Ctrl+K)"');
    expect($html)->not->toContain('aria-label="Menu Outils"');
});
