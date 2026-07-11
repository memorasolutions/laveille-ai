<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 *
 * Tests Pest — modèle Journal (relations, casts) et JournalPolicy
 * (view/update/delete : publié = tout le monde, brouillon = propriétaire seul).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Modules\Journal\Models\Journal;
use Modules\Journal\Models\JournalBlock;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

function makeJournal(int $userId, array $overrides = []): Journal
{
    return Journal::create(array_merge([
        'user_id' => $userId,
        'title' => 'Journal de test',
        'slug' => 'journal-test-'.uniqid(),
        'journal_date' => now()->toDateString(),
        'template' => 'classique',
        'is_published' => false,
    ], $overrides));
}

test('journal belongs to a user and has many ordered blocks', function () {
    $user = User::factory()->create();
    $journal = makeJournal($user->id);

    JournalBlock::create(['journal_id' => $journal->id, 'type' => 'text', 'payload' => ['html' => 'B'], 'sort_order' => 2]);
    JournalBlock::create(['journal_id' => $journal->id, 'type' => 'text', 'payload' => ['html' => 'A'], 'sort_order' => 1]);

    expect($journal->user->id)->toBe($user->id);
    expect($journal->blocks()->pluck('sort_order')->all())->toBe([1, 2]);
});

test('is_published and journal_date casts work', function () {
    $user = User::factory()->create();
    $journal = makeJournal($user->id, ['is_published' => true, 'journal_date' => '2026-07-11']);

    expect($journal->is_published)->toBeTrue();
    expect($journal->isPublished())->toBeTrue();
    expect($journal->journal_date)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('route key is the slug, not the id', function () {
    $user = User::factory()->create();
    $journal = makeJournal($user->id, ['slug' => 'mon-slug-unique']);

    expect($journal->getRouteKey())->toBe('mon-slug-unique');
    expect($journal->getRouteKeyName())->toBe('slug');
});

test('policy: a draft journal is only visible to its owner', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $draft = makeJournal($owner->id, ['is_published' => false]);

    expect(Gate::forUser($owner)->allows('view', $draft))->toBeTrue();
    expect(Gate::forUser($stranger)->allows('view', $draft))->toBeFalse();
    expect(Gate::allows('view', $draft))->toBeFalse(); // invité (pas d'utilisateur courant)
});

test('policy: a published journal is visible to everyone', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $published = makeJournal($owner->id, ['is_published' => true]);

    expect(Gate::forUser($owner)->allows('view', $published))->toBeTrue();
    expect(Gate::forUser($stranger)->allows('view', $published))->toBeTrue();
});

test('policy: only the owner can update or delete a journal', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $journal = makeJournal($owner->id);

    expect(Gate::forUser($owner)->allows('update', $journal))->toBeTrue();
    expect(Gate::forUser($owner)->allows('delete', $journal))->toBeTrue();
    expect(Gate::forUser($stranger)->allows('update', $journal))->toBeFalse();
    expect(Gate::forUser($stranger)->allows('delete', $journal))->toBeFalse();
});
