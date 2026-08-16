<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest - courriel de veille quotidien (demande fondateur 2026-08-16, suite a l'arret de la
 * publication automatique v1.174.0) : NotifyNewsDigestCommand + NewsDigestMail. Couvre le
 * declenchement sur brouillon nouveau, l'idempotence (curseur Setting), l'interrupteur
 * news.digest.enabled, l'exclusion des articles deja publies, et l'absence du texte source
 * integral dans le corps du courriel.
 *
 * NON EXECUTES par ce sous-agent (consigne CONTRAINTES-SOUS-AGENTS.md, section 2) - a executer
 * par le superviseur, une seule suite a la fois.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\News\Mail\NewsDigestMail;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\Settings\Models\Setting;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

function digestSource(string $name = 'Source de test'): NewsSource
{
    return NewsSource::create([
        'name' => $name,
        'url' => 'https://exemple.test/'.\Illuminate\Support\Str::slug($name).'-'.uniqid(),
        'language' => 'fr',
    ]);
}

function digestArticle(NewsSource $source, array $overrides = []): NewsArticle
{
    static $n = 0;
    $n++;

    return NewsArticle::create(array_merge([
        'news_source_id' => $source->id,
        'title' => 'Actualite de test numero '.$n,
        'slug' => 'actualite-de-test-numero-'.$n.'-'.uniqid(),
        'guid' => 'guid-digest-'.$n.'-'.uniqid(),
        'url' => 'https://exemple.test/article-'.$n,
        'summary' => 'Resume court deja produit pour l\'article numero '.$n.'.',
        'description' => 'TEXTE SOURCE INTEGRAL QUI NE DOIT JAMAIS APPARAITRE DANS LE COURRIEL - article '.$n,
        'pub_date' => now(),
        'is_published' => false,
        'relevance_score' => 70,
    ], $overrides));
}

// ── Declenchement sur brouillon nouveau ──────────────────────────────────────

test('des brouillons nouveaux declenchent un courriel avec le bon decompte', function (): void {
    Mail::fake();

    $source = digestSource();
    digestArticle($source);
    digestArticle($source);

    $this->artisan('news:notify-digest')->assertExitCode(0);

    Mail::assertSentCount(1);
    Mail::assertSent(NewsDigestMail::class, fn ($mail) => $mail->totalCount === 2
        && $mail->hasTo(config('app.superadmin_email')));

    expect(Setting::get('news.digest_last_sent_at'))->not->toBeNull();
});

// ── Idempotence ───────────────────────────────────────────────────────────────

test('un second passage sans nouveaute n\'envoie rien', function (): void {
    Mail::fake();

    $source = digestSource();
    digestArticle($source);

    $this->artisan('news:notify-digest');
    Mail::assertSentCount(1);

    $this->travel(1)->day();
    $this->artisan('news:notify-digest')->assertExitCode(0);

    Mail::assertSentCount(1);
});

test('une nouvelle activite APRES un premier envoi genere un second courriel distinct', function (): void {
    Mail::fake();

    $source = digestSource();
    digestArticle($source);

    $this->artisan('news:notify-digest');
    Mail::assertSentCount(1);

    $this->travel(1)->day();
    digestArticle($source);
    $this->artisan('news:notify-digest');

    Mail::assertSentCount(2);
    Mail::assertSent(NewsDigestMail::class, fn ($mail) => $mail->totalCount === 1);
});

// ── Interrupteur ──────────────────────────────────────────────────────────────

test('le reglage desactive = aucun envoi', function (): void {
    config(['news.digest.enabled' => false]);
    Mail::fake();

    $source = digestSource();
    digestArticle($source);

    $this->artisan('news:notify-digest')->assertExitCode(0);

    Mail::assertNothingSent();
    expect(Setting::get('news.digest_last_sent_at'))->toBeNull();
});

// ── Exclusion des articles publies ───────────────────────────────────────────

test('les articles deja publies ne figurent pas dans le courriel', function (): void {
    Mail::fake();

    $source = digestSource();
    digestArticle($source, ['is_published' => false]);
    digestArticle($source, ['is_published' => true]);

    $this->artisan('news:notify-digest');

    Mail::assertSent(NewsDigestMail::class, fn ($mail) => $mail->totalCount === 1
        && $mail->articles->every(fn ($article) => $article->is_published === false));
});

test('aucun brouillon = aucun courriel', function (): void {
    Mail::fake();

    $source = digestSource();
    digestArticle($source, ['is_published' => true]);

    $this->artisan('news:notify-digest')->assertExitCode(0);

    Mail::assertNothingSent();
});

// ── Zero copie du texte source ───────────────────────────────────────────────

test('le texte integral d\'une source n\'apparait jamais dans le corps du courriel', function (): void {
    $source = digestSource();
    $article = digestArticle($source, [
        'summary' => 'Un resume court et sur, jamais le texte source.',
        'description' => 'PHRASE SOURCE INTERDITE QUI NE DOIT JAMAIS FUITER DANS LE COURRIEL',
    ]);

    $mailable = new NewsDigestMail(collect([$article->load('source')]), 1, 100);
    $rendered = $mailable->render();

    expect($rendered)
        ->not->toContain('PHRASE SOURCE INTERDITE QUI NE DOIT JAMAIS FUITER DANS LE COURRIEL')
        ->toContain('Un resume court et sur, jamais le texte source.');
});
