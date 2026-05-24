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

    public function show(\Illuminate\Http\Request $request, string $slug)
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

        // S120-1 — search query handling (GET ?q=...)
        $searchQuery = trim((string) $request->query('q', ''));
        $searchResults = null;
        if ($searchQuery !== '' && mb_strlen($searchQuery) >= 2) {
            $escaped = '%'.addcslashes($searchQuery, '%_\\').'%';
            $searchResults = \Modules\Authors\Models\AuthorPost::published()
                ->public()
                ->where('author_profile_id', $author->id)
                ->where(function ($q) use ($escaped) {
                    $q->where('title', 'like', $escaped)
                        ->orWhere('excerpt', 'like', $escaped)
                        ->orWhere('body_markdown', 'like', $escaped);
                })
                ->orderByDesc('published_at')
                ->limit(20)
                ->get();
        }

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
            'searchQuery' => $searchQuery,
            'searchResults' => $searchResults,
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

        $xml = $this->buildRichRssXml($author, $items);

        return ResponseFacade::make($xml, Response::HTTP_OK, [
            'Content-Type' => 'application/rss+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=600, s-maxage=1800',
        ]);
    }

    private function buildRichRssXml(AuthorProfile $author, $items): string
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
        $xml->writeAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
        $xml->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $xml->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');

        $xml->startElement('channel');
        $title = $author->display_name ?: ($author->user?->name ?? $author->slug);
        $xml->writeElement('title', $title);
        $xml->writeElement('link', url("/@{$author->slug}"));
        $xml->writeElement('description', (string) ($author->bio ?? 'Auteur sur laveille.ai'));
        $xml->writeElement('language', 'fr-CA');
        $xml->writeElement('lastBuildDate', now()->toRssString());

        $pubDate = ($items->isNotEmpty() && $items->first()->published_at)
            ? Carbon::parse($items->first()->published_at)->toRssString()
            : now()->toRssString();
        $xml->writeElement('pubDate', $pubDate);

        $xml->startElement('atom:link');
        $xml->writeAttribute('href', url("/@{$author->slug}/feed.xml"));
        $xml->writeAttribute('rel', 'self');
        $xml->writeAttribute('type', 'application/rss+xml');
        $xml->endElement();

        $imageUrl = $author->profile_image ? url('storage/'.$author->profile_image) : url('/images/default-avatar.svg');
        $xml->startElement('image');
        $xml->writeElement('url', $imageUrl);
        $xml->writeElement('title', $title);
        $xml->writeElement('link', url("/@{$author->slug}"));
        $xml->endElement();

        $xml->writeElement('copyright', '© '.date('Y').' '.$title);
        $xml->writeElement('generator', 'laveille.ai Authors v1.28');
        if ($author->user?->email) {
            $xml->writeElement('managingEditor', $author->user->email.' ('.$title.')');
        }

        foreach ($items as $item) {
            $xml->startElement('item');
            $itemTitle = $item->title ?? mb_substr((string) ($item->content ?? ''), 0, 80);
            $xml->writeElement('title', $itemTitle);
            $link = url('/blog/'.$item->slug);
            $xml->writeElement('link', $link);
            $xml->startElement('guid');
            $xml->writeAttribute('isPermaLink', 'true');
            $xml->text($link);
            $xml->endElement();
            $description = (string) ($item->excerpt ?? strip_tags((string) ($item->content ?? '')));
            $xml->writeElement('description', $description);

            $xml->startElement('content:encoded');
            $xml->writeCData((string) ($item->content ?? $description));
            $xml->endElement();

            if ($item->published_at) {
                $xml->writeElement('pubDate', Carbon::parse($item->published_at)->toRssString());
            }
            $xml->writeElement('dc:creator', $title);

            $xml->startElement('atom:author');
            $xml->writeElement('name', $title);
            if ($author->user?->email) {
                $xml->writeElement('email', $author->user->email);
            }
            $xml->endElement();

            if (! empty($item->cover_image)) {
                $xml->startElement('media:thumbnail');
                $xml->writeAttribute('url', url('storage/'.$item->cover_image));
                $xml->writeAttribute('width', '1200');
                $xml->writeAttribute('height', '630');
                $xml->endElement();
            }

            $tags = is_array($item->tags ?? null) ? $item->tags : [];
            foreach ($tags as $tag) {
                $xml->startElement('category');
                $xml->text((string) $tag);
                $xml->endElement();
            }

            $xml->endElement();
        }

        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
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

    public function tip(Request $request, string $slug)
    {
        $author = AuthorProfile::where('slug', $slug)->whereNull('archived_at')->firstOrFail();

        $service = app(\Modules\Authors\Services\AuthorTipsService::class);

        if (! $service->isEnabled()) {
            abort(503, 'Tips non configurés.');
        }

        $amountCents = (int) $request->input('amount_cents', 500);

        if ($amountCents < 100 || $amountCents > 50000) {
            abort(422, 'Montant invalide.');
        }

        try {
            $checkoutUrl = $service->createCheckout($author, $amountCents);

            return redirect($checkoutUrl);
        } catch (\Throwable $e) {
            Log::channel('daily')->error('tips.checkout.failed', [
                'author_slug' => $slug,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Erreur Stripe. Réessaie.');
        }
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
