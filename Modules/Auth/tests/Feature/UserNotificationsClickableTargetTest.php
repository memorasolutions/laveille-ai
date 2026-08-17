<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Correctif 2026-08-17 : les notifications de "Mon espace" (/user/notifications) n'offraient
 * qu'un bouton de suppression - aucun moyen de se rendre à l'élément concerné (l'outil ou la
 * ressource soumis) en un clic ; le propriétaire devait fouiller l'administration à la main.
 * Chaque notification stocke désormais une clé 'url' (dans le JSON déjà existant de la colonne
 * notifications.data - aucune migration requise) pointant vers l'écran d'ADMINISTRATION de
 * l'élément soumis (jamais la page publique, puisque c'est le modérateur qui la reçoit). Le
 * titre de la notification devient un lien vers cette cible quand elle existe ; les notifications
 * plus anciennes qui n'ont pas cette clé restent affichées sans lien, sans erreur.
 *
 * Corrige aussi la microcopie « Tout est à jour. » qui s'affichait au-dessus d'une liste de
 * notifications dès que le compteur de non-lues tombait à zéro (contradictoire quand des
 * notifications lues restaient affichées).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolResource;
use Modules\Directory\Notifications\ResourceSubmittedNotification;
use Modules\Directory\Notifications\ToolSubmittedNotification;

uses(Tests\TestCase::class, RefreshDatabase::class);

function makeClickableTargetTestTool(string $slug): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil Test Notification '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-outil.test';
    $tool->pricing = 'free';
    $tool->status = 'pending';
    $tool->save();
    $tool->refresh();

    return $tool;
}

// --- 1. Notification AVEC url stockée : le titre rend un lien vers cette cible ------------

test('a database notification with a url renders a link to that url', function () {
    $owner = User::factory()->create();
    $submitter = User::factory()->create(['name' => 'Andy Zhang']);

    $tool = makeClickableTargetTestTool('imgfast-clic');

    $owner->notify(new ToolSubmittedNotification($tool, $submitter));

    $expectedUrl = url('/admin/directory/'.$tool->getKey().'/edit');

    $response = $this->actingAs($owner)->get(route('user.notifications'));

    $response->assertOk();
    $response->assertSee($tool->name, false);
    $response->assertSee('href="'.$expectedUrl.'"', false);
});

// --- 2. Notification SANS url (ancienne) : s'affiche sans lien, sans erreur ----------------

test('a legacy database notification without a url renders without a link and without error', function () {
    $owner = User::factory()->create();

    DatabaseNotification::create([
        'id' => (string) Str::uuid(),
        'type' => 'Modules\\Directory\\Notifications\\LegacySubmissionNotification',
        'notifiable_type' => get_class($owner),
        'notifiable_id' => $owner->id,
        'data' => [
            'message' => 'Ancienne notification sans URL cible',
        ],
        'read_at' => null,
    ]);

    $response = $this->actingAs($owner)->get(route('user.notifications'));

    $response->assertOk();
    $response->assertSee('Ancienne notification sans URL cible', false);

    // L'ancre qui entoure ce titre ne doit porter aucun attribut href.
    preg_match(
        '/<a([^>]*)>\s*<p[^>]*>\s*Ancienne notification sans URL cible/s',
        $response->getContent(),
        $matches
    );

    expect($matches)->toHaveCount(2);
    expect($matches[1])->not->toContain('href');
});

// --- 3. Microcopie « Tout est à jour. » -----------------------------------------------------

test('tout est a jour is shown only when there are zero notifications', function () {
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)->get(route('user.notifications'));

    $response->assertOk();
    $response->assertSee('Tout est à jour.');
});

test('tout est a jour is absent and a sober count is shown when notifications exist but all are read', function () {
    $owner = User::factory()->create();
    $submitter = User::factory()->create();

    $toolA = makeClickableTargetTestTool('sober-count-a');
    $toolB = makeClickableTargetTestTool('sober-count-b');

    $owner->notify(new ToolSubmittedNotification($toolA, $submitter));
    $owner->notify(new ToolSubmittedNotification($toolB, $submitter));
    $owner->unreadNotifications->markAsRead();

    $response = $this->actingAs($owner)->get(route('user.notifications'));

    $response->assertOk();
    $response->assertDontSee('Tout est à jour.');
    $response->assertSee('2 notifications', false);
});

test('unread count message is shown and tout est a jour is absent when there are unread notifications', function () {
    $owner = User::factory()->create();
    $submitter = User::factory()->create();

    $tool = makeClickableTargetTestTool('unread-count');
    $owner->notify(new ToolSubmittedNotification($tool, $submitter));

    $response = $this->actingAs($owner)->get(route('user.notifications'));

    $response->assertOk();
    $response->assertDontSee('Tout est à jour.');
    $response->assertSee('non lue', false);
});

// --- 4. Chaque point d'émission modifié renseigne bien l'URL admin -------------------------

test('ToolSubmittedNotification stores the admin edit url of the submitted tool', function () {
    $submitter = User::factory()->create();
    $tool = makeClickableTargetTestTool('emission-tool');

    $notification = new ToolSubmittedNotification($tool, $submitter);
    $data = $notification->toArray($submitter);

    expect($data['url'])->toBe(url('/admin/directory/'.$tool->getKey().'/edit'));
});

test('ResourceSubmittedNotification stores the admin moderation queue url', function () {
    $submitter = User::factory()->create();
    $tool = makeClickableTargetTestTool('emission-resource');

    $resource = ToolResource::create([
        'directory_tool_id' => $tool->id,
        'user_id' => $submitter->id,
        'url' => 'https://exemple-ressource.test/video',
        'title' => 'Tutoriel de test',
        'type' => 'video',
        'is_approved' => false,
    ]);

    $notification = new ResourceSubmittedNotification($resource);
    $data = $notification->toArray($submitter);

    expect($data['url'])->toBe(route('admin.directory.moderation'));
});
