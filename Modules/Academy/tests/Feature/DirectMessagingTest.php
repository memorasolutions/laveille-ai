<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Messagerie directe (DM) formateur <-> apprenant (parité Moodle).
 * Prouve :
 *  - drapeau désactivé (défaut) : routes/composants 404 ;
 *  - CAS POSITIF : formateur et apprenant du MÊME cours peuvent s'échanger ;
 *  - CAS NÉGATIF OBLIGATOIRE : deux apprenants SANS cours commun NE PEUVENT
 *    PAS s'envoyer de message (échoue si l'autorisation est cassée) ;
 *  - IDOR : un utilisateur A ne peut ni lire ni écrire dans une conversation
 *    dont il n'est pas participant, même en connaissant son id ;
 *  - rate limiting anti-spam ;
 *  - validation du contenu (longueur max, HTML échappé/dépouillé).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\DirectMessages\ConversationThread;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\DirectMessage;
use Modules\Academy\Models\DirectMessageConversation;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Services\DirectMessageService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.direct_messaging_enabled', true);
    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers autonomes (préfixe dm)
// ─────────────────────────────────────────────────────────────────────────────

function dmCourse(string $slug): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours ' . $slug,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function dmInstructor(Course $course, string $email): User
{
    $u = User::factory()->create(['email' => $email, 'name' => 'Formateur ' . $email]);
    $u->assignRole('instructor');

    CourseRole::create(['course_id' => $course->id, 'user_id' => $u->id, 'role' => 'instructor']);

    return $u;
}

function dmStudent(Course $course, string $email, string $status = 'active'): User
{
    $u = User::factory()->create(['email' => $email, 'name' => 'Etudiant ' . $email]);
    $u->assignRole('student');

    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $u->id,
        'status'      => $status,
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);

    return $u;
}

