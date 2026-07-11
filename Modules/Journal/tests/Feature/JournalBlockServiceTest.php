<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 *
 * Tests Pest — JournalBlockService : les 4 types de blocs (texte, image, vidéo,
 * source) dont les 4 sous-types de source (news/glossary/tool/directory_tool).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Dictionary\Models\Term;
use Modules\Directory\Models\Tool as DirectoryTool;
use Modules\Journal\Models\Journal;
use Modules\Journal\Services\JournalBlockService;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\Tools\Models\Tool as InternalTool;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

function jbsJournal(): Journal
{
    $user = User::factory()->create();

    return Journal::create([
        'user_id' => $user->id,
        'title' => 'Journal JBS',
        'slug' => 'journal-jbs-'.uniqid(),
        'journal_date' => now()->toDateString(),
        'template' => 'classique',
        'is_published' => false,
    ]);
}

test('addTextBlock creates a text block with incrementing sort_order', function () {
    $journal = jbsJournal();
    $service = app(JournalBlockService::class);

    $first = $service->addTextBlock($journal, '<p>Un</p>');
    $second = $service->addTextBlock($journal, '<p>Deux</p>');

    expect($first->type)->toBe('text');
    expect($first->payload['html'])->toBe('<p>Un</p>');
    expect($second->sort_order)->toBe($first->sort_order + 1);
});

test('addVideoBlock parses a valid YouTube URL and rejects an invalid one', function () {
    $journal = jbsJournal();
    $service = app(JournalBlockService::class);

    $block = $service->addVideoBlock($journal, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
    expect($block->type)->toBe('video');
    expect($block->payload['youtube_id'])->toBe('dQw4w9WgXcQ');
    expect($block->payload['embed_url'])->toContain('youtube-nocookie.com/embed/dQw4w9WgXcQ');

    expect(fn () => $service->addVideoBlock($journal, 'https://example.com/not-youtube'))
        ->toThrow(InvalidArgumentException::class);
});

test('addImageBlock requires rights confirmation and stores a webp', function () {
    Storage::fake('public');
    $journal = jbsJournal();
    $service = app(JournalBlockService::class);

    $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

    expect(fn () => $service->addImageBlock($journal, $file, false))
        ->toThrow(InvalidArgumentException::class);

    $block = $service->addImageBlock($journal, $file, true);
    expect($block->type)->toBe('image');
    expect($block->payload['url'])->toContain('.webp');
});

test('addFromSource creates a news block with real fields', function () {
    $journal = jbsJournal();
    $source = NewsSource::create(['name' => 'Source JBS', 'url' => 'https://jbs.exemple.com/rss', 'language' => 'fr', 'active' => true]);
    $article = NewsArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Article JBS',
        'guid' => 'guid-jbs-1',
        'url' => 'https://exemple.com/jbs-1',
        'description' => 'Description JBS',
        'slug' => 'article-jbs-1',
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ]);

    $block = app(JournalBlockService::class)->addFromSource($journal, 'news', $article->id);

    expect($block->type)->toBe('news');
    expect($block->source_type)->toBe(NewsArticle::class);
    expect($block->payload['title'])->toBe('Article JBS');
    expect($block->payload['url'])->toBe(route('news.show', $article->slug));
});

test('addFromSource creates a glossary block with real fields', function () {
    $journal = jbsJournal();
    $term = Term::create([
        'name' => 'Terme JBS',
        'slug' => 'terme-jbs',
        'definition' => 'Une définition de test pour le terme JBS.',
        'is_published' => true,
    ]);

    $block = app(JournalBlockService::class)->addFromSource($journal, 'glossary', $term->id);

    expect($block->type)->toBe('glossary');
    expect($block->source_type)->toBe(Term::class);
    expect($block->payload['title'])->toBe('Terme JBS');
    expect($block->payload['url'])->toBe(route('dictionary.show', $term->slug));
});

test('addFromSource creates an internal-tool block with real fields', function () {
    $journal = jbsJournal();
    $tool = InternalTool::create([
        'name' => 'Outil interne JBS',
        'slug' => 'outil-interne-jbs',
        'description' => 'Description outil interne JBS.',
        'category' => 'productivite',
        'is_active' => true,
    ]);

    $block = app(JournalBlockService::class)->addFromSource($journal, 'tool', $tool->id);

    expect($block->type)->toBe('tool');
    expect($block->source_type)->toBe(InternalTool::class);
    expect($block->payload['url'])->toBe(route('tools.show', $tool->slug));
});

test('addFromSource creates a directory-tool block with real fields', function () {
    $journal = jbsJournal();
    $tool = DirectoryTool::create([
        'name' => 'Outil annuaire JBS',
        'slug' => 'outil-annuaire-jbs',
        'description' => 'Description outil annuaire JBS.',
        'url' => 'https://exemple.com/outil-annuaire-jbs',
        'pricing' => 'free',
        'status' => 'published',
    ]);

    $block = app(JournalBlockService::class)->addFromSource($journal, 'directory_tool', $tool->id);

    expect($block->type)->toBe('directory_tool');
    expect($block->source_type)->toBe(DirectoryTool::class);
    expect($block->payload['url'])->toBe(route('directory.show', $tool->slug));
});

test('addFromSource rejects an unknown source type', function () {
    $journal = jbsJournal();

    expect(fn () => app(JournalBlockService::class)->addFromSource($journal, 'bogus', 1))
        ->toThrow(InvalidArgumentException::class);
});
