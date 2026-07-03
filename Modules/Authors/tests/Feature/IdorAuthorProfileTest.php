<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Authors\Livewire\AffiliateLinkManager;
use Modules\Authors\Livewire\AuthorActivityLogViewer;
use Modules\Authors\Livewire\AuthorAnalyticsWidget;
use Modules\Authors\Livewire\AuthorDashboard;
use Modules\Authors\Livewire\AuthorEditor;
use Modules\Authors\Livewire\AuthorRecentNotifications;
use Modules\Authors\Livewire\AuthorSettings;
use Modules\Authors\Livewire\ImageUploader;
use Modules\Authors\Models\AuthorProfile;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Audit IDOR (défense en profondeur) — Modules/Authors/app/Livewire/*.
 *
 * Aucun de ces composants n'est câblé sur une route publique authentifiée en
 * production aujourd'hui (seule une route de test gated `environment('local')`
 * existe : /auteur/test-dashboard/{authorProfileId}). Ces tests vérifient que,
 * le jour où ces composants seront réellement branchés, un utilisateur A ne
 * peut pas monter/agir sur les données du profil auteur d'un utilisateur B.
 */
function makeAuthorProfileIdor(): AuthorProfile
{
    $user = User::factory()->create(['email' => 'idor-'.Str::random(6).'@example.com']);

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 'idor-'.strtolower(Str::random(8)),
        'tier' => 'free',
    ]);
}

it('AuthorEditor mount rejects a foreign author profile with 403', function () {
    $owner = makeAuthorProfileIdor();
    $attacker = makeAuthorProfileIdor();

    $this->actingAs($attacker->user);

    Livewire::test(AuthorEditor::class, ['authorProfile' => $owner])
        ->assertStatus(403);
});

it('AuthorEditor mount succeeds for the profile owner', function () {
    $owner = makeAuthorProfileIdor();

    $this->actingAs($owner->user);

    Livewire::test(AuthorEditor::class, ['authorProfile' => $owner])
        ->assertStatus(200);
});

it('AffiliateLinkManager mount rejects a foreign authorProfileId with 403', function () {
    $owner = makeAuthorProfileIdor();
    $attacker = makeAuthorProfileIdor();

    $this->actingAs($attacker->user);

    Livewire::test(AffiliateLinkManager::class, ['authorProfileId' => $owner->id])
        ->assertStatus(403);
});

it('AuthorActivityLogViewer mount rejects a foreign authorProfileId with 403', function () {
    $owner = makeAuthorProfileIdor();
    $attacker = makeAuthorProfileIdor();

    $this->actingAs($attacker->user);

    Livewire::test(AuthorActivityLogViewer::class, ['authorProfileId' => $owner->id])
        ->assertStatus(403);
});

it('AuthorAnalyticsWidget mount rejects a foreign authorProfileId with 403', function () {
    $owner = makeAuthorProfileIdor();
    $attacker = makeAuthorProfileIdor();

    $this->actingAs($attacker->user);

    Livewire::test(AuthorAnalyticsWidget::class, ['authorProfileId' => $owner->id])
        ->assertStatus(403);
});

it('AuthorRecentNotifications mount rejects a foreign authorProfileId with 403', function () {
    $owner = makeAuthorProfileIdor();
    $attacker = makeAuthorProfileIdor();

    $this->actingAs($attacker->user);

    Livewire::test(AuthorRecentNotifications::class, ['authorProfileId' => $owner->id])
        ->assertStatus(403);
});

it('AuthorSettings mount rejects a foreign authorProfileId with 403', function () {
    $owner = makeAuthorProfileIdor();
    $attacker = makeAuthorProfileIdor();

    $this->actingAs($attacker->user);

    Livewire::test(AuthorSettings::class, ['authorProfileId' => $owner->id])
        ->assertStatus(403);
});

it('AuthorSettings mount without authorProfileId does not throw (no external id provided)', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(AuthorSettings::class)->assertStatus(200);
});

it('AuthorDashboard mount rejects a foreign authorProfileId with 403', function () {
    $owner = makeAuthorProfileIdor();
    $attacker = makeAuthorProfileIdor();

    $this->actingAs($attacker->user);

    Livewire::test(AuthorDashboard::class, ['authorProfileId' => $owner->id])
        ->assertStatus(403);
});

it('AuthorDashboard mount without authorProfileId does not throw (no external id provided)', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(AuthorDashboard::class)->assertStatus(200);
});

it('ImageUploader mount rejects a foreign authorProfileId with 403', function () {
    $owner = makeAuthorProfileIdor();
    $attacker = makeAuthorProfileIdor();

    $this->actingAs($attacker->user);

    Livewire::test(ImageUploader::class, ['authorProfileId' => $owner->id])
        ->assertStatus(403);
});
