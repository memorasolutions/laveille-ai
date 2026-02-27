<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Testimonials\Models\Testimonial;

uses(RefreshDatabase::class);

// --- Modèle ---

it('crée un témoignage avec les champs requis', function () {
    $t = Testimonial::create([
        'author_name' => 'Jean Dupont',
        'content' => 'Excellent service.',
        'rating' => 4,
    ]);

    expect($t->author_name)->toBe('Jean Dupont');
    expect($t->rating)->toBe(4);
    expect($t->is_approved)->toBeFalse();
    expect($t->order)->toBe(0);
});

it('le scope approved filtre correctement', function () {
    Testimonial::create(['author_name' => 'A', 'content' => 'OK', 'is_approved' => true]);
    Testimonial::create(['author_name' => 'B', 'content' => 'OK', 'is_approved' => false]);

    expect(Testimonial::approved()->count())->toBe(1);
});

it('le scope ordered trie par ordre', function () {
    $t2 = Testimonial::create(['author_name' => 'C', 'content' => 'OK', 'order' => 2]);
    $t0 = Testimonial::create(['author_name' => 'A', 'content' => 'OK', 'order' => 0]);
    $t1 = Testimonial::create(['author_name' => 'B', 'content' => 'OK', 'order' => 1]);

    $ordered = Testimonial::ordered()->get();

    expect($ordered[0]->id)->toBe($t0->id);
    expect($ordered[1]->id)->toBe($t1->id);
    expect($ordered[2]->id)->toBe($t2->id);
});

it('safeContent nettoie le HTML', function () {
    $t = Testimonial::create([
        'author_name' => 'Test',
        'content' => '<p>Bon service</p><script>alert(1)</script>',
    ]);

    expect($t->safeContent())->not->toContain('<script>');
    expect($t->safeContent())->toContain('Bon service');
});

// --- Admin CRUD ---

it('admin peut voir la liste des témoignages', function () {
    Testimonial::create(['author_name' => 'Visible', 'content' => 'Test']);

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('admin.testimonials.index'))
        ->assertOk()
        ->assertSee('Visible');
});

it('admin peut créer un témoignage', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->post(route('admin.testimonials.store'), [
            'author_name' => 'Nouveau client',
            'content' => 'Super produit !',
            'rating' => 5,
        ])
        ->assertRedirect(route('admin.testimonials.index'));

    expect(Testimonial::where('author_name', 'Nouveau client')->exists())->toBeTrue();
});

it('admin peut modifier un témoignage', function () {
    $t = Testimonial::create(['author_name' => 'Ancien', 'content' => 'OK']);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->put(route('admin.testimonials.update', $t), [
            'author_name' => 'Nouveau',
            'content' => 'Modifié',
        ])
        ->assertRedirect();

    expect($t->fresh()->author_name)->toBe('Nouveau');
});

it('admin peut supprimer un témoignage', function () {
    $t = Testimonial::create(['author_name' => 'À supprimer', 'content' => 'Bye']);
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->delete(route('admin.testimonials.destroy', $t))
        ->assertRedirect(route('admin.testimonials.index'));

    $this->assertDatabaseMissing('testimonials', ['id' => $t->id]);
});

it('admin peut réordonner les témoignages', function () {
    $t1 = Testimonial::create(['author_name' => 'A', 'content' => 'OK', 'order' => 0]);
    $t2 = Testimonial::create(['author_name' => 'B', 'content' => 'OK', 'order' => 1]);

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->postJson(route('admin.testimonials.reorder'), [
            'items' => [$t2->id, $t1->id],
        ])
        ->assertOk();

    expect($t2->fresh()->order)->toBe(0);
    expect($t1->fresh()->order)->toBe(1);
});

it('la validation rejette un témoignage sans nom', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->post(route('admin.testimonials.store'), ['content' => 'Contenu seul'])
        ->assertSessionHasErrors('author_name');
});

// --- Page publique ---

it('la page publique affiche les témoignages approuvés', function () {
    Testimonial::create(['author_name' => 'Approuvé', 'content' => 'Super', 'is_approved' => true]);
    Testimonial::create(['author_name' => 'Masqué', 'content' => 'Non visible', 'is_approved' => false]);

    $this->get(route('testimonials.show'))
        ->assertOk()
        ->assertSee('Approuvé')
        ->assertDontSee('Masqué');
});

it('la page publique contient du JSON-LD WebPage', function () {
    $this->get(route('testimonials.show'))
        ->assertOk()
        ->assertSee('"@type": "WebPage"', false);
});

it('la page publique affiche un message si aucun témoignage', function () {
    $this->get(route('testimonials.show'))
        ->assertOk()
        ->assertSee('Aucun témoignage');
});
