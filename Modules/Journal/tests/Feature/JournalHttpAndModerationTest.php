<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 *
 * Tests Pest — routes HTTP du module Journal (index/create/store/show/edit/
 * destroy/quick-add), intégration avec le système de signalement générique
 * (Modules\Community) et l'avis-et-avis droit d'auteur (Modules\Directory).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Community\Http\Controllers\ReportController;
use Modules\Community\Models\Report;
use Modules\Journal\Models\Journal;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

function jhAdmin(): User
{
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'journal_http_test_admin', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::firstOrCreate(['name' => 'view_admin_panel', 'guard_name' => 'web']));
    $user->assignRole($role);

    return $user;
}

test('create/store creates a journal owned by the current user and redirects to edit', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('journal.store'), [
        'title' => 'Mon premier journal',
        'template' => 'classique',
    ]);

    $journal = Journal::where('user_id', $user->id)->first();
    expect($journal)->not->toBeNull();
    expect($journal->title)->toBe('Mon premier journal');
    expect($journal->is_published)->toBeFalse();
    $response->assertRedirect(route('journal.edit', $journal));
});

test('store rejects an invalid template', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('journal.store'), ['title' => 'X', 'template' => 'inexistant'])
        ->assertSessionHasErrors('template');
});

test('index (Mes journaux) only lists the current user own journals', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    Journal::create(['user_id' => $owner->id, 'title' => 'Journal Alpha HTTP', 'slug' => 'alpha-http', 'journal_date' => now()->toDateString(), 'template' => 'classique', 'is_published' => false]);
    Journal::create(['user_id' => $other->id, 'title' => 'Journal Bravo HTTP', 'slug' => 'bravo-http', 'journal_date' => now()->toDateString(), 'template' => 'classique', 'is_published' => false]);

    $this->actingAs($owner)
        ->get(route('journal.index'))
        ->assertOk()
        ->assertSee('Journal Alpha HTTP')
        ->assertDontSee('Journal Bravo HTTP');
});

test('edit route enforces ownership (403 for a stranger)', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $journal = Journal::create(['user_id' => $owner->id, 'title' => 'Journal edit HTTP', 'slug' => 'edit-http', 'journal_date' => now()->toDateString(), 'template' => 'classique', 'is_published' => false]);

    $this->actingAs($owner)->get(route('journal.edit', $journal))->assertOk();
    $this->actingAs($stranger)->get(route('journal.edit', $journal))->assertForbidden();
});

test('public show: guest sees a published journal, gets 403 on a draft, owner always sees their draft', function () {
    $owner = User::factory()->create();
    $published = Journal::create(['user_id' => $owner->id, 'title' => 'Journal publié HTTP', 'slug' => 'publie-http', 'journal_date' => now()->toDateString(), 'template' => 'classique', 'is_published' => true]);
    $draft = Journal::create(['user_id' => $owner->id, 'title' => 'Journal brouillon HTTP', 'slug' => 'brouillon-http', 'journal_date' => now()->toDateString(), 'template' => 'classique', 'is_published' => false]);

    $this->get(route('journal.show', $published))->assertOk()->assertSee('Journal publié HTTP');
    $this->get(route('journal.show', $draft))->assertForbidden();
    $this->actingAs($owner)->get(route('journal.show', $draft))->assertOk()->assertSee('brouillon privé');
});

test('destroy enforces ownership and actually deletes on success', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $journal = Journal::create(['user_id' => $owner->id, 'title' => 'Journal destroy HTTP', 'slug' => 'destroy-http', 'journal_date' => now()->toDateString(), 'template' => 'classique', 'is_published' => false]);

    $this->actingAs($stranger)->delete(route('journal.destroy', $journal))->assertForbidden();
    expect(Journal::find($journal->id))->not->toBeNull();

    $this->actingAs($owner)
        ->delete(route('journal.destroy', $journal))
        ->assertRedirect(route('journal.index'));
    expect(Journal::find($journal->id))->toBeNull();
});

