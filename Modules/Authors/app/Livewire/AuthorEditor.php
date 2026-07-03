<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Livewire\Component;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorPostRevision;
use Modules\Authors\Models\AuthorProfile;

class AuthorEditor extends Component
{
    public ?AuthorPost $post = null;

    public ?string $scheduledAt = null;

    public AuthorProfile $authorProfile;

    public string $title = '';

    public string $body_markdown = '';

    public string $excerpt = '';

    public string $status = 'draft';

    public string $visibility = 'public';

    public array $tags = [];

    public string $tagsInput = '';

    public ?string $cover_image = null;

    public string $autoSaveStatus = 'idle';

    public ?string $publicUrl = null;

    public function mount(AuthorProfile $authorProfile, ?int $postId = null): void
    {
        abort_if($authorProfile->user_id !== auth()->id(), 403);

        $this->authorProfile = $authorProfile;

        if ($postId !== null) {
            $this->post = AuthorPost::where('author_profile_id', $this->authorProfile->id)
                ->where('id', $postId)
                ->firstOrFail();

            $this->title = $this->post->title;
            $this->body_markdown = $this->post->body_markdown;
            $this->excerpt = $this->post->excerpt ?? '';
            $this->status = $this->post->status;
            $this->visibility = $this->post->visibility;
            $this->tags = $this->post->tags ?? [];
            $this->cover_image = $this->post->cover_image;
            $this->tagsInput = implode(', ', $this->tags);
        }
    }

