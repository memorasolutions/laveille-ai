<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Mandat #2201 (2026-09-03) : 20 tutoriels affichés en production n'avaient aucun lien avec leur
 * outil, tous par le même mécanisme - un nom d'outil qui est un mot commun fait accepter
 * n'importe quelle vidéo dont le titre contient le mot (« Monologue » l'outil de dictée récoltait
 * du théâtre et le synthétiseur Korg Monologue). Cette suite verrouille les trois correctifs :
 *   1. La garde de domaine curée (GENERIC_NAME_DOMAIN_KEYWORDS) rejette les homonymes MESURÉS
 *      sans toucher aux outils à nom distinctif.
 *   2. L'heuristique de langue s'applique même quand api_lang prétend fr/en (cas réels « Como
 *      Usar Moodle » et « Cómo usar Genially », passés parce que le créateur avait mal réglé la
 *      langue audio).
 *   3. tools:moderate-tutorials désapprouve sans jamais supprimer (le re-scan détecte les
 *      doublons par video_id seul : une ressource désapprouvée ne peut pas être recréée), et
 *      --restore rend l'opération entièrement réversible.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolResource;
use Modules\Directory\Services\YouTubeService;

uses(Tests\TestCase::class, RefreshDatabase::class);

function trgVideo(string $title, array $overrides = []): array
{
    return array_merge([
        'video_id' => substr(md5($title), 0, 11),
        'title' => $title,
        'view_count' => 50000,
        'like_count' => 2000,
        'duration_seconds' => 600,
        'published_at' => now()->subMonths(2)->toIso8601String(),
        'api_lang' => 'en',
    ], $overrides);
}

it('rejette les homonymes des outils a nom commun mais garde les vrais tutoriels', function () {
    $service = new YouTubeService;

    $titles = fn (array $result) => array_column($result, 'title');

    // Cas REELS mesures en production le 2026-09-03 (fiche Monologue : 8 faux sur 8 affiches).
    $monologue = $service->scoreAndFilter([
        trgVideo('VILLAINOUS Female Shakespeare Monologue TUTORIAL | Goneril (King Lear)'),
        trgVideo('Trial By Synth - Korg Monologue'),
        trgVideo('Using Monologue for faster dictation with AI voice typing'),
    ], 'Monologue', 1000);
    expect($titles($monologue))->toBe(['Using Monologue for faster dictation with AI voice typing']);

    // Motion : le motion design est rejete, l'application de gestion de taches passe.
    $motion = $service->scoreAndFilter([
        trgVideo('Premiere Pro Motion Graphics Reel Tutorial for Beginners 2026'),
        trgVideo('How to Use Motion Tracking on CapCut PC'),
        trgVideo('How to Use Motion for Task Management | Full Tutorial'),
    ], 'Motion', 1000);
    expect($titles($motion))->toBe(['How to Use Motion for Task Management | Full Tutorial']);

    // Make : le dessin anime Cartoon Network est rejete, Make.com passe.
    $make = $service->scoreAndFilter([
        trgVideo('How to Make Your Own Comic | Toontorial'),
        trgVideo('Make.com Automation Tutorial for Beginners'),
    ], 'Make', 1000);
    expect($titles($make))->toBe(['Make.com Automation Tutorial for Beginners']);

    // Un outil au nom DISTINCTIF n'est pas concerne par la garde : filtre inchange.
    $deepseek = $service->scoreAndFilter([
        trgVideo('DeepSeek-R1 Crash Course'),
    ], 'DeepSeek', 1000);
    expect($titles($deepseek))->toBe(['DeepSeek-R1 Crash Course']);
});

it('rejette un titre manifestement non fr-en meme quand api_lang pretend le contraire', function () {
    $service = new YouTubeService;

    // Cas reels : api_lang='en' (mal regle par le createur) mais titre espagnol.
    $result = $service->scoreAndFilter([
        trgVideo('Como Usar Moodle (2026) Tutorial De Moodle', ['api_lang' => 'en']),
        trgVideo('How to Install moodle on Windows 10 & 11', ['api_lang' => 'en']),
    ], 'Moodle', 1000);

    expect(array_column($result, 'title'))->toBe(['How to Install moodle on Windows 10 & 11']);
});

it('moderate-tutorials desapprouve par video_id sans supprimer, et --restore retablit', function () {
    // Construction directe (pas de ToolFactory dans ce module - meme convention que les tests voisins).
    $tool = new Tool;
    $tool->url = 'https://tutorial-guard-test.example';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->is_featured = false;
    $tool->setTranslation('name', 'fr_CA', 'Outil Tutorial Guard Test');
    $tool->setTranslation('slug', 'fr_CA', 'tutorial-guard-test-'.uniqid());
    $tool->save();
    $resource = ToolResource::create([
        'directory_tool_id' => $tool->id,
        'url' => 'https://youtube.com/watch?v=abc123def45',
        'title' => 'Faux tutoriel a retirer',
        'type' => 'youtube',
        'language' => 'en',
        'video_id' => 'abc123def45',
        'is_approved' => true,
    ]);

    $this->artisan('tools:moderate-tutorials', ['--videos' => 'abc123def45,inconnu00000'])
        ->expectsOutputToContain('Introuvable en base : inconnu00000')
        ->expectsOutputToContain('Désapprouvé')
        ->assertExitCode(0);

    // Desapprouvee, jamais supprimee : la ligne existe toujours, invisible au public.
    expect(ToolResource::where('video_id', 'abc123def45')->exists())->toBeTrue()
        ->and((bool) $resource->fresh()->is_approved)->toBeFalse();

    // --dry-run n'ecrit rien.
    $this->artisan('tools:moderate-tutorials', ['--videos' => 'abc123def45', '--restore' => true, '--dry-run' => true])
        ->assertExitCode(0);
    expect((bool) $resource->fresh()->is_approved)->toBeFalse();

    // --restore retablit reellement.
    $this->artisan('tools:moderate-tutorials', ['--videos' => 'abc123def45', '--restore' => true])
        ->expectsOutputToContain('Rétabli')
        ->assertExitCode(0);
    expect((bool) $resource->fresh()->is_approved)->toBeTrue();
});
