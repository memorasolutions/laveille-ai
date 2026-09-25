<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Signature\Models\Signature;
use Modules\Signature\Models\SignatureImage;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->superadmin = User::factory()->create(['email' => config('app.superadmin_email')]);
    $this->superadmin->assignRole('super_admin');
});

function sigPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'template' => 'minimal',
        'content' => [
            'first_name' => 'Marie',
            'last_name' => 'Tremblay',
            'email' => 'marie@example.com',
        ],
    ], $overrides);
}

test('un membre (superadmin, seul accès pendant la construction) peut créer une signature via l\'assistant, qui apparaît dans Mes signatures', function (): void {
    $response = $this->actingAs($this->superadmin)
        ->postJson(route('signature.draft.store'), sigPayload());

    $response->assertCreated();

    $signature = Signature::first();
    expect($signature)->not->toBeNull()
        ->and($signature->user_id)->toBe($this->superadmin->id)
        ->and($signature->content['first_name'])->toBe('Marie');

    $this->actingAs($this->superadmin)
        ->get(route('signature.user.index'))
        ->assertOk()
        ->assertSee('Marie', false);
});

test('un membre peut modifier une signature déjà enregistrée via Mes signatures', function (): void {
    $signature = new Signature();
    $signature->user_id = $this->superadmin->id;
    $signature->template = 'minimal';
    $signature->content = ['first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com'];
    $signature->admin_token_hash = hash('sha256', 'x');
    $signature->status = Signature::STATUS_ACTIVE;
    $signature->save();

    $response = $this->actingAs($this->superadmin)
        ->patchJson(route('signature.user.update', $signature), sigPayload([
            'content' => ['first_name' => 'Marie-Claude'],
        ]));

    $response->assertOk();
    expect($signature->fresh()->content['first_name'])->toBe('Marie-Claude');
});

test('un membre ne peut PAS modifier la signature d\'un autre membre (403)', function (): void {
    $other = User::factory()->create();
    $signature = new Signature();
    $signature->user_id = $other->id;
    $signature->template = 'minimal';
    $signature->content = ['first_name' => 'Autre', 'last_name' => 'Personne', 'email' => 'autre@example.com'];
    $signature->admin_token_hash = hash('sha256', 'y');
    $signature->status = Signature::STATUS_ACTIVE;
    $signature->save();

    $this->actingAs($this->superadmin)
        ->patchJson(route('signature.user.update', $signature), sigPayload())
        ->assertForbidden();
});

test('la validation refuse un courriel invalide et une signature reste non créée', function (): void {
    $this->actingAs($this->superadmin)
        ->postJson(route('signature.draft.store'), sigPayload(['content' => ['email' => 'pas-un-courriel']]))
        ->assertStatus(422);

    expect(Signature::count())->toBe(0);
});

/**
 * B4 - le VRAI chemin membre passe par le JS de la page rendue, pas par un patchJson direct
 * (qui contourne entièrement le bug qu'on corrige : save() ne calculait jamais la bonne URL/méthode
 * pour un membre). Sans navigateur disponible dans cette tâche, ce test vérifie ce que le JS
 * consulte réellement pour décider où écrire (signature-core.js: save() → `this.updateUrl ?
 * 'PATCH' : 'POST'`) : la page de « Mes signatures » doit exposer token=null ET updateUrl déjà
 * posé sur la VRAIE route PATCH, plus signatureId (nécessaire au téléversement d'image, qui ne
 * peut pas passer par un jeton que le membre ne possède jamais).
 */
test('B4 - la page « Mes signatures » configure l\'éditeur avec token=null, le VRAI updateUrl PATCH et signatureId (chemin que save() emprunte réellement)', function (): void {
    $signature = new Signature();
    $signature->user_id = $this->superadmin->id;
    $signature->template = 'minimal';
    $signature->content = ['first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com'];
    $signature->admin_token_hash = hash('sha256', 'jeton-jamais-vu-du-membre');
    $signature->status = Signature::STATUS_ACTIVE;
    $signature->save();

    $html = $this->actingAs($this->superadmin)
        ->get(route('signature.user.edit', $signature))
        ->assertOk()
        ->getContent();

    $realUpdateUrl = route('signature.user.update', $signature);

    expect($html)->toContain('token: null')
        ->and($html)->toContain('signatureId: '.$signature->id)
        ->and($html)->toContain("updateUrl: '{$realUpdateUrl}'")
        // Le jeton en clair n'apparaît JAMAIS dans la page d'un membre - il n'existe que dans le
        // flux anonyme (rendu une seule fois via showTokenModal, jamais injecté côté serveur ici).
        ->and($html)->not->toContain('jeton-jamais-vu-du-membre');
});