    /**
     * Déclenche l'auto-sauvegarde quand le titre ou le contenu (Tiptap) changent,
     * en s'appuyant sur les modificateurs Livewire .live des champs du formulaire
     * plutôt que sur un minuteur JS séparé (S107 → migration Tiptap).
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['title', 'body_markdown'], true)) {
            $this->autoSave();
        }
    }

    public function autoSave(): void
    {
        if (mb_strlen(trim($this->title)) < 3 && mb_strlen(trim($this->body_markdown)) < 3) {
            return;
        }

        $this->autoSaveStatus = 'saving';

        try {
            $this->normalizeData();

            if ($this->post === null) {
                $this->post = AuthorPost::create([
                    'author_profile_id' => $this->authorProfile->id,
                    'title' => $this->title ?: 'Brouillon sans titre',
                    'slug' => $this->generateUniqueSlug($this->title ?: 'brouillon'),
                    'body_markdown' => $this->body_markdown,
                    'body_html' => $this->renderHtml($this->body_markdown),
                    'excerpt' => $this->computeExcerpt(),
                    'status' => 'draft',
                    'visibility' => $this->visibility,
                    'tags' => $this->tags,
                    'reading_time_minutes' => $this->computeReadingTime(),
                ]);
            } else {
                $this->post->update([
                    'title' => $this->title,
                    'body_markdown' => $this->body_markdown,
                    'body_html' => $this->renderHtml($this->body_markdown),
                    'excerpt' => $this->computeExcerpt(),
                    'visibility' => $this->visibility,
                    'tags' => $this->tags,
                    'reading_time_minutes' => $this->computeReadingTime(),
                ]);
            }

            $this->autoSaveStatus = 'saved';
        } catch (\Throwable $e) {
            $this->autoSaveStatus = 'error';
        }
    }

    public function schedule(): void
    {
        $this->validate([
            'title' => 'required|string|min:3|max:255',
            'body_markdown' => 'required|string|min:20',
            'scheduledAt' => 'required|date|after:now',
        ]);

        $this->normalizeData();

        if ($this->post === null) {
            $this->autoSave();
        }

        $this->post->update([
            'title' => $this->title,
            'slug' => $this->generateUniqueSlug($this->title, $this->post->id),
            'body_markdown' => $this->body_markdown,
            'body_html' => $this->renderHtml($this->body_markdown),
            'excerpt' => $this->computeExcerpt(),
            'status' => AuthorPost::STATUS_SCHEDULED,
            'visibility' => $this->visibility,
            'tags' => $this->tags,
            'reading_time_minutes' => $this->computeReadingTime(),
            'published_at' => $this->scheduledAt,
        ]);

        $this->dispatch('post-scheduled', scheduledAt: (string) $this->scheduledAt);
    }

    public function previewUrl(): string
    {
        if ($this->post === null) {
            return '';
        }

        return URL::signedRoute(
            'authors.post.preview',
            [
                'slug' => $this->authorProfile->slug,
                'postSlug' => $this->post->slug,
            ],
            now()->addDays(7)
        );
    }

    public function publish(): void
    {
        $this->validate([
            'title' => 'required|string|min:3|max:255',
            'body_markdown' => 'required|string|min:20',
        ]);

        $this->normalizeData();

        if ($this->post === null) {
            $this->autoSave();
        }

        $wasPublished = $this->post->status === 'published';

        if ($this->post->wasChanged('body_markdown') || ! $wasPublished) {
            AuthorPostRevision::create([
                'author_post_id' => $this->post->id,
                'user_id' => Auth::id(),
                'body_markdown_snapshot' => $this->post->body_markdown,
                'change_summary' => $wasPublished ? 'Mise à jour du contenu' : 'Publication initiale',
            ]);
        }

        $this->post->update([
            'title' => $this->title,
            'slug' => $this->generateUniqueSlug($this->title, $this->post->id),
            'body_markdown' => $this->body_markdown,
            'body_html' => $this->renderHtml($this->body_markdown),
            'excerpt' => $this->computeExcerpt(),
            'status' => 'published',
            'visibility' => $this->visibility,
            'tags' => $this->tags,
            'reading_time_minutes' => $this->computeReadingTime(),
            'published_at' => $this->post->published_at ?? now(),
        ]);

        $this->publicUrl = url('/@'.$this->authorProfile->slug.'/'.$this->post->slug);
        $this->dispatch('post-published', url: $this->publicUrl);
    }

    public function addTag(): void
    {
        $tag = trim(strtolower($this->tagsInput));

        if ($tag === '' || in_array($tag, $this->tags, true)) {
            $this->tagsInput = '';

            return;
        }

        $this->tags[] = $tag;
        $this->tagsInput = '';
    }

    public function removeTag(string $tag): void
    {
        $this->tags = array_values(array_diff($this->tags, [$tag]));
    }

    private function normalizeData(): void
    {
        $this->title = trim($this->title);

        if ($this->tagsInput !== '') {
            $parsed = array_filter(array_map(
                fn ($t) => trim(strtolower($t)),
                explode(',', $this->tagsInput)
            ));
            $this->tags = array_values(array_unique(array_merge($this->tags, $parsed)));
            $this->tagsInput = '';
        }
    }

    private function generateUniqueSlug(string $title, ?int $excludePostId = null): string
    {
        $base = Str::slug($title ?: 'article') ?: 'article';
        $slug = mb_substr($base, 0, 200);
        $i = 1;

        while (AuthorPost::where('author_profile_id', $this->authorProfile->id)
            ->where('slug', $slug)
            ->when($excludePostId, fn ($q) => $q->where('id', '!=', $excludePostId))
            ->exists()) {
            $i++;
            $slug = mb_substr($base, 0, 195).'-'.$i;
        }

        return $slug;
    }

    private function computeReadingTime(): int
    {
        $words = str_word_count(strip_tags($this->renderHtml($this->body_markdown)));

        return max(1, (int) ceil($words / 200));
    }

    private function computeExcerpt(): string
    {
        $strip = trim(strip_tags($this->renderHtml($this->body_markdown)));

        if ($this->excerpt !== '') {
            return mb_substr($this->excerpt, 0, 280);
        }

        return mb_substr($strip, 0, 280);
    }

    private function renderHtml(string $markdown): string
    {
        // S111 — oEmbed pre-process (URLs sur ligne seule → iframe lazy)
        $oembed = new \Modules\Authors\Services\OembedService();
        $processed = $oembed->transformMarkdown($markdown);

        // S111 — html_input=allow pour préserver iframes oEmbed (markdown=strip stripperait)
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        return (string) $converter->convert($processed);
    }

    private function collectExistingTags(): array
    {
        return AuthorPost::where('author_profile_id', $this->authorProfile->id)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    public function render()
    {
        return view('authors::livewire.author-editor', [
            'allTags' => $this->collectExistingTags(),
        ]);
    }
}
