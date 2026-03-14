<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Media\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Media\Models\MediaUpload;
use Modules\Media\Services\MediaService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{
    public function __construct(
        protected MediaService $mediaService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Media::query()->where('collection_name', 'images');

        if ($request->filled('search')) {
            $query->where('file_name', 'LIKE', '%'.$request->query('search').'%');
        }

        if ($request->filled('folder')) {
            $query->where('custom_properties->folder', $request->query('folder'));
        }

        $media = $query->orderByDesc('created_at')->paginate(24);

        $items = $media->map(fn (Media $item) => [
            'id' => $item->id,
            'file_name' => $item->file_name,
            'mime_type' => $item->mime_type,
            'size' => $item->size,
            'url' => $item->getUrl(),
            'thumbnail' => $this->getThumbnailUrl($item),
            'thumbnail_webp' => $this->getConversionUrl($item, 'thumbnail-webp'),
            'medium_webp' => $this->getConversionUrl($item, 'medium-webp'),
            'large_webp' => $this->getConversionUrl($item, 'large-webp'),
            'created_at' => $item->created_at->toISOString(),
            'title' => $item->getCustomProperty('title', ''),
            'alt_text' => $item->getCustomProperty('alt_text', ''),
            'caption' => $item->getCustomProperty('caption', ''),
            'description' => $item->getCustomProperty('description', ''),
            'folder' => $item->getCustomProperty('folder', ''),
        ]);

        return response()->json([
            'items' => $items,
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'total' => $media->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|max:5120',
            'title' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
        ]);

        $container = MediaUpload::firstOrCreate(['name' => 'general']);

        $media = $container
            ->addMedia($request->file('file'))
            ->toMediaCollection('images');

        foreach (['title', 'alt_text', 'caption', 'description'] as $field) {
            if ($request->filled($field)) {
                $media->setCustomProperty($field, $request->input($field));
            }
        }
        if ($request->hasAny(['title', 'alt_text', 'caption', 'description'])) {
            $media->save();
        }

        return response()->json([
            'id' => $media->id,
            'url' => $media->getUrl(),
            'thumbnail' => $this->getThumbnailUrl($media),
            'file_name' => $media->file_name,
            'alt_text' => $media->getCustomProperty('alt_text', ''),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:500',
            'description' => 'nullable|string|max:1000',
            'folder' => 'nullable|string|max:100',
        ]);

        $media = Media::findOrFail($id);

        foreach (['title', 'alt_text', 'caption', 'description', 'folder'] as $field) {
            $media->setCustomProperty($field, $request->input($field, ''));
        }
        $media->save();

        return response()->json([
            'id' => $media->id,
            'title' => $media->getCustomProperty('title', ''),
            'alt_text' => $media->getCustomProperty('alt_text', ''),
            'caption' => $media->getCustomProperty('caption', ''),
            'description' => $media->getCustomProperty('description', ''),
            'folder' => $media->getCustomProperty('folder', ''),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->mediaService->deleteMedia($id);

        return response()->json(null, 204);
    }

    private function getThumbnailUrl(Media $media): string
    {
        return $this->getConversionUrl($media, 'thumbnail');
    }

    private function getConversionUrl(Media $media, string $conversion): string
    {
        try {
            return $media->getUrl($conversion);
        } catch (\Throwable) {
            return $media->getUrl();
        }
    }
}
