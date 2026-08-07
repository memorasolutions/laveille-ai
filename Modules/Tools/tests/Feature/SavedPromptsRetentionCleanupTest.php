<?php
declare(strict_types=1);
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Settings\Models\Setting;
use Modules\Tools\Models\SavedPrompt;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

/**
 * Mission #1414 (2026-08-07) : les prompts supprimés par leurs propriétaires (soft delete sur
 * saved_prompts, cf. SavedPromptController::destroy()) n'étaient jamais purgés définitivement.
 * app:cleanup (Modules\Core\Console\CleanupOldRecords) purge désormais la corbeille via le
 * réglage retention.saved_prompts_trashed_days (défaut 30 j), même patron que les autres
 * réglages de rétention existants (login_attempts, sent_emails, activity_log, blocked_ips).
 */
function makeTrashedSavedPromptForRetentionTest(User $user, int $deletedDaysAgo): SavedPrompt
{
    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt corbeille',
        'prompt_text' => 'Texte du prompt',
    ]);

    // delete() applique le soft delete (SoftDeletes) puis on recule deleted_at manuellement pour
    // simuler une suppression ancienne, sans dépendre de l'horloge réelle du test.
    $prompt->delete();
    DB::table('saved_prompts')->where('id', $prompt->id)->update([
        'deleted_at' => now()->subDays($deletedDaysAgo),
    ]);

    return $prompt;
}

it('force-supprime un prompt en corbeille depuis plus de 30 jours (réglage par défaut)', function () {
    $user = User::factory()->create();
    $prompt = makeTrashedSavedPromptForRetentionTest($user, 31);

    Artisan::call('app:cleanup');

    expect(SavedPrompt::withTrashed()->find($prompt->id))->toBeNull();
});

it('conserve un prompt supprimé hier, dans le délai de rétention', function () {
    $user = User::factory()->create();
    $prompt = makeTrashedSavedPromptForRetentionTest($user, 1);

    Artisan::call('app:cleanup');

    $survivor = SavedPrompt::withTrashed()->find($prompt->id);
    expect($survivor)->not->toBeNull();
    expect($survivor->trashed())->toBeTrue();
});

it('ne touche jamais un prompt actif (jamais supprimé), même très ancien', function () {
    $user = User::factory()->create();
    $prompt = SavedPrompt::create([
        'user_id' => $user->id,
        'name' => 'Prompt actif ancien',
        'prompt_text' => 'Texte actif',
    ]);
    // created_at/updated_at très anciens, mais deleted_at reste NULL : jamais un candidat à la
    // purge (WHERE deleted_at < X exclut nativement les lignes NULL en SQL).
    DB::table('saved_prompts')->where('id', $prompt->id)->update([
        'created_at' => now()->subDays(500),
        'updated_at' => now()->subDays(500),
    ]);

    Artisan::call('app:cleanup');

    $prompt->refresh();
    expect($prompt->exists)->toBeTrue();
    expect($prompt->trashed())->toBeFalse();
});

it('respecte le réglage retention.saved_prompts_trashed_days modifié via le patron existant (table settings)', function () {
    $user = User::factory()->create();

    // Réglage réduit à 1 jour : un prompt en corbeille depuis 2 jours devient éligible, alors
    // qu'il aurait survécu sous le défaut de 30 jours (cf. test précédent avec 1 jour de corbeille).
    Setting::set('retention.saved_prompts_trashed_days', '1', 'number', 'retention');

    $prompt = makeTrashedSavedPromptForRetentionTest($user, 2);

    Artisan::call('app:cleanup');

    expect(SavedPrompt::withTrashed()->find($prompt->id))->toBeNull();
});

it('affiche la ligne saved_prompts dans le tableau de bord admin Rétention des données', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('admin.data-retention'))
        ->assertOk()
        ->assertSee('Prompts supprimés (corbeille)')
        ->assertSee('saved_prompts');
});
