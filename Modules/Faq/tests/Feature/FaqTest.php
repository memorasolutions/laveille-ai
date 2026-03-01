<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Modules\Faq\Models\Faq;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

it('affiche la liste des FAQ en admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.faqs.index'))
        ->assertOk();
});

it('affiche le formulaire de création', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.faqs.create'))
        ->assertOk();
});

it('crée une question FAQ', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.faqs.store'), [
            'question' => 'Comment ça marche ?',
            'answer' => 'Très simplement.',
            'category' => 'Général',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('faqs', ['question' => 'Comment ça marche ?', 'category' => 'Général']);
});

it('valide la question requise', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.faqs.store'), ['answer' => 'Réponse sans question.'])
        ->assertSessionHasErrors('question');
});

it('valide la réponse requise', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.faqs.store'), ['question' => 'Question sans réponse ?'])
        ->assertSessionHasErrors('answer');
});

it('affiche le formulaire d\'édition', function () {
    $faq = Faq::create(['question' => 'Test ?', 'answer' => 'Oui.', 'order' => 0]);

    $this->actingAs($this->admin)
        ->get(route('admin.faqs.edit', $faq))
        ->assertOk();
});

it('met à jour une question FAQ', function () {
    $faq = Faq::create(['question' => 'Ancienne ?', 'answer' => 'Ancien.', 'order' => 0]);

    $this->actingAs($this->admin)
        ->put(route('admin.faqs.update', $faq), [
            'question' => 'Nouvelle ?',
            'answer' => 'Nouveau.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('faqs', ['id' => $faq->id, 'question' => 'Nouvelle ?']);
});

it('supprime une question FAQ', function () {
    $faq = Faq::create(['question' => 'À supprimer ?', 'answer' => 'Oui.', 'order' => 0]);

    $this->actingAs($this->admin)
        ->delete(route('admin.faqs.destroy', $faq))
        ->assertRedirect();

    $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
});

it('réordonne les FAQ via JSON', function () {
    $faq1 = Faq::create(['question' => 'Premier ?', 'answer' => 'A.', 'order' => 0]);
    $faq2 = Faq::create(['question' => 'Deuxième ?', 'answer' => 'B.', 'order' => 1]);

    $this->actingAs($this->admin)
        ->postJson(route('admin.faqs.reorder'), [
            'items' => [$faq2->id, $faq1->id],
        ])
        ->assertJson(['success' => true]);

    expect(Faq::find($faq2->id)->order)->toBe(0);
    expect(Faq::find($faq1->id)->order)->toBe(1);
});

it('affiche la page FAQ publique', function () {
    Faq::create(['question' => 'Visible ?', 'answer' => 'Oui.', 'order' => 0, 'is_published' => true]);
    Faq::create(['question' => 'Cachée ?', 'answer' => 'Non.', 'order' => 1, 'is_published' => false]);

    $response = $this->get(route('faq.show'));
    $response->assertOk();
    $response->assertSee('Visible ?');
    $response->assertDontSee('Cachée ?');
});

it('inclut le JSON-LD Schema.org dans la page publique', function () {
    Faq::create(['question' => 'JSON-LD test ?', 'answer' => 'Réponse.', 'order' => 0]);

    $response = $this->get(route('faq.show'));
    $response->assertOk();
    $response->assertSee('application/ld+json', false);
    $response->assertSee('FAQPage', false);
});

it('filtre par catégorie via le scope', function () {
    Faq::create(['question' => 'Cat A ?', 'answer' => 'A.', 'category' => 'Tech', 'order' => 0]);
    Faq::create(['question' => 'Cat B ?', 'answer' => 'B.', 'category' => 'General', 'order' => 1]);

    $tech = Faq::byCategory('Tech')->get();
    expect($tech)->toHaveCount(1);
    expect($tech->first()->question)->toBe('Cat A ?');
});

it('retourne les FAQ publiées et ordonnées', function () {
    Faq::create(['question' => 'Troisième ?', 'answer' => 'C.', 'order' => 2, 'is_published' => true]);
    Faq::create(['question' => 'Premier ?', 'answer' => 'A.', 'order' => 0, 'is_published' => true]);
    Faq::create(['question' => 'Brouillon ?', 'answer' => 'X.', 'order' => 1, 'is_published' => false]);

    $faqs = Faq::published()->ordered()->get();
    expect($faqs)->toHaveCount(2);
    expect($faqs->first()->question)->toBe('Premier ?');
});

it('auto-incrémente l\'ordre à la création', function () {
    Faq::create(['question' => 'A ?', 'answer' => 'A.', 'order' => 5]);

    $this->actingAs($this->admin)
        ->post(route('admin.faqs.store'), [
            'question' => 'B ?',
            'answer' => 'B.',
        ])
        ->assertRedirect();

    $latest = Faq::where('question', 'B ?')->first();
    expect($latest->order)->toBe(6);
});

it('nettoie le HTML de la réponse avec purifier', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.faqs.store'), [
            'question' => 'XSS test ?',
            'answer' => '<p>Safe</p><script>alert("xss")</script>',
        ])
        ->assertRedirect();

    $faq = Faq::where('question', 'XSS test ?')->first();
    expect($faq->answer)->not->toContain('<script>');
    expect($faq->answer)->toContain('Safe');
});
