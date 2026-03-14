<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\AI\Models\KnowledgeChunk;
use Modules\AI\Models\KnowledgeDocument;
use Modules\AI\Services\EmbeddingService;
use Modules\AI\Services\KnowledgeBaseService;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('peut créer un document KB', function () {
    $document = KnowledgeDocument::factory()->create([
        'title' => 'Document de test',
        'source_type' => 'faq',
    ]);

    $this->assertDatabaseHas('ai_knowledge_documents', [
        'id' => $document->id,
        'title' => 'Document de test',
        'source_type' => 'faq',
    ]);
});

it('un document a des chunks', function () {
    $document = KnowledgeDocument::factory()->create();
    KnowledgeChunk::factory()->count(2)->create([
        'document_id' => $document->id,
    ]);

    expect($document->chunks)->toHaveCount(2);
});

it('le scope active filtre correctement', function () {
    KnowledgeDocument::factory()->create(['is_active' => true]);
    KnowledgeDocument::factory()->create(['is_active' => false]);

    expect(KnowledgeDocument::active()->count())->toBe(1);
});

it('le scope byType filtre par type', function () {
    KnowledgeDocument::factory()->create(['source_type' => 'faq']);
    KnowledgeDocument::factory()->create(['source_type' => 'article']);

    expect(KnowledgeDocument::byType('faq')->count())->toBe(1);
});

it('le chunking découpe le texte correctement', function () {
    $embeddingService = Mockery::mock(EmbeddingService::class);
    $service = new KnowledgeBaseService($embeddingService);

    $longText = str_repeat('Ceci est une phrase de test pour le chunking. ', 100);
    $chunks = $service->chunkText($longText);

    expect($chunks)->toBeArray()
        ->and(count($chunks))->toBeGreaterThan(1);
});

it('la cosine similarity fonctionne', function () {
    $service = new EmbeddingService;

    // Vecteurs identiques → 1.0
    expect($service->cosineSimilarity([1, 2, 3], [1, 2, 3]))->toBe(1.0);

    // Vecteurs orthogonaux → 0.0
    expect($service->cosineSimilarity([1, 0, 0], [0, 1, 0]))->toBe(0.0);

    // Vecteurs vides → 0.0
    expect($service->cosineSimilarity([], []))->toBe(0.0);
});

it('l\'accessor embedding_array décode le JSON', function () {
    $chunk = KnowledgeChunk::factory()->create([
        'embedding' => json_encode([0.1, 0.2, 0.3]),
    ]);

    expect($chunk->embedding_array)->toBe([0.1, 0.2, 0.3]);
});

it('l\'accessor embedding_array retourne vide si null', function () {
    $chunk = KnowledgeChunk::factory()->create(['embedding' => null]);

    expect($chunk->embedding_array)->toBe([]);
});

it('le CRUD admin nécessite authentification', function () {
    $this->get(route('admin.ai.knowledge.index'))
        ->assertRedirect();
});

it('l\'admin peut lister les documents KB', function () {
    Role::findOrCreate('super_admin');
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('super_admin');

    $document = KnowledgeDocument::factory()->create(['title' => 'Test KB Admin']);

    $this->actingAs($admin)
        ->get(route('admin.ai.knowledge.index'))
        ->assertStatus(200)
        ->assertSee('Test KB Admin');
});

it('l\'admin peut créer un document', function () {
    Role::findOrCreate('super_admin');
    $admin = \App\Models\User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->post(route('admin.ai.knowledge.store'), [
            'title' => 'Nouveau document KB',
            'content' => 'Contenu du document de test pour la base de connaissances.',
            'source_type' => 'manual',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('ai_knowledge_documents', [
        'title' => 'Nouveau document KB',
        'source_type' => 'manual',
    ]);
});

it('la suppression cascade les chunks', function () {
    $document = KnowledgeDocument::factory()->create();
    KnowledgeChunk::factory()->count(3)->create([
        'document_id' => $document->id,
    ]);

    $docId = $document->id;
    $document->delete();

    $this->assertDatabaseMissing('ai_knowledge_documents', ['id' => $docId]);
    $this->assertDatabaseMissing('ai_knowledge_chunks', ['document_id' => $docId]);
});
