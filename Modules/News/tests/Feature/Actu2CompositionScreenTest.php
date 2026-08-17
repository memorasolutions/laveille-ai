<?php

declare(strict_types=1);

/**
 * Tests de l'écran de composition (Modules\News\resources\views\admin\composition-builder.blade.php)
 * après l'implémentation /actu2 - volet serveur (design doc "Actus - composition manuelle
 * assistée" 2026-08-15, section "Implémentation /actu2 - volet serveur (2026-08-17)") : le bouton
 * principal devient « Copier le prompt /actu2 », construit CÔTÉ CLIENT (aucun appel serveur) et
 * copié au presse-papier ; l'ancien flux de génération (gros gabarit) reste accessible, déplacé
 * dans le volet replié « Édition manuelle (filet de secours) », étiqueté déprécié.
 *
 * Convention du projet (JS Alpine non exécuté en Pest, cf. NewsCompositionBuilderTest.php) :
 * assertions sur le HTML/JS rendu, pas d'exécution du navigateur - helpers locaux préfixés `a2c`
 * (actu2 composition screen), autonomes.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function a2cAdmin(): \App\Models\User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole($role);

    return $user;
}

// ── Bouton principal : « Copier le prompt /actu2 » ──────────────────────────────────

it('the composition screen renders the primary "Copier le prompt /actu2" button, calling copyQuickPrompt()', function () {
    $admin = a2cAdmin();

    $response = $this->actingAs($admin)->get(route('admin.news.composition.index'));

    $response->assertOk()
        ->assertSee('📋 Copier le prompt /actu2', false)
        ->assertSee('@click="copyQuickPrompt()"', false)
        ->assertSee('quickPromptCopied', false);
});

// ── Mini-prompt construit CÔTÉ CLIENT (aucun appel serveur) ─────────────────────────

it('the copyQuickPrompt() JS method builds "/actu2 {source_url} fiche:{id}" client-side, without any server call', function () {
    $admin = a2cAdmin();

    $response = $this->actingAs($admin)->get(route('admin.news.composition.index'));

    $response->assertOk()
        ->assertSee("'/actu2 '", false)
        ->assertSee("'fiche:' + this.selectedArticle.id", false)
        ->assertSee('this.selectedArticle.source_url', false)
        ->assertSee('navigator.clipboard.writeText(prompt)', false);
});

// ── Infobulle mise à jour ────────────────────────────────────────────────────────────

it('the composition screen shows the updated tooltip pointing to /actu2', function () {
    $admin = a2cAdmin();

    $response = $this->actingAs($admin)->get(route('admin.news.composition.index'));

    $response->assertOk()->assertSee(
        "Colle /actu2 dans Claude Code : il retrouve l'original, rédige, prouve, révise, choisit la photo et publie - puis te donne le lien.",
        false
    );
});

// ── Ancien flux (gros gabarit) : déplacé, accessible, étiqueté déprécié ─────────────

it('the old "generate prompt" flow remains accessible inside the collapsed manual-edition panel, labeled deprecated', function () {
    $admin = a2cAdmin();

    $response = $this->actingAs($admin)->get(route('admin.news.composition.index'));

    $response->assertOk()
        ->assertSee('nc-manual-details', false)
        ->assertSee('Édition manuelle (filet de secours)', false)
        ->assertSee("Enregistrer et générer le prompt Claude Code (déprécié - l'ancien gros prompt)", false)
        ->assertSee('@click="generatePrompt()"', false)
        ->assertSee('@click="copyPrompt()"', false);
});

it('a non-admin cannot view the composition screen (403)', function () {
    $user = \App\Models\User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->get(route('admin.news.composition.index'));

    $response->assertStatus(403);
});
