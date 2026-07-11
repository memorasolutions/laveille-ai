<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 */

declare(strict_types=1);

namespace Modules\Journal\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use Modules\Journal\Models\Journal;
use Modules\Journal\Models\JournalBlock;

class JournalBlockService
{
    /**
     * @var array<string, array{class: string, route: string}>
     */
    public const SOURCE_MAP = [
        'news' => ['class' => \Modules\News\Models\NewsArticle::class, 'route' => 'news.show'],
        'glossary' => ['class' => \Modules\Dictionary\Models\Term::class, 'route' => 'dictionary.show'],
        'tool' => ['class' => \Modules\Tools\Models\Tool::class, 'route' => 'tools.show'],
        'directory_tool' => ['class' => \Modules\Directory\Models\Tool::class, 'route' => 'directory.show'],
    ];

    private ImageManager $imageManager;

    public function __construct()
    {
        try {
            $this->imageManager = new ImageManager(new ImagickDriver());
        } catch (Exception) {
            $this->imageManager = new ImageManager(new GdDriver());
        }
    }

    public function addFromSource(Journal $journal, string $type, int $sourceId): JournalBlock
    {
        if (! array_key_exists($type, self::SOURCE_MAP)) {
            throw new InvalidArgumentException('Type de bloc invalide.');
        }

        $modelClass = self::SOURCE_MAP[$type]['class'];
        $routeName = self::SOURCE_MAP[$type]['route'];

        /** @var \Illuminate\Database\Eloquent\Model $source */
        $source = $modelClass::findOrFail($sourceId);

        $payload = [
            'title' => $source->title ?? $source->name ?? '',
            'excerpt' => Str::limit(strip_tags($source->description ?? $source->definition ?? ''), 200),
            'url' => route($routeName, $source->slug),
        ];

        return JournalBlock::create([
            'journal_id' => $journal->id,
            'type' => $type,
            'source_type' => $modelClass,
            'source_id' => $sourceId,
            'payload' => $payload,
            'sort_order' => $this->nextSortOrder($journal),
        ]);
    }

    public function addTextBlock(Journal $journal, string $html): JournalBlock
    {
        return JournalBlock::create([
            'journal_id' => $journal->id,
            'type' => 'text',
            'payload' => ['html' => $html],
            'sort_order' => $this->nextSortOrder($journal),
        ]);
    }

    public function addImageBlock(Journal $journal, UploadedFile $file, bool $rightsConfirmed): JournalBlock
    {
        if (! $rightsConfirmed) {
            throw new InvalidArgumentException('Vous devez confirmer détenir les droits sur cette image.');
        }

        $encoded = $this->imageManager->read($file->getRealPath())->scale(width: 1200)->toWebp(80);
        $path = "journal-images/{$journal->id}/".uniqid().'.webp';
        Storage::disk('public')->put($path, (string) $encoded);

        return JournalBlock::create([
            'journal_id' => $journal->id,
            'type' => 'image',
            'payload' => ['url' => Storage::disk('public')->url($path)],
            'sort_order' => $this->nextSortOrder($journal),
        ]);
    }

    public function addVideoBlock(Journal $journal, string $youtubeUrl): JournalBlock
    {
        $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
        if (! preg_match($pattern, $youtubeUrl, $matches)) {
            throw new InvalidArgumentException('URL YouTube invalide.');
        }

        $id = $matches[1];

        return JournalBlock::create([
            'journal_id' => $journal->id,
            'type' => 'video',
            'payload' => [
                'youtube_id' => $id,
                'embed_url' => "https://www.youtube-nocookie.com/embed/{$id}",
            ],
            'sort_order' => $this->nextSortOrder($journal),
        ]);
    }

    private function nextSortOrder(Journal $journal): int
    {
        $max = $journal->blocks()->max('sort_order');

        return $max === null ? 0 : $max + 1;
    }
}