function dmService(): DirectMessageService
{
    return app(DirectMessageService::class);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. DRAPEAU DÉSACTIVÉ (défaut) : tout est fermé
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau desactive par defaut : le composant liste 404', function (): void {
    config()->set('academy.direct_messaging_enabled', false);

    $course     = dmCourse('flag-off');
    $instructor = dmInstructor($course, 'i-flag-off@example.test');

    $this->actingAs($instructor);

    Livewire::test(\Modules\Academy\Livewire\DirectMessages\ConversationList::class)
        ->assertStatus(404);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. CAS POSITIF — formateur et apprenant du MÊME cours peuvent échanger
// ─────────────────────────────────────────────────────────────────────────────

test('formateur et apprenant du meme cours peuvent echanger des messages', function (): void {
    $course     = dmCourse('cours-positif');
    $instructor = dmInstructor($course, 'formateur@example.test');
    $student    = dmStudent($course, 'apprenant@example.test');

    expect(DirectMessageConversation::canMessage($instructor, $student))->toBeTrue();

    $conversation = dmService()->openConversation($instructor, $student);

    expect($conversation)->not->toBeNull();
    expect($conversation->course_id)->toBe($course->id);

    $message = dmService()->send($conversation, $instructor, 'Bonjour, comment allez-vous ?');

    expect($message)->not->toBeNull();
    expect($message->recipient_id)->toBe($student->id);
    expect($message->body)->toBe('Bonjour, comment allez-vous ?');

    // Réponse de l'apprenant dans le même fil.
    $reply = dmService()->send($conversation, $student, 'Très bien, merci !');
    expect($reply)->not->toBeNull();
    expect($reply->recipient_id)->toBe($instructor->id);

    expect(DirectMessage::where('conversation_id', $conversation->id)->count())->toBe(2);
});

test('via Livewire : le formateur peut envoyer un message a son apprenant inscrit', function (): void {
    $course     = dmCourse('cours-livewire');
    $instructor = dmInstructor($course, 'formateur-lw@example.test');
    $student    = dmStudent($course, 'apprenant-lw@example.test');

    $conversation = dmService()->openConversation($instructor, $student);

    $this->actingAs($instructor);

    Livewire::test(ConversationThread::class, ['conversation' => $conversation])
        ->set('body', 'Message envoyé via le composant Livewire.')
        ->call('sendMessage')
        ->assertHasNoErrors();

    expect(DirectMessage::where('conversation_id', $conversation->id)->where('sender_id', $instructor->id)->exists())
        ->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. CAS NÉGATIF OBLIGATOIRE — deux apprenants SANS cours commun
// ─────────────────────────────────────────────────────────────────────────────

test('deux apprenants SANS cours commun NE PEUVENT PAS echanger de message', function (): void {
    $courseA = dmCourse('cours-a-negatif');
    $courseB = dmCourse('cours-b-negatif');

    $studentA = dmStudent($courseA, 'etudiant-a@example.test');
    $studentB = dmStudent($courseB, 'etudiant-b@example.test');

    expect(DirectMessageConversation::canMessage($studentA, $studentB))->toBeFalse();

    expect(fn () => dmService()->openConversation($studentA, $studentB))
        ->toThrow(\RuntimeException::class);

    expect(DirectMessageConversation::count())->toBe(0);
});

test('deux apprenants du MEME cours (sans lien formateur) ne peuvent pas non plus echanger', function (): void {
    // Règle produit : la relation autorisée est TOUJOURS formateur<->apprenant,
    // jamais apprenant<->apprenant, même inscrits au même cours (anti-harcèlement).
    $course   = dmCourse('cours-pairs');
    $studentA = dmStudent($course, 'pair-a@example.test');
    $studentB = dmStudent($course, 'pair-b@example.test');

    expect(DirectMessageConversation::canMessage($studentA, $studentB))->toBeFalse();
});

test('formateur retire du cours perd le droit de continuer a echanger', function (): void {
    $course     = dmCourse('cours-retrait');
    $instructor = dmInstructor($course, 'formateur-retrait@example.test');
    $student    = dmStudent($course, 'apprenant-retrait@example.test');

    $conversation = dmService()->openConversation($instructor, $student);
    expect(dmService()->send($conversation, $instructor, 'Premier message'))->not->toBeNull();

    // L'apprenant se désinscrit (annulation) : la relation pédagogique disparaît.
    Enrollment::where('course_id', $course->id)->where('user_id', $student->id)->update(['status' => 'cancelled']);

    // Le fil existe toujours (historique conservé), mais l'envoi est refusé.
    $blocked = dmService()->send($conversation, $instructor, 'Message après désinscription');
    expect($blocked)->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. IDOR — accès/écriture strictement réservés aux 2 participants
// ─────────────────────────────────────────────────────────────────────────────

test('IDOR : un utilisateur hors conversation ne peut PAS lire le fil via Livewire', function (): void {
    $course     = dmCourse('cours-idor');
    $instructor = dmInstructor($course, 'formateur-idor@example.test');
    $student    = dmStudent($course, 'apprenant-idor@example.test');
    $intruder   = dmStudent(dmCourse('cours-idor-intrus'), 'intrus@example.test');

    $conversation = dmService()->openConversation($instructor, $student);
    dmService()->send($conversation, $instructor, 'Message privé');

    $this->actingAs($intruder);

    Livewire::test(ConversationThread::class, ['conversation' => $conversation])
        ->assertStatus(403);
});

test('IDOR : un utilisateur hors conversation ne peut PAS y envoyer de message via le service', function (): void {
    $course     = dmCourse('cours-idor-send');
    $instructor = dmInstructor($course, 'formateur-idor-send@example.test');
    $student    = dmStudent($course, 'apprenant-idor-send@example.test');
    $intruder   = dmStudent(dmCourse('cours-idor-send-intrus'), 'intrus-send@example.test');

    $conversation = dmService()->openConversation($instructor, $student);

    $blocked = dmService()->send($conversation, $intruder, 'Je m\'invite dans la conversation');

    expect($blocked)->toBeNull();
    expect(DirectMessage::where('conversation_id', $conversation->id)->where('sender_id', $intruder->id)->exists())
        ->toBeFalse();
});

test('IDOR : la route messages/{conversation} renvoie 403 pour un non-participant', function (): void {
    $course     = dmCourse('cours-idor-route');
    $instructor = dmInstructor($course, 'formateur-idor-route@example.test');
    $student    = dmStudent($course, 'apprenant-idor-route@example.test');
    $intruder   = dmStudent(dmCourse('cours-idor-route-intrus'), 'intrus-route@example.test');

    $conversation = dmService()->openConversation($instructor, $student);

    $this->actingAs($intruder);

    $response = $this->get(route('academy.messages.show', $conversation->id));
    $response->assertStatus(403);
});

test('Policy DirectMessageConversationPolicy refuse view/send a un non-participant', function (): void {
    $course     = dmCourse('cours-policy');
    $instructor = dmInstructor($course, 'formateur-policy@example.test');
    $student    = dmStudent($course, 'apprenant-policy@example.test');
    $intruder   = dmStudent(dmCourse('cours-policy-intrus'), 'intrus-policy@example.test');

    $conversation = dmService()->openConversation($instructor, $student);

    expect($instructor->can('view', $conversation))->toBeTrue();
    expect($student->can('view', $conversation))->toBeTrue();
    expect($intruder->can('view', $conversation))->toBeFalse();
    expect($intruder->can('send', $conversation))->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. RATE LIMITING + VALIDATION DU CONTENU
// ─────────────────────────────────────────────────────────────────────────────

test('rate limiting : au-dela du plafond, les messages supplementaires sont bloques', function (): void {
    $course     = dmCourse('cours-ratelimit');
    $instructor = dmInstructor($course, 'formateur-rl@example.test');
    $student    = dmStudent($course, 'apprenant-rl@example.test');

    $conversation = dmService()->openConversation($instructor, $student);

    for ($i = 0; $i < DirectMessageService::RATE_LIMIT_MAX; $i++) {
        expect(dmService()->send($conversation, $instructor, "Message {$i}"))->not->toBeNull();
    }

    // Le message excédentaire est bloqué.
    $blocked = dmService()->send($conversation, $instructor, 'Message de trop');
    expect($blocked)->toBeNull();
});

test('validation : un message HTML est depouille (aucun HTML brut stocke)', function (): void {
    $course     = dmCourse('cours-html');
    $instructor = dmInstructor($course, 'formateur-html@example.test');
    $student    = dmStudent($course, 'apprenant-html@example.test');

    $conversation = dmService()->openConversation($instructor, $student);

    $message = dmService()->send($conversation, $instructor, '<script>alert(1)</script>Bonjour');

    expect($message)->not->toBeNull();
    expect($message->body)->not->toContain('<script>');
    expect($message->body)->toContain('Bonjour');
});

test('validation : un message vide ou trop long est refuse', function (): void {
    $course     = dmCourse('cours-longueur');
    $instructor = dmInstructor($course, 'formateur-longueur@example.test');
    $student    = dmStudent($course, 'apprenant-longueur@example.test');

    $conversation = dmService()->openConversation($instructor, $student);

    expect(dmService()->send($conversation, $instructor, ''))->toBeNull();
    expect(dmService()->send($conversation, $instructor, str_repeat('a', DirectMessage::MAX_LENGTH + 1)))->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. ACCUSÉ DE LECTURE + LISTE DES CONTACTS AUTORISÉS
// ─────────────────────────────────────────────────────────────────────────────

test('accuse de lecture : marquer une conversation lue met a jour read_at', function (): void {
    $course     = dmCourse('cours-lecture');
    $instructor = dmInstructor($course, 'formateur-lecture@example.test');
    $student    = dmStudent($course, 'apprenant-lecture@example.test');

    $conversation = dmService()->openConversation($instructor, $student);
    $message      = dmService()->send($conversation, $instructor, 'Message non lu');

    expect($message->read_at)->toBeNull();

    dmService()->markConversationRead($conversation, $student);

    expect($message->fresh()->read_at)->not->toBeNull();
    expect(dmService()->unreadCountFor($student))->toBe(0);
});

test('allowedContactsFor ne propose que des contacts pedagogiquement lies', function (): void {
    $course        = dmCourse('cours-contacts');
    $otherCourse   = dmCourse('cours-contacts-autre');
    $instructor    = dmInstructor($course, 'formateur-contacts@example.test');
    $student       = dmStudent($course, 'apprenant-contacts@example.test');
    $unrelatedUser = dmStudent($otherCourse, 'sans-lien@example.test');

    $studentContacts = dmService()->allowedContactsFor($student);
    expect($studentContacts->pluck('id'))->toContain($instructor->id);
    expect($studentContacts->pluck('id'))->not->toContain($unrelatedUser->id);

    $instructorContacts = dmService()->allowedContactsFor($instructor);
    expect($instructorContacts->pluck('id'))->toContain($student->id);
    expect($instructorContacts->pluck('id'))->not->toContain($unrelatedUser->id);
});
