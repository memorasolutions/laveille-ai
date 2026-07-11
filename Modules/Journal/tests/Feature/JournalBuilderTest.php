<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 *
 * Tests Pest — composant Livewire JournalBuilder (constructeur front-end),
 * cycle de vie réel (Livewire::test), autorisation serveur systématique,
 * confirmation inline à 2 temps (jamais de popup native), réordonnancement
 * anti-IDOR.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Journal\Livewire\JournalBuilder;
use Modules\Journal\Models\Journal;
use Modules\Journal\Services\JournalBlockService;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

function jbJournal(int $userId, array $overrides = []): Journal
{
    return Journal::create(array_merge([
        'user_id' => $userId,
        'title' => 'Journal builder test',
        'slug' => 'journal-builder-test-'.uniqid(),
        'journal_date' => now()->toDateString(),
        'template' => 'classique',
        'is_published' => false,
    ], $overrides));
}

test('mount is forbidden for a non-owner', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $journal = jbJournal($owner->id);

    Livewire::actingAs($stranger)
        ->test(JournalBuilder::class, ['journal' => $journal])
        ->assertForbidden();
});

test('updateSettings persists title and template for the owner', function () {
    $owner = User::factory()->create();
    $journal = jbJournal($owner->id);

    Livewire::actingAs($owner)
        ->test(JournalBuilder::class, ['journal' => $journal])
        ->set('title', 'Nouveau titre')
        ->set('template', 'magazine')
        ->call('updateSettings');

    $journal->refresh();
    expect($journal->title)->toBe('Nouveau titre');
    expect($journal->template)->toBe('magazine');
});

test('togglePublished flips is_published', function () {
    $owner = User::factory()->create();
    $journal = jbJournal($owner->id, ['is_published' => false]);

    Livewire::actingAs($owner)
        ->test(JournalBuilder::class, ['journal' => $journal])
        ->call('togglePublished')
        ->assertSet('isPublished', true);

    expect($journal->fresh()->isPublished())->toBeTrue();
});

test('saveTextBlock creates a block, editTextBlock loads it back for editing', function () {
    $owner = User::factory()->create();
    $journal = jbJournal($owner->id);

    $component = Livewire::actingAs($owner)
        ->test(JournalBuilder::class, ['journal' => $journal])
        ->call('openPanel', 'text')
        ->set('textBlockHtml', '<p>Contenu initial</p>')
        ->call('saveTextBlock');

    expect($journal->blocks()->count())->toBe(1);
    $block = $journal->blocks()->first();
    expect($block->payload['html'])->toBe('<p>Contenu initial</p>');

    $component->call('editTextBlock', $block->id)
        ->assertSet('textBlockHtml', '<p>Contenu initial</p>')
        ->assertSet('editingBlockId', $block->id)
        ->set('textBlockHtml', '<p>Contenu modifié</p>')
        ->call('saveTextBlock');

    expect($journal->blocks()->count())->toBe(1); // édition, pas ajout
    expect($journal->blocks()->first()->payload['html'])->toBe('<p>Contenu modifié</p>');
});

test('saveVideoBlock validates the URL server-side', function () {
    $owner = User::factory()->create();
    $journal = jbJournal($owner->id);

    Livewire::actingAs($owner)
        ->test(JournalBuilder::class, ['journal' => $journal])
        ->call('openPanel', 'video')
        ->set('videoUrl', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')
        ->call('saveVideoBlock');

    expect($journal->blocks()->where('type', 'video')->count())->toBe(1);
});

test('remove block uses inline two-step confirmation, never deletes on the first click', function () {
    $owner = User::factory()->create();
    $journal = jbJournal($owner->id);
    $block = app(JournalBlockService::class)->addTextBlock($journal, '<p>À retirer</p>');

    $component = Livewire::actingAs($owner)
        ->test(JournalBuilder::class, ['journal' => $journal])
        ->call('confirmRemoveBlock', $block->id)
        ->assertSet('confirmingRemoveBlockId', $block->id);

    expect($journal->blocks()->count())->toBe(1); // toujours là après le 1er clic

    $component->call('cancelRemoveBlock')->assertSet('confirmingRemoveBlockId', null);
    expect($journal->blocks()->count())->toBe(1);

    $component->call('removeBlock', $block->id);
    expect($journal->blocks()->count())->toBe(0);
});

test('moveBlockUp and moveBlockDown swap sort_order with the immediate neighbour', function () {
    $owner = User::factory()->create();
    $journal = jbJournal($owner->id);
    $service = app(JournalBlockService::class);

    $first = $service->addTextBlock($journal, '<p>1</p>');
    $second = $service->addTextBlock($journal, '<p>2</p>');

    Livewire::actingAs($owner)
        ->test(JournalBuilder::class, ['journal' => $journal])
        ->call('moveBlockUp', $second->id);

    expect($first->fresh()->sort_order)->toBe($second->sort_order);
    expect($second->fresh()->sort_order)->toBe($first->sort_order);
});

test('reorderBlocks rejects a tampered id set (anti-IDOR)', function () {
    $owner = User::factory()->create();
    $journal = jbJournal($owner->id);
    $service = app(JournalBlockService::class);

    $a = $service->addTextBlock($journal, '<p>A</p>');
    $b = $service->addTextBlock($journal, '<p>B</p>');

    $originalOrders = [$a->id => $a->sort_order, $b->id => $b->sort_order];

    // Un id étranger (999999) glissé dans la liste : le serveur doit refuser en bloc.
    Livewire::actingAs($owner)
        ->test(JournalBuilder::class, ['journal' => $journal])
        ->call('reorderBlocks', [999999, $a->id, $b->id]);

    expect($a->fresh()->sort_order)->toBe($originalOrders[$a->id]);
    expect($b->fresh()->sort_order)->toBe($originalOrders[$b->id]);
});

test('reorderBlocks accepts a valid permutation of the journal own block ids', function () {
    $owner = User::factory()->create();
    $journal = jbJournal($owner->id);
    $service = app(JournalBlockService::class);

    $a = $service->addTextBlock($journal, '<p>A</p>');
    $b = $service->addTextBlock($journal, '<p>B</p>');

    Livewire::actingAs($owner)
        ->test(JournalBuilder::class, ['journal' => $journal])
        ->call('reorderBlocks', [$b->id, $a->id]);

    expect($b->fresh()->sort_order)->toBeLessThan($a->fresh()->sort_order);
});
