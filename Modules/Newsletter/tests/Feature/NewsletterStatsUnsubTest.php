<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * Tests : distinction « désabonnements réels » vs « purges d'hygiene J+7 »
 * dans le tableau de bord /admin/newsletter/stats.
 *
 * Critere :
 *  - Purgé auto (bounce_reason = 'auto_purge_unconfirmed_j7') => hygiene_purges, PAS real_unsubs.
 *  - Abonné confirme qui se désabonne => real_unsubs, PAS hygiene_purges.
 *  - Abonné non confirme sans marqueur de purge => ni l'un ni l'autre (ni purgé, ni désabonné réel).
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Newsletter\Models\Subscriber;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ----------------------------------------------------------------
// Requetes directes (hors HTTP) pour tester l'agregation SQL
// ----------------------------------------------------------------

it("ne compte pas une purge J+7 comme desabonnement reel", function () {
    // Non-confirme, purge automatiquement apres 7 jours.
    Subscriber::create([
        'email'           => 'purge@example.com',
        'unsubscribed_at' => now(),
        'bounce_reason'   => 'auto_purge_unconfirmed_j7',
        // confirmed_at = null (jamais confirme)
    ]);

    $realUnsubs = Subscriber::whereNotNull('unsubscribed_at')
        ->whereNotNull('confirmed_at')
        ->where(static fn ($q) => $q
            ->whereNull('bounce_reason')
            ->orWhere('bounce_reason', '!=', 'auto_purge_unconfirmed_j7'))
        ->count();

    $hygienePurges = Subscriber::where('bounce_reason', 'auto_purge_unconfirmed_j7')
        ->count();

    expect($realUnsubs)->toBe(0, 'La purge J+7 ne doit pas apparaitre dans les desabonnements reels');
    expect($hygienePurges)->toBe(1, 'La purge J+7 doit etre comptee dans les purges hygiene');
});

it("compte un vrai desabonnement confirme comme desabonnement reel", function () {
    // Abonné qui a confirmé, puis s'est désabonné manuellement.
    Subscriber::create([
        'email'           => 'real-unsub@example.com',
        'confirmed_at'    => now()->subDays(30),
        'unsubscribed_at' => now(),
        'bounce_reason'   => null,
    ]);

    $realUnsubs = Subscriber::whereNotNull('unsubscribed_at')
        ->whereNotNull('confirmed_at')
        ->where(static fn ($q) => $q
            ->whereNull('bounce_reason')
            ->orWhere('bounce_reason', '!=', 'auto_purge_unconfirmed_j7'))
        ->count();

    $hygienePurges = Subscriber::where('bounce_reason', 'auto_purge_unconfirmed_j7')
        ->count();

    expect($realUnsubs)->toBe(1, 'Le desabonnement reel doit etre compte');
    expect($hygienePurges)->toBe(0, 'Aucune purge hygiene dans ce scenario');
});

it("distingue correctement les deux categories en presence des deux types", function () {
    // Scénario mixte : 3 purges J+7 + 2 vrais désabonnements + 1 abonné actif.
    foreach (range(1, 3) as $i) {
        Subscriber::create([
            'email'           => "purge{$i}@example.com",
            'unsubscribed_at' => now(),
            'bounce_reason'   => 'auto_purge_unconfirmed_j7',
        ]);
    }

    foreach (range(1, 2) as $i) {
        Subscriber::create([
            'email'           => "real{$i}@example.com",
            'confirmed_at'    => now()->subDays(10),
            'unsubscribed_at' => now(),
            'bounce_reason'   => null,
        ]);
    }

    Subscriber::create([
        'email'        => 'active@example.com',
        'confirmed_at' => now()->subDays(5),
    ]);

    $realUnsubs = Subscriber::whereNotNull('unsubscribed_at')
        ->whereNotNull('confirmed_at')
        ->where(static fn ($q) => $q
            ->whereNull('bounce_reason')
            ->orWhere('bounce_reason', '!=', 'auto_purge_unconfirmed_j7'))
        ->count();

    $hygienePurges = Subscriber::where('bounce_reason', 'auto_purge_unconfirmed_j7')
        ->count();

    expect($realUnsubs)->toBe(2);
    expect($hygienePurges)->toBe(3);
    // La somme des deux categories couvre tous les unsubscribed_at non null.
    expect($realUnsubs + $hygienePurges)->toBe(
        Subscriber::whereNotNull('unsubscribed_at')->count()
    );
});

it("ne compte pas un abonne confirme actif dans les desabonnements", function () {
    Subscriber::create([
        'email'        => 'actif@example.com',
        'confirmed_at' => now()->subDays(7),
    ]);

    $realUnsubs    = Subscriber::whereNotNull('unsubscribed_at')->whereNotNull('confirmed_at')->count();
    $hygienePurges = Subscriber::where('bounce_reason', 'auto_purge_unconfirmed_j7')->count();

    expect($realUnsubs)->toBe(0);
    expect($hygienePurges)->toBe(0);
});

// ----------------------------------------------------------------
// Test HTTP : la page stats est accessible et affiche les bons textes
// ----------------------------------------------------------------

it("la page admin newsletter stats affiche la distinction purges vs reels", function () {
    // Créer un admin authentifié.
    $user = \App\Models\User::factory()->create();
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'user']);
    $user->assignRole('admin');

    // 1 purge + 1 vrai désabonné.
    Subscriber::create([
        'email'           => 'purge-http@example.com',
        'unsubscribed_at' => now(),
        'bounce_reason'   => 'auto_purge_unconfirmed_j7',
    ]);
    Subscriber::create([
        'email'           => 'real-http@example.com',
        'confirmed_at'    => now()->subDays(5),
        'unsubscribed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/admin/newsletter/stats')
        ->assertStatus(200)
        ->assertSee('Désabonnements réels')
        ->assertSee('purge')
        ->assertSee('hygiene');
});
