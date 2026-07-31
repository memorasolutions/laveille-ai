<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Tools\Models\SavedPrompt;
use Modules\Tools\Models\Tool;
use Tests\TestCase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Round 48 (2026-07-27) : passe adversariale fraîche sur constructeur-prompts.
// created_at seul (granularité seconde, ->latest()) n'est pas un ordre déterministe garanti par
// SQL pour la pagination quand plusieurs lignes partagent exactement la même seconde de création
// (ex. importLocalCustomCards() qui poste des dizaines de prompts en rafale via Promise.all) - un
// utilisateur pouvait voir des prompts dupliqués ou absents en naviguant entre les pages de sa
// bibliothèque. Fix : ->orderByDesc('id') en tiebreaker secondaire.
//
// NOTE MÉTHODO : un test COMPORTEMENTAL (créer 25 lignes à la même seconde, comparer page 1/page 2)
// s'est avéré un FAUX NÉGATIF sous SQLite en test - le moteur préserve l'ordre d'insertion (rowid)
// pour des requêtes répétées sans écriture concurrente entre les deux appels, masquant totalement
// la régression au revert. Le test structurel ci-dessous inspecte la clause ORDER BY réellement
// générée (via le query log), ce qui catche bien la régression indépendamment du moteur SQL.

it('orders /api/prompts by created_at with an id tiebreaker (round 48, structural check via query log)', function () {
    $user = User::factory()->create();
    SavedPrompt::create(['user_id' => $user->id, 'name' => 'P1', 'prompt_text' => 'T1']);

    DB::enableQueryLog();
    $this->actingAs($user)->getJson('/api/prompts')->assertOk();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $selectQuery = collect($queries)->first(fn ($q) => str_contains($q['query'], 'from "saved_prompts"') && str_contains($q['query'], 'order by'));

    expect($selectQuery)->not->toBeNull();
    expect($selectQuery['query'])->toContain('"created_at" desc');
    expect($selectQuery['query'])->toContain('"id" desc');
});

it('orders /user/prompts by created_at with an id tiebreaker (round 48, structural check via query log)', function () {
    Tool::firstOrCreate(['slug' => 'constructeur-prompts'], [
        'name' => 'Constructeur de prompts',
        'description' => 'Test',
        'icon' => '✨',
        'is_active' => true,
        'is_under_construction' => false,
        'category' => 'productivite',
    ]);

    $user = User::factory()->create();
    SavedPrompt::create(['user_id' => $user->id, 'name' => 'P1', 'prompt_text' => 'T1']);

    DB::enableQueryLog();
    $this->actingAs($user)->get('/user/prompts')->assertOk();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $selectQuery = collect($queries)->first(fn ($q) => str_contains($q['query'], 'from "saved_prompts"') && str_contains($q['query'], 'order by'));

    expect($selectQuery)->not->toBeNull();
    expect($selectQuery['query'])->toContain('"created_at" desc');
    expect($selectQuery['query'])->toContain('"id" desc');
});
