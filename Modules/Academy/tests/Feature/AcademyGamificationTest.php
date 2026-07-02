<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Gamification moderne (XP / niveaux / classement de cohorte, LMS 2026).
 *
 * Prouve que :
 *  - le drapeau academy.gamification_enabled OFF (défaut) ne crédite AUCUN XP ;
 *  - compléter une leçon crédite l'XP UNE seule fois (idempotent, anti-triche) ;
 *  - le niveau progresse avec le total d'XP (courbe croissante) ;
 *  - le classement d'une cohorte est STRICTEMENT scopé (un apprenant d'une autre
 *    cohorte n'y apparaît jamais) ;
 *  - un apprenant retiré du classement (opt-out Loi 25 QC) en est absent ;
 *  - RÉGRESSION Scénario F (simulation croisée 2026-07-02) : le widget Livewire
 *    respecte le gating par palier d'abonnement (TierGateService) sans jamais
 *    500 (racine Blade unique conservée que le widget soit affiché ou masqué).
 *
 * Autonome : helpers préfixés `gami`, aucune redéclaration d'une fonction d'un
 * autre fichier de test. Garde-fou : si le module Academy est désactivé, tous
 * les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\Gamification;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Cohort;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\SubscriptionTier;
use Modules\Academy\Models\XpEvent;
use Modules\Academy\Services\CompletionService;
use Modules\Academy\Services\GamificationService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    // Défaut EXPLICITE : chaque test active le drapeau lui-même quand nécessaire
    // (le test « drapeau off » vérifie justement le défaut réel de la config).
    config(['academy.gamification_enabled' => false]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers gami (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

/** Crée un cours gratuit publié minimal. */
function gamiCourse(string $slug = 'gami-cours'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Gami Cours',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

/** Crée un item de leçon requis (document) dans un cours donné. */
function gamiLessonItem(Course $course, string $suffix = '1'): LessonItem
{
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Chapitre', 'position' => 1]);
    $lesson  = Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon',
        'slug'       => "gami-lecon-{$suffix}-{$course->id}",
        'position'   => 1,
    ]);

    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'document',
        'title'       => 'Item',
        'position'    => 1,
        'is_required' => true,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// (a) Drapeau OFF (défaut) = AUCUN XP crédité
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau gamification OFF (défaut) ne crédite aucun XP à la complétion d\'une leçon', function (): void {
    config(['academy.gamification_enabled' => false]);

    $user   = User::factory()->create();
    $course = gamiCourse();
    $item   = gamiLessonItem($course);

    CompletionService::markComplete($user, $item);

    expect(XpEvent::count())->toBe(0);
    expect((new GamificationService())->totalPointsFor($user))->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// (b) Compléter une leçon crédite l'XP UNE fois (idempotent, anti-triche)
// ─────────────────────────────────────────────────────────────────────────────

test('compléter une leçon crédite l\'XP une seule fois même en rejouant l\'action', function (): void {
    config(['academy.gamification_enabled' => true]);

    $user   = User::factory()->create();
    $course = gamiCourse();
    $item   = gamiLessonItem($course);
    $service = new GamificationService();

    // 1re complétion : crédite lesson_completed (+ bonus de série du jour).
    CompletionService::markComplete($user, $item);

    $lessonEvents = XpEvent::where('user_id', $user->id)->where('reason', 'lesson_completed')->count();
    expect($lessonEvents)->toBe(1);
    expect($service->totalPointsFor($user, $course))->toBeGreaterThanOrEqual(GamificationService::POINTS_LESSON_COMPLETED);

    // Rejouer l'action (markComplete est déjà idempotent : la Completion existe,
    // il retourne tôt SANS ré-émettre l'événement) : toujours UN SEUL crédit.
    CompletionService::markComplete($user, $item);

    $lessonEventsAfter = XpEvent::where('user_id', $user->id)->where('reason', 'lesson_completed')->count();
    expect($lessonEventsAfter)->toBe(1);

    // Anti-triche direct : re-appeler award() avec EXACTEMENT la même clé ne crée
    // jamais de 2e ligne, quel que soit le point d'entrée appelant.
    $completion = Completion::where('user_id', $user->id)->where('lesson_item_id', $item->id)->first();
    $again = $service->award($user, $course, 'completion', $completion->id, 'lesson_completed', GamificationService::POINTS_LESSON_COMPLETED);
    expect($again)->toBeNull();
    expect(XpEvent::where('user_id', $user->id)->where('reason', 'lesson_completed')->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// (c) Le niveau progresse avec le total d'XP
// ─────────────────────────────────────────────────────────────────────────────

test('le niveau augmente avec le total d\'XP (courbe croissante)', function (): void {
    $service = new GamificationService();

    expect($service->levelFor(0))->toBe(1);

    $levelAt0   = $service->levelFor(0);
    $levelAt50  = $service->levelFor(50);
    $levelAt200 = $service->levelFor(500);

    expect($levelAt50)->toBeGreaterThanOrEqual($levelAt0);
    expect($levelAt200)->toBeGreaterThan($levelAt50);

    // progressFor() expose la même courbe pour l'affichage (barre + palier).
    config(['academy.gamification_enabled' => true]);
    $user   = User::factory()->create();
    $course = gamiCourse();
    $service->award($user, $course, 'manual', 1, 'test_xp_1', 500);

    $progress = $service->progressFor($user, $course);
    expect($progress['total'])->toBe(500);
    expect($progress['level'])->toBe($service->levelFor(500));
    expect($progress['percent'])->toBeGreaterThanOrEqual(0)->toBeLessThanOrEqual(100);
});

// ─────────────────────────────────────────────────────────────────────────────
// (d) Classement scopé cohorte : un apprenant ne voit pas une autre cohorte
// ─────────────────────────────────────────────────────────────────────────────

test('le classement d\'une cohorte est strictement scopé à ses membres', function (): void {
    config(['academy.gamification_enabled' => true]);

    $service = new GamificationService();
    $course  = gamiCourse();

    $cohortA = Cohort::create(['course_id' => $course->id, 'name' => 'Cohorte A']);
    $cohortB = Cohort::create(['course_id' => $course->id, 'name' => 'Cohorte B']);

    $alice = User::factory()->create();
    $bob   = User::factory()->create();
    $carol = User::factory()->create();

    $cohortA->members()->attach([$alice->id, $bob->id]);
    $cohortB->members()->attach([$carol->id]);

    $service->award($alice, $course, 'manual', 1, 'test_xp_alice', 100);
    $service->award($bob, $course, 'manual', 1, 'test_xp_bob', 40);
    $service->award($carol, $course, 'manual', 1, 'test_xp_carol', 999);

    $leaderboardA = $service->leaderboardForCohort($cohortA->fresh());

    // Alice et Bob (cohorte A) présents, JAMAIS Carol (cohorte B) — même si son
    // score serait le plus haut, elle n'appartient pas à cette cohorte.
    $userIdsA = $leaderboardA->pluck('user.id')->all();
    expect($userIdsA)->toContain($alice->id, $bob->id);
    expect($userIdsA)->not->toContain($carol->id);

    // Tri décroissant + rangs continus 1..N.
    expect($leaderboardA->first()['user']->id)->toBe($alice->id);
    expect($leaderboardA->first()['rank'])->toBe(1);
    expect($leaderboardA->last()['rank'])->toBe($leaderboardA->count());

    // Cohorte B : uniquement Carol.
    $leaderboardB = $service->leaderboardForCohort($cohortB->fresh());
    expect($leaderboardB->pluck('user.id')->all())->toBe([$carol->id]);
});

// ─────────────────────────────────────────────────────────────────────────────
// (e) Opt-out (Loi 25 QC) = absent du classement
// ─────────────────────────────────────────────────────────────────────────────

test('un apprenant retiré du classement (opt-out) en est absent', function (): void {
    config(['academy.gamification_enabled' => true]);

    $service = new GamificationService();
    $course  = gamiCourse();
    $cohort  = Cohort::create(['course_id' => $course->id, 'name' => 'Cohorte Opt-out']);

    $alice = User::factory()->create();
    $bob   = User::factory()->create();
    $cohort->members()->attach([$alice->id, $bob->id]);

    $service->award($alice, $course, 'manual', 1, 'test_xp_alice_optout', 80);
    $service->award($bob, $course, 'manual', 1, 'test_xp_bob_optout', 60);

    // Par défaut, tout le monde est visible.
    expect($service->isLeaderboardVisible($alice))->toBeTrue();

    $service->setLeaderboardVisibility($alice, false);

    expect($service->isLeaderboardVisible($alice))->toBeFalse();

    $leaderboard = $service->leaderboardForCohort($cohort->fresh());
    $userIds     = $leaderboard->pluck('user.id')->all();

    expect($userIds)->not->toContain($alice->id);
    expect($userIds)->toContain($bob->id);

    // Alice peut se réinscrire au classement à tout moment.
    $service->setLeaderboardVisibility($alice, true);
    expect($service->leaderboardForCohort($cohort->fresh())->pluck('user.id')->all())->toContain($alice->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// (f) RÉGRESSION Scénario F : gating par palier d'abonnement, jamais de 500
// ─────────────────────────────────────────────────────────────────────────────

test('palier SANS la fonctionnalité gamification masque le widget sans jamais planter', function (): void {
    config(['academy.gamification_enabled' => true]);
    config(['academy.subscription_tiers_enabled' => true]);
    config(['academy.subscription_tier_feature_keys' => [
        'academy_gamification' => 'Gamification',
    ]]);

    // Palier par défaut (freemium) SANS la fonctionnalité — aucune assignation.
    SubscriptionTier::create([
        'name'       => 'Freemium',
        'slug'       => 'freemium-gami-'.uniqid(),
        'features'   => [],
        'is_default' => true,
        'is_active'  => true,
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Gamification::class)
        ->assertOk()
        ->assertSet('enabled', false)
        ->assertDontSee('Ma progression');
});

test('palier AVEC la fonctionnalité gamification affiche le widget normalement', function (): void {
    config(['academy.gamification_enabled' => true]);
    config(['academy.subscription_tiers_enabled' => true]);
    config(['academy.subscription_tier_feature_keys' => [
        'academy_gamification' => 'Gamification',
    ]]);

    SubscriptionTier::create([
        'name'       => 'Pro',
        'slug'       => 'pro-gami-'.uniqid(),
        'features'   => ['academy_gamification'],
        'is_default' => true,
        'is_active'  => true,
    ]);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Gamification::class)
        ->assertOk()
        ->assertSet('enabled', true)
        ->assertSee('Ma progression');
});
