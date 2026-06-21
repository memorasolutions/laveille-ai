<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// Helpers préfixés Tas pour éviter tout conflit avec d'autres fichiers de tests.

function tasMakeTool(array $overrides = []): Tool
{
    return Tool::firstOrCreate(
        ['slug' => $overrides['slug'] ?? 'outil-demo-partage'],
        array_merge([
            'name' => 'Outil démo partage',
            'description' => 'Un outil interactif gratuit pour tester le partage admin. Visitez https://laveille.ai/outils/outil-demo-partage pour l\'essayer.',
            'answer_summary' => 'Cet outil aide à faire X plus vite, gratuitement.',
            'icon' => '🧪',
            'sort_order' => 99,
            'is_active' => true,
            'is_under_construction' => false,
            'category' => 'productivite',
        ], $overrides)
    );
}

function tasMakeSuperAdmin(): \App\Models\User
{
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $email = (string) config('app.superadmin_email');
    if ($email === '') {
        config(['app.superadmin_email' => $email = 'superadmin-test@laveille.ai']);
    }
    $user = \App\Models\User::factory()->create([
        'email' => $email,
        'email_verified_at' => now(),
    ]);
    $user->assignRole($role);

    return $user;
}

it('retourne au moins 7 items avec les bons labels et des textes non vides', function () {
    $tool = tasMakeTool();

    $items = $tool->adminShareContents();

    expect($items)->toBeArray()->and(count($items))->toBeGreaterThanOrEqual(7);

    $labels = array_map(fn ($i) => $i['label'], $items);

    foreach (['Résumé (NotebookLM)', 'NotebookLM Infographie', 'NotebookLM Diapositives', 'Post LinkedIn', 'Post Facebook', 'Post X', 'Légende Instagram'] as $expected) {
        expect($labels)->toContain($expected);
    }

    foreach ($items as $item) {
        expect($item)->toHaveKeys(['label', 'icon', 'text']);
        expect(trim((string) $item['text']))->not->toBe('');
    }
});

it('le post LinkedIn ne contient aucun lien http (anti-lien)', function () {
    $tool = tasMakeTool();

    $items = collect($tool->adminShareContents());
    $linkedin = $items->firstWhere('label', 'Post LinkedIn')['text'];

    expect($linkedin)->not->toMatch('#https?://#i');
});

it('le post X reste sous 280 caractères et sans lien', function () {
    $tool = tasMakeTool();

    $items = collect($tool->adminShareContents());
    $x = $items->firstWhere('label', 'Post X')['text'];

    expect(mb_strlen($x))->toBeLessThanOrEqual(280);
    expect($x)->not->toMatch('#https?://#i');
});

it('reste robuste si la description et le résumé sont vides', function () {
    $tool = tasMakeTool([
        'slug' => 'outil-vide-partage',
        'name' => 'Outil sans contenu',
        'description' => '',
        'answer_summary' => '',
    ]);

    $items = $tool->adminShareContents();

    expect($items)->toBeArray()->and(count($items))->toBeGreaterThanOrEqual(7);
    foreach ($items as $item) {
        expect(trim((string) $item['text']))->not->toBe('');
    }
});

it('un superadmin voit le menu admin de partage sur une page outil', function () {
    $admin = tasMakeSuperAdmin();
    $tool = tasMakeTool(['slug' => 'outil-gating-partage', 'name' => 'Outil gating']);

    $response = $this->actingAs($admin)->get('/outils/'.$tool->slug);

    $response->assertStatus(200);
    // Le menu admin-copy-menu est rendu (déclencheur + au moins un item copiable).
    $response->assertSee('ct-acm-trigger', escape: false);
    $response->assertSee('ct-acm-item', escape: false);
    // Le contenu Instagram (texte de l'item, dans le <textarea> source) est présent.
    $response->assertSee('IntelligenceArtificielle', escape: false);
});

it('un invité ne voit pas le menu admin de partage', function () {
    $tool = tasMakeTool(['slug' => 'outil-invite-partage', 'name' => 'Outil invité']);

    $response = $this->get('/outils/'.$tool->slug);

    $response->assertStatus(200);
    $response->assertDontSee('ct-acm-trigger', escape: false);
});
