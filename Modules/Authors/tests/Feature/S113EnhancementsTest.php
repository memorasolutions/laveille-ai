<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Authors\Models\AuthorAffiliateLink;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Services\WebmentionSenderService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function makeAuthorS113(): AuthorProfile
{
    $user = User::factory()->create();

    return AuthorProfile::create([
        'user_id' => $user->id,
        'slug' => 's113-'.strtolower(Str::random(6)),
        'display_name' => 'S113 Test',
        'tier' => 'free',
    ]);
}

it('WebmentionSenderService discoverEndpoint detects Link header rel=webmention', function () {
    Http::fake([
        'external.example.com/*' => Http::response('', 200, [
            'Link' => '<https://external.example.com/webmention>; rel="webmention"',
        ]),
    ]);

    $service = new WebmentionSenderService();
    $endpoint = $service->discoverEndpoint('https://external.example.com/post');

    expect($endpoint)->toBe('https://external.example.com/webmention');
});

it('WebmentionSenderService discoverEndpoint returns null when no webmention found', function () {
    Http::fake([
        'nowhere.example.com/*' => Http::response('<html><body>No webmention</body></html>', 200),
    ]);

    $service = new WebmentionSenderService();
    expect($service->discoverEndpoint('https://nowhere.example.com/post'))->toBeNull();
});

it('WebmentionSenderService send POST source+target returns true on 202', function () {
    Http::fake([
        'endpoint.example.com/*' => Http::response('', 202),
    ]);

    $service = new WebmentionSenderService();
    expect($service->send(
        'https://endpoint.example.com/webmention',
        'https://my.site/post',
        'https://target.com/x'
    ))->toBeTrue();
});

it('AffiliateLinkManager creates new link with valid attributes', function () {
    $author = makeAuthorS113();
    $this->actingAs($author->user);

    Livewire::test(\Modules\Authors\Livewire\AffiliateLinkManager::class, ['authorProfileId' => $author->id])
        ->set('slug', 'amazon-livre')
        ->set('destinationUrl', 'https://amazon.com/livre')
        ->set('label', 'Mon livre Amazon')
        ->call('save');

    $link = AuthorAffiliateLink::where('slug', 'amazon-livre')->first();

    expect($link)
        ->not->toBeNull()
        ->and($link->destination_url)->toBe('https://amazon.com/livre')
        ->and($link->author_profile_id)->toBe($author->id);
});

it('AffiliateLinkManager validates slug regex lowercase + dashes only', function () {
    $author = makeAuthorS113();
    $this->actingAs($author->user);

    Livewire::test(\Modules\Authors\Livewire\AffiliateLinkManager::class, ['authorProfileId' => $author->id])
        ->set('slug', 'AmazonLivre With Spaces')
        ->set('destinationUrl', 'https://amazon.com')
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('AffiliateLinkManager rejects duplicate slug', function () {
    $author = makeAuthorS113();
    AuthorAffiliateLink::create([
        'author_profile_id' => $author->id,
        'slug' => 'duplicate',
        'destination_url' => 'https://a.com',
    ]);

    $this->actingAs($author->user);

    Livewire::test(\Modules\Authors\Livewire\AffiliateLinkManager::class, ['authorProfileId' => $author->id])
        ->set('slug', 'duplicate')
        ->set('destinationUrl', 'https://b.com')
        ->call('save')
        ->assertHasErrors(['slug']);
});

it('AffiliateLinkManager startEdit loads link into form', function () {
    $author = makeAuthorS113();
    $link = AuthorAffiliateLink::create([
        'author_profile_id' => $author->id,
        'slug' => 'to-edit',
        'destination_url' => 'https://a.com',
        'label' => 'A',
    ]);

    $this->actingAs($author->user);

    Livewire::test(\Modules\Authors\Livewire\AffiliateLinkManager::class, ['authorProfileId' => $author->id])
        ->call('startEdit', $link->id)
        ->assertSet('editingId', $link->id)
        ->assertSet('slug', 'to-edit')
        ->assertSet('destinationUrl', 'https://a.com');
});

it('AffiliateLinkManager delete soft removes link', function () {
    $author = makeAuthorS113();
    $link = AuthorAffiliateLink::create([
        'author_profile_id' => $author->id,
        'slug' => 'to-delete',
        'destination_url' => 'https://a.com',
    ]);

    $this->actingAs($author->user);

    Livewire::test(\Modules\Authors\Livewire\AffiliateLinkManager::class, ['authorProfileId' => $author->id])
        ->call('delete', $link->id);

    $link->refresh();
    expect($link->trashed())->toBeTrue();
});

it('Tip button shown on mini-site when cashier secret configured', function () {
    config(['cashier.secret' => 'sk_test_xxx']);
    $author = makeAuthorS113();
    $response = $this->get('/@'.$author->slug);
    $response->assertStatus(200);
    $response->assertSee('lv-tip-btn', false);
    $response->assertSee('Offrir un café', false);
});

it('Tip button NOT shown when cashier secret missing', function () {
    config(['cashier.secret' => null]);
    $author = makeAuthorS113();
    $response = $this->get('/@'.$author->slug);
    $response->assertStatus(200);
    $response->assertDontSee('lv-tip-btn', false);
});