test('B4 - un membre peut téléverser une image sans jeton, via signature_id + session authentifiée', function (): void {
    Storage::fake('public');

    $signature = new Signature();
    $signature->user_id = $this->superadmin->id;
    $signature->template = 'minimal';
    $signature->content = ['first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com'];
    $signature->admin_token_hash = hash('sha256', 'z');
    $signature->status = Signature::STATUS_ACTIVE;
    $signature->save();

    $file = UploadedFile::fake()->image('logo.png', 200, 100);

    $response = $this->actingAs($this->superadmin)
        ->post(route('signature.images.store'), [
            'signature_id' => $signature->id,
            'role' => 'logo',
            'display_width' => 96,
            'image' => $file,
        ]);

    $response->assertCreated();
    expect($signature->images()->count())->toBe(1);
});

test('B4 - un membre ne peut PAS téléverser une image sur la signature d\'un autre membre via signature_id (403)', function (): void {
    Storage::fake('public');

    $other = User::factory()->create();
    $signature = new Signature();
    $signature->user_id = $other->id;
    $signature->template = 'minimal';
    $signature->content = ['first_name' => 'Autre', 'last_name' => 'Personne', 'email' => 'autre@example.com'];
    $signature->admin_token_hash = hash('sha256', 'w');
    $signature->status = Signature::STATUS_ACTIVE;
    $signature->save();

    $file = UploadedFile::fake()->image('logo.png', 200, 100);

    $this->actingAs($this->superadmin)
        ->post(route('signature.images.store'), [
            'signature_id' => $signature->id,
            'role' => 'logo',
            'display_width' => 96,
            'image' => $file,
        ])
        ->assertForbidden();
});

/**
 * M3.4 - la suppression membre réutilise EXACTEMENT la logique de purgeOne() (service partagé) :
 * jamais un hard-delete, la coquille technique reste avec un contenu vidé, l'image est en
 * quarantaine (B2), et la signature disparaît de la liste « Mes signatures » (index filtré).
 */
test('M3.4 - la suppression d\'une signature par son membre vide le contenu et déplace l\'image en quarantaine, jamais un hard-delete', function (): void {
    Storage::fake('public');
    Storage::fake('local');

    $signature = new Signature();
    $signature->user_id = $this->superadmin->id;
    $signature->template = 'minimal';
    $signature->content = ['first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com'];
    $signature->admin_token_hash = hash('sha256', 'v');
    $signature->status = Signature::STATUS_ACTIVE;
    $signature->save();

    $image = SignatureImage::create([
        'signature_id' => $signature->id,
        'role' => 'logo',
        'path' => 'signature-derivatives/signature-'.$signature->id.'/logo-membre.png',
        'width' => 96, 'height' => 40,
    ]);
    Storage::disk('public')->put($image->path, 'contenu-membre');

    $this->actingAs($this->superadmin)
        ->delete(route('signature.user.destroy', $signature))
        ->assertRedirect(route('signature.user.index'));

    $signature->refresh();
    $image->refresh();

    // La LIGNE survit (coquille technique), jamais un hard-delete.
    expect(Signature::find($signature->id))->not->toBeNull()
        ->and($signature->status)->toBe(Signature::STATUS_PURGED)
        ->and($signature->content)->toBe([])
        ->and($image->path)->toBeNull()
        ->and(Storage::disk('public')->exists('signature-derivatives/signature-'.$signature->id.'/logo-membre.png'))->toBeFalse();

    // L'index « Mes signatures » ne montre plus une signature purgée.
    $this->actingAs($this->superadmin)
        ->get(route('signature.user.index'))
        ->assertOk()
        ->assertDontSee('Marie Tremblay', false);
});

test('M3.4 - l\'édition d\'une signature déjà purgée redirige plutôt que d\'ouvrir un formulaire vide', function (): void {
    $signature = new Signature();
    $signature->user_id = $this->superadmin->id;
    $signature->template = 'minimal';
    $signature->content = [];
    $signature->admin_token_hash = hash('sha256', 'u');
    $signature->status = Signature::STATUS_PURGED;
    $signature->purged_at = now();
    $signature->save();

    $this->actingAs($this->superadmin)
        ->get(route('signature.user.edit', $signature))
        ->assertRedirect(route('signature.user.index'));
});
