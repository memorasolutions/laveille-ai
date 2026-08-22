<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Bug corrigé le 2026-08-22 : Modules/Directory/resources/views/public/show.blade.php
 * reconstruisait TOUJOURS la miniature YouTube dès qu'un video_id était présent, en ignorant
 * la colonne thumbnail sans condition. Une capture locale téléversée par un administrateur
 * (ModerationController::uploadResourceScreenshot() -> ScreenshotUploadService, qui stocke un
 * chemin "/directory-resources-screenshots/{id}.jpg") sur une ressource VIDÉO n'était donc
 * jamais affichée. La logique déplacée dans ToolResource::hasLocalThumbnail()/
 * displayThumbnailUrl() est couverte ici directement sur le modèle, sans passer par la base
 * (aucune factory ToolResource n'existe dans ce module - modèles en mémoire, non persistés,
 * suffisants pour une logique pure sur des attributs).
 */

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Modules\Directory\Models\ToolResource;

uses(Tests\TestCase::class);

function makeThumbnailTestResource(array $attributes = []): ToolResource
{
    $resource = new ToolResource();
    $resource->forceFill(array_merge([
        'title' => 'Ressource de test',
        'url' => 'https://exemple.test/ressource',
        'type' => 'article',
        'language' => 'fr',
    ], $attributes));
    $resource->updated_at = $attributes['updated_at'] ?? Carbon::create(2026, 8, 22, 10, 0, 0);

    return $resource;
}

test('1. ressource vidéo avec thumbnail LOCAL : le chemin local est retourné (bug corrigé)', function () {
    $resource = makeThumbnailTestResource([
        'video_id' => 'AbCdEfGhIjK',
        'thumbnail' => '/directory-resources-screenshots/304.jpg',
    ]);

    expect($resource->hasLocalThumbnail())->toBeTrue();
    expect($resource->displayThumbnailUrl())
        ->toBe('/directory-resources-screenshots/304.jpg?v=' . $resource->updated_at->timestamp);
});

test('2. ressource vidéo avec thumbnail YouTube (img.youtube.com) : aucune régression, URL YouTube retournée', function () {
    $resource = makeThumbnailTestResource([
        'video_id' => 'Wg9M140pduU',
        'thumbnail' => 'https://img.youtube.com/vi/Wg9M140pduU/hqdefault.jpg',
    ]);

    expect($resource->hasLocalThumbnail())->toBeFalse();
    expect($resource->displayThumbnailUrl())->toBe('https://img.youtube.com/vi/Wg9M140pduU/hqdefault.jpg');
});

test('3. ressource vidéo sans thumbnail : URL YouTube reconstruite depuis video_id', function () {
    $resource = makeThumbnailTestResource([
        'video_id' => 'cjLT7mmY0VU',
        'thumbnail' => null,
    ]);

    expect($resource->hasLocalThumbnail())->toBeFalse();
    expect($resource->displayThumbnailUrl())->toBe('https://img.youtube.com/vi/cjLT7mmY0VU/hqdefault.jpg');
});

test('4. ressource NON vidéo avec thumbnail local : le chemin local (comportement existant inchangé)', function () {
    $resource = makeThumbnailTestResource([
        'video_id' => null,
        'type' => 'formation',
        'thumbnail' => '/directory-resources-screenshots/772.jpg',
    ]);

    expect($resource->hasLocalThumbnail())->toBeTrue();
    expect($resource->displayThumbnailUrl())
        ->toBe('/directory-resources-screenshots/772.jpg?v=' . $resource->updated_at->timestamp);
});

test('5. ressource sans video_id ni thumbnail : aucune miniature, aucune erreur', function () {
    $resource = makeThumbnailTestResource([
        'video_id' => null,
        'thumbnail' => null,
    ]);

    expect($resource->hasLocalThumbnail())->toBeFalse();
    expect($resource->displayThumbnailUrl())->toBeNull();
});
