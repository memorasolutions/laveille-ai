<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACTION: tests du disjoncteur budgétaire (`ai.monthly_budget`) sur AiService::chatWithHistory()
 * RAISON: `checkBudget()` n'était appelé QUE dans l'ancienne méthode `chat()`, jamais dans
 *         `chatWithHistory()`/`performChatRequest()` — le point d'appel HTTP réel utilisé par
 *         les 4 fonctionnalités IA de l'Académie (Tuteur, Feedback, Authoring, Traduction).
 *         Le réglage était donc inopérant pour elles (risque de DoS financier). Couvre :
 *   (a) budget dépassé → chatWithHistory() lève AiBudgetExceededException, AUCUN appel HTTP ;
 *   (b) budget sous le seuil → comportement normal inchangé (zéro régression) ;
 *   (c) l'exception remonte proprement côté Livewire (AcademyTutorService/TutorChat) —
 *       message utilisateur clair, jamais une erreur 500 brute.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\AI\Exceptions\AiBudgetExceededException;
use Modules\AI\Models\AiMessage;
use Modules\AI\Services\AiService;
use Modules\Academy\Livewire\TutorChat;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Settings\Models\Setting;
use App\Models\User;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Setting::set('ai.openrouter_api_key', 'test-key', 'string', 'ai');
    Setting::set('ai.chatbot_model', 'meta-llama/llama-3.3-70b-instruct:free', 'string', 'ai');
    Setting::set('ai.temperature', '0.7', 'number', 'ai');
    Setting::set('ai.max_tokens', '2048', 'number', 'ai');
});

it('budget mensuel dépassé : chatWithHistory() lève AiBudgetExceededException et n\'appelle jamais OpenRouter', function () {
    Setting::set('ai.monthly_budget', '0.01', 'number', 'ai');

    // Dépense déjà enregistrée ce mois-ci : 10 000 tokens × 0,000002 $ = 0,02 $ > budget 0,01 $.
    AiMessage::factory()->create(['tokens' => 10000, 'created_at' => now()]);

    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'ne devrait jamais être appelé']]]]),
    ]);

    $service = app(AiService::class);

    expect(fn () => $service->chatWithHistory([
        ['role' => 'user', 'content' => 'Bonjour'],
    ]))->toThrow(AiBudgetExceededException::class);

    Http::assertNothingSent();
});

it('budget sous le seuil : chatWithHistory() se comporte normalement (zéro régression)', function () {
    Setting::set('ai.monthly_budget', '100', 'number', 'ai');

    AiMessage::factory()->create(['tokens' => 50, 'created_at' => now()]);

    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'Réponse normale']]]]),
    ]);

    $service = app(AiService::class);

    $result = $service->chatWithHistory([
        ['role' => 'user', 'content' => 'Bonjour'],
    ]);

    expect($result)->toBe('Réponse normale');
    Http::assertSentCount(1);
});

it('budget désactivé (0 = illimité) : comportement inchangé (défaut)', function () {
    Setting::set('ai.monthly_budget', '0', 'number', 'ai');

    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'Réponse normale']]]]),
    ]);

    $service = app(AiService::class);

    $result = $service->chatWithHistory([
        ['role' => 'user', 'content' => 'Bonjour'],
    ]);

    expect($result)->toBe('Réponse normale');
    Http::assertSentCount(1);
});

it('budget dépassé : le Tuteur IA Academy remonte un message clair côté Livewire, jamais une 500', function () {
    Setting::set('ai.monthly_budget', '0.01', 'number', 'ai');
    AiMessage::factory()->create(['tokens' => 10000, 'created_at' => now()]);

    Http::fake([
        'openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'ne devrait jamais être appelé']]]]),
    ]);

    $user    = User::factory()->create();
    $course  = Course::create([
        'slug' => 'cours-budget', 'title' => 'Cours test', 'language' => 'fr',
        'level' => 'beginner', 'visibility' => 'public', 'access_type' => 'free', 'status' => 'published',
    ]);
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Chapitre test', 'position' => 1]);
    $lesson  = Lesson::create(['chapter_id' => $chapter->id, 'title' => 'Leçon test', 'slug' => 'lecon-budget', 'position' => 1]);
    Enrollment::create(['user_id' => $user->id, 'course_id' => $course->id, 'status' => 'active', 'source' => 'manual', 'enrolled_at' => now()]);

    $component = Livewire::actingAs($user)
        ->test(TutorChat::class, ['lesson' => $lesson, 'course' => $course])
        ->set('question', 'Explique-moi la leçon svp')
        ->call('sendQuestion')
        ->assertOk(); // Pas de 500 : la requête Livewire répond normalement.

    $messages = $component->get('messages');
    $lastMessage = end($messages);

    expect($lastMessage['role'])->toBe('assistant')
        ->and($lastMessage['error'])->toBeTrue()
        ->and($lastMessage['content'])->not->toBeEmpty();

    Http::assertNothingSent();
});
