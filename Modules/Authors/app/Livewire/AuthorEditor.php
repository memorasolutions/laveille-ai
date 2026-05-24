<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Livewire\Component;
use Modules\Authors\Models\AuthorPost;
use Modules\Authors\Models\AuthorProfile;

/**
 * AuthorEditor — Top5-E S106 scaffolding S107-ready.
 *
 * TODO S107 implementation complète (~20h) :
 * - EasyMDE CDN integration (60KB) + Tailwind Typography preview
 * - Auto-save toutes 30s : localStorage + UPDATE draft DB
 * - Slash commands : /image /quote /code /embed /toc /poll /tip-button
 * - Toolbar minimal mobile-collapse : H1-H6, bold, italic, link, image upload, embed paste
 * - Side-by-side live preview avec league/commonmark
 * - Drag-drop image upload via Spatie/Image (resize 1920w max + webp + jpg fallback)
 * - Auto-embed detection : Twitter/X, YouTube, CodePen, Spotify, Loom URLs
 * - Slug auto-generated from title (Str::slug + unique check)
 * - Reading time calc : str_word_count / 200wpm
 * - Excerpt auto : 200 first chars stripped
 * - Pre-publish scan via ModerationPipelineService (LlamaGuard cascade)
 * - Snapshot AuthorPostRevision avant chaque save publish
 * - Tags input : Alpine.js chips + autocomplete depuis tags existants auteur
 */
class AuthorEditor extends Component
{
    public ?AuthorPost $post = null;
    public AuthorProfile $authorProfile;

    public string $title = '';
    public string $body_markdown = '';
    public string $excerpt = '';
    public string $status = AuthorPost::STATUS_DRAFT;
    public string $visibility = AuthorPost::VISIBILITY_PUBLIC;
    public array $tags = [];
    public ?string $cover_image = null;

    public bool $autoSaveEnabled = true;
    public string $autoSaveStatus = 'idle';

    public function mount(AuthorProfile $authorProfile, ?int $postId = null): void
    {
        $this->authorProfile = $authorProfile;

        if ($postId) {
            $this->post = AuthorPost::where('author_profile_id', $authorProfile->id)
                ->findOrFail($postId);
            $this->title = $this->post->title;
            $this->body_markdown = $this->post->body_markdown;
            $this->excerpt = $this->post->excerpt ?? '';
            $this->status = $this->post->status;
            $this->visibility = $this->post->visibility;
            $this->tags = $this->post->tags ?? [];
            $this->cover_image = $this->post->cover_image;
        }
    }

    public function autoSave(): void
    {
        // TODO S107 : persist draft DB + snapshot revision
        $this->autoSaveStatus = 'saved';
    }

    public function publish(): void
    {
        // TODO S107 : validation + ModerationPipelineService scan + persist
        $this->status = AuthorPost::STATUS_PUBLISHED;
    }

    public function render()
    {
        return view('authors::livewire.author-editor');
    }
}
