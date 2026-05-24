<?php

declare(strict_types=1);

namespace Modules\Authors\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Services\AuthorExportService;
use Modules\Authors\Services\NewsletterSubscriberService;
use Modules\Authors\Services\TurnstileVerificationService;

final class MiniSiteController extends Controller
{
    public function __construct(
        private readonly NewsletterSubscriberService $newsletterService,
        private readonly TurnstileVerificationService $turnstile
    ) {
    }

    public function show(string $slug)
    {
        $author = AuthorProfile::where('slug', $slug)
            ->whereNull('archived_at')
            ->with('user')
            ->firstOrFail();

        $timeline = collect();

        if ($author->user) {
            $articles = \Modules\Blog\Models\Article::where('user_id', $author->user_id)
                ->where('status', 'published')
                ->orderByDesc('published_at')
                ->get();

            $statuses = \Modules\Authors\Models\AuthorStatus::where('author_profile_id', $author->id)
                ->where('is_published', true)
                ->orderByDesc('published_at')
                ->get();

            $timeline = $articles->concat($statuses)->sortByDesc('published_at')->values();
        }

        $lastPublishedAt = $author->last_published_at;
        $showAbandonBanner = $lastPublishedAt !== null && $lastPublishedAt->diffInDays(Carbon::now()) > 60;

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                lv_jsonld_author_from_profile($author),
                lv_jsonld_author_website($author),
            ],
        ];

        return view('authors::mini-site.show', [
            'author' => $author,
            'timeline' => $timeline,
            'showAbandonBanner' => $showAbandonBanner,
            'jsonLd' => $jsonLd,
        ]);
    }

    public function rss(string $slug)
    {
        $author = AuthorProfile::where('slug', $slug)
            ->whereNull('archived_at')
            ->with('user')
            ->firstOrFail();

        $items = collect();
        if ($author->user) {
            $articles = \Modules\Blog\Models\Article::where('user_id', $author->user_id)
                ->where('status', 'published')
                ->orderByDesc('published_at')
                ->limit(20)
                ->get();
            $items = $articles;
        }

        $xml = $this->buildRssXml($author, $items);

        return ResponseFacade::make($xml, Response::HTTP_OK, [
            'Content-Type' => 'application/rss+xml; charset=utf-8',
        ]);
    }

    public function jsonFeed(string $slug)
    {
        $author = AuthorProfile::where('slug', $slug)->whereNull('archived_at')->firstOrFail();

        try {
            $service = app(AuthorExportService::class);
            $path = $service->exportJsonFeed($author->id);
            $content = file_get_contents($path);
            return ResponseFacade::make($content, 200, [
                'Content-Type' => 'application/feed+json; charset=utf-8',
            ]);
        } catch (\Throwable $e) {
            return ResponseFacade::json(['error' => 'feed unavailable'], 500);
        }
    }

    public function subscribe(Request $request, string $slug)
    {
        if (! empty($request->input('website'))) {
            return ResponseFacade::json(['ok' => true], 200);
        }

        // S108 — Cloudflare Turnstile verification (graceful bypass si pas configuré)
        if ($this->turnstile->isEnabled()) {
            $token = $request->input('cf-turnstile-response');
            if (! $this->turnstile->verify($token, $request->ip())) {
                return ResponseFacade::json([
                    'ok' => false,
                    'message' => "Validation anti-bot échouée. Réessaie en rafraîchissant la page.",
                ], 422);
            }
        }

        $validated = $request->validate([
            'email' => 'required|email:rfc|max:255',
            'consent' => 'required|accepted',
            'source' => 'nullable|string|in:inline,footer,modal',
        ]);

        $author = AuthorProfile::where('slug', $slug)->whereNull('archived_at')->firstOrFail();

        try {
            $this->newsletterService->subscribe(
                email: $validated['email'],
                author: $author,
                source: $validated['source'] ?? 'inline',
                ip: $request->ip(),
                ua: substr((string) $request->userAgent(), 0, 512),
                locale: app()->getLocale()
            );
        } catch (\Throwable $e) {
            Log::channel('daily')->error('author.newsletter.subscribe.error', [
                'author_slug' => $author->slug,
                'email' => $validated['email'],
                'error' => $e->getMessage(),
            ]);

            return ResponseFacade::json([
                'ok' => false,
                'message' => "Une erreur est survenue. Réessaie dans quelques instants.",
            ], 500);
        }

        return ResponseFacade::json([
            'ok' => true,
            'message' => "Merci. Vérifie ta boîte courriel pour confirmer.",
        ], 200);
    }

    public function confirmNewsletter(Request $request, string $slug, string $token)
    {
        abort_unless($request->hasValidSignature(), 401, 'Lien expiré ou invalide.');

        $author = AuthorProfile::where('slug', $slug)->whereNull('archived_at')->firstOrFail();
        $sub = $this->newsletterService->confirm($token);

        abort_if($sub === null, 404, 'Abonnement introuvable.');

        return view('authors::mini-site.newsletter-confirmed', [
            'author' => $author,
            'sub' => $sub,
        ]);
    }

    public function unsubscribeOneClick(Request $request, string $slug, string $token)
    {
        abort_unless($request->hasValidSignature(), 401, 'Lien expiré ou invalide.');

        $author = AuthorProfile::where('slug', $slug)->whereNull('archived_at')->firstOrFail();
        $unsubscribed = $this->newsletterService->unsubscribe($token);

        Log::channel('daily')->info('newsletter.unsubscribe.one_click', [
            'slug' => $slug,
            'author_id' => $author->id,
            'success' => $unsubscribed,
            'ua' => substr((string) $request->userAgent(), 0, 256),
            'ip' => $request->ip(),
        ]);

        return ResponseFacade::make('Unsubscribed', 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    public function unsubscribeNewsletter(Request $request, string $slug, string $token)
    {
        abort_unless($request->hasValidSignature(), 401, 'Lien expiré ou invalide.');

        $author = AuthorProfile::where('slug', $slug)->whereNull('archived_at')->firstOrFail();
        $this->newsletterService->unsubscribe($token);

        return view('authors::mini-site.newsletter-unsubscribed', [
            'author' => $author,
        ]);
    }

    private function buildRssXml(AuthorProfile $author, $items): string
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->startElement('channel');

        $xml->writeElement('title', $author->user?->name ?? $author->slug);
        $xml->writeElement('link', url("/@{$author->slug}"));
        $xml->writeElement('description', (string) ($author->bio ?? ''));
        $xml->writeElement('lastBuildDate', now()->toRssString());

        foreach ($items as $item) {
            $xml->startElement('item');
            $xml->writeElement('title', (string) ($item->title ?? mb_substr((string) $item->content, 0, 80)));
            $xml->writeElement('link', url("/blog/{$item->slug}"));
            $xml->writeElement('description', (string) ($item->excerpt ?? $item->content ?? ''));
            if ($item->published_at) {
                $xml->writeElement('pubDate', Carbon::parse($item->published_at)->toRssString());
            }
            $xml->writeElement('guid', url("/blog/{$item->slug}"));
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }
}
