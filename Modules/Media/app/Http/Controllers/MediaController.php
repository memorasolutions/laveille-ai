<?php

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
            $query->where('file_name', 'LIKE', '%' . $request->query('search') . '%');
        }

        $media = $query->orderByDesc('created_at')->paginate(24);

        $items = $media->map(fn (Media $item) => [
            'id' => $item->id,
            'file_name' => $item->file_name,
            'mime_type' => $item->mime_type,
            'size' => $item->size,
            'url' => $item->getUrl(),
            'thumbnail' => $this->getThumbnailUrl($item),
            'created_at' => $item->created_at->toISOString(),
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
        ]);

        $container = MediaUpload::firstOrCreate(['name' => 'general']);

        $media = $container
            ->addMedia($request->file('file'))
            ->toMediaCollection('images');

        return response()->json([
            'id' => $media->id,
            'url' => $media->getUrl(),
            'thumbnail' => $this->getThumbnailUrl($media),
            'file_name' => $media->file_name,
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->mediaService->deleteMedia($id);

        return response()->json(null, 204);
    }

    private function getThumbnailUrl(Media $media): string
    {
        try {
            return $media->getUrl('thumbnail');
        } catch (\Throwable) {
            return $media->getUrl();
        }
    }
}