test('quick-add adds a news source block only to a journal the caller owns', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $journal = Journal::create(['user_id' => $owner->id, 'title' => 'Journal quick-add HTTP', 'slug' => 'quickadd-http', 'journal_date' => now()->toDateString(), 'template' => 'classique', 'is_published' => false]);

    $source = NewsSource::create(['name' => 'Source HTTP', 'url' => 'https://http-test.exemple.com/rss', 'language' => 'fr', 'active' => true]);
    $article = NewsArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Article HTTP',
        'guid' => 'guid-http-1',
        'url' => 'https://exemple.com/http-1',
        'description' => 'Description HTTP',
        'slug' => 'article-http-1',
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ]);

    $this->actingAs($owner)
        ->postJson(route('journal.quick-add'), ['journal_id' => $journal->id, 'source_type' => 'news', 'source_id' => $article->id])
        ->assertOk()->assertJson(['ok' => true]);
    expect($journal->fresh()->blocks()->count())->toBe(1);

    $this->actingAs($stranger)
        ->postJson(route('journal.quick-add'), ['journal_id' => $journal->id, 'source_type' => 'news', 'source_id' => $article->id])
        ->assertForbidden();
    expect($journal->fresh()->blocks()->count())->toBe(1);
});

test('a journal can be reported with the legal taxonomy and appears in the admin moderation queue', function () {
    $owner = User::factory()->create();
    $reporter = User::factory()->create();
    $admin = jhAdmin();
    $journal = Journal::create(['user_id' => $owner->id, 'title' => 'Journal signalé HTTP', 'slug' => 'signale-http', 'journal_date' => now()->toDateString(), 'template' => 'classique', 'is_published' => true]);

    $this->actingAs($reporter)
        ->postJson(route('report.store'), [
            'reportable_type' => 'Modules\\Journal\\Models\\Journal',
            'reportable_id' => $journal->id,
            'reason' => 'Motif hors taxonomie',
        ])
        ->assertStatus(422);

    $this->actingAs($reporter)
        ->postJson(route('report.store'), [
            'reportable_type' => 'Modules\\Journal\\Models\\Journal',
            'reportable_id' => $journal->id,
            'reason' => ReportController::REASONS[0],
        ])
        ->assertOk()->assertJson(['success' => true]);

    $report = Report::where('reportable_type', 'Modules\\Journal\\Models\\Journal')->where('reportable_id', $journal->id)->first();
    expect($report)->not->toBeNull();

    $this->actingAs($admin)
        ->get(route('admin.community.moderation'))
        ->assertOk()
        ->assertSee(ReportController::REASONS[0]);

    $this->actingAs($admin)
        ->post(route('admin.community.reports.resolve', $report), ['action' => 'resolved', 'notes' => 'Diligence effectuée.'])
        ->assertRedirect(route('admin.community.moderation'));

    $report->refresh();
    expect($report->status)->toBe('resolved');
    expect($report->reviewed_at)->not->toBeNull();
    expect($report->handled_by)->toBe($admin->id);
});

test('the takedown (avis-et-avis) form pre-fills the journal URL via the generic ?url= param', function () {
    $journalUrl = 'https://laveille.ai/journaux/exemple-http';

    $this->get(route('directory.takedown.create', ['url' => $journalUrl]))
        ->assertOk()
        ->assertSee($journalUrl, false);
});

test('the published journal page exposes Signaler and the takedown link', function () {
    $owner = User::factory()->create();
    $journal = Journal::create(['user_id' => $owner->id, 'title' => 'Journal action bar HTTP', 'slug' => 'actionbar-http', 'journal_date' => now()->toDateString(), 'template' => 'classique', 'is_published' => true]);

    $this->actingAs($owner)
        ->get(route('journal.show', $journal))
        ->assertOk()
        ->assertSee('Signaler')
        ->assertSee('Demander un retrait');
});
