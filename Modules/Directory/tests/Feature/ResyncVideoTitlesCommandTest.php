<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ce que ces tests protègent (#2555) : 37 ressources affichaient un titre TRADUIT en anglais
 * pour une vidéo française, sur un site québécois francophone. Mesuré le 2026-09-14 : l'API
 * YouTube renvoie bien « Les bases de Midjourney : Le Guide de A à Z » là où la base stockait
 * « The Basics of Midjourney: The A-Z Guide ». La base mentait, pas la source.
 *
 * LE piège que le premier jet contenait, et que le 3e test verrouille : getVideoDetails()
 * renvoie une LISTE indexée par entier, pas par identifiant de vidéo. Sans réindexation, la
 * commande aurait compté les 1855 ressources « disparues » et n'aurait rien corrigé - en ayant
 * l'air de fonctionner.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolResource;
use Modules\Directory\Services\YouTubeService;

uses(Tests\TestCase::class, RefreshDatabase::class);

function resyncOutil(): Tool
{
    $tool = new Tool;
    $tool->url = 'https://resync-test-'.uniqid().'.example';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->is_featured = false;
    $tool->setTranslation('name', 'fr_CA', 'Outil Resync Test');
    $tool->setTranslation('slug', 'fr_CA', 'resync-test-'.uniqid());
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->save();

    return $tool;
}

function resyncRessource(string $videoId, string $titre, string $langue = 'fr'): ToolResource
{
    return ToolResource::create([
        'directory_tool_id' => resyncOutil()->id,
        'user_id' => null,
        'url' => "https://www.youtube.com/watch?v={$videoId}",
        'title' => $titre,
        'type' => 'youtube',
        'language' => $langue,
        'video_id' => $videoId,
        'is_approved' => true,
    ]);
}

/**
 * Le faux service rend la LISTE indexée par entier que renvoie réellement getVideoDetails() -
 * jamais un tableau associatif, qui masquerait justement le défaut à verrouiller.
 */
function resyncFauxYoutube(array $videos): void
{
    app()->instance(YouTubeService::class, new class($videos) extends YouTubeService
    {
        public function __construct(private array $videos) {}

        public function getVideoDetails(array $videoIds): array
        {
            return array_values(array_filter(
                $this->videos,
                fn ($v) => in_array($v['video_id'], $videoIds, true)
            ));
        }
    });
}

it('corrige un titre français stocké traduit en anglais', function () {
    $r = resyncRessource('f7Q5jaQjSbs', 'The Basics of Midjourney: The A-Z Guide');
    resyncFauxYoutube([[
        'video_id' => 'f7Q5jaQjSbs',
        'title' => 'Les bases de Midjourney : Le Guide de A à Z',
        'api_lang' => 'fr',
    ]]);

    $this->artisan('directory:resync-video-titles --apply')->assertSuccessful();

    expect($r->fresh()->title)->toBe('Les bases de Midjourney : Le Guide de A à Z');
    expect($r->fresh()->language)->toBe('fr');
});

// Sans --apply, la commande MESURE et n'écrit rien : c'est ce qui permet de la lancer sur la
// production avant de décider.
it("n'écrit rien sans --apply", function () {
    $r = resyncRessource('f7Q5jaQjSbs', 'The Basics of Midjourney: The A-Z Guide');
    resyncFauxYoutube([[
        'video_id' => 'f7Q5jaQjSbs',
        'title' => 'Les bases de Midjourney : Le Guide de A à Z',
        'api_lang' => 'fr',
    ]]);

    $this->artisan('directory:resync-video-titles')->assertSuccessful();

    expect($r->fresh()->title)->toBe('The Basics of Midjourney: The A-Z Guide');
});

// LE test qui compte : il échoue si la réindexation par video_id disparaît.
it('retrouve la vidéo malgré une réponse indexée par entier', function () {
    $r = resyncRessource('aaaAAAaaaAA', 'Ancien titre');
    resyncFauxYoutube([[
        'video_id' => 'aaaAAAaaaAA',
        'title' => 'Titre réel de la vidéo',
        'api_lang' => 'fr',
    ]]);

    $this->artisan('directory:resync-video-titles --apply')
        ->expectsOutputToContain('1')
        ->assertSuccessful();

    expect($r->fresh()->title)->toBe('Titre réel de la vidéo');
});

// Une vidéo privée ou retirée disparaît de la réponse. On la signale, on ne la supprime
// JAMAIS et on ne la dépublie pas : la décision appartient à un humain.
it('ne touche pas une ressource absente de la réponse YouTube', function () {
    $r = resyncRessource('zzzZZZzzzZZ', 'Vidéo devenue privée');
    resyncFauxYoutube([]);

    $this->artisan('directory:resync-video-titles --apply')->assertSuccessful();

    expect(ToolResource::find($r->id))->not->toBeNull();
    expect($r->fresh()->title)->toBe('Vidéo devenue privée');
    expect($r->fresh()->is_approved)->toBeTrue();
});

it('laisse intacte une ressource dont le titre est déjà juste', function () {
    $r = resyncRessource('bbbBBBbbbBB', 'Un titre déjà correct');
    resyncFauxYoutube([[
        'video_id' => 'bbbBBBbbbBB',
        'title' => 'Un titre déjà correct',
        'api_lang' => 'fr',
    ]]);

    $avant = $r->updated_at;

    $this->artisan('directory:resync-video-titles --apply')->assertSuccessful();

    expect($r->fresh()->title)->toBe('Un titre déjà correct');
    expect($r->fresh()->updated_at->toString())->toBe($avant->toString());
});
