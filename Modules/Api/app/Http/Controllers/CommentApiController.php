<?php

declare(strict_types=1);

namespace Modules\Api\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Api\Http\Requests\StoreCommentRequest;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Comment;

class CommentApiController extends BaseApiController
{
    public function store(StoreCommentRequest $request, Article $article): JsonResponse
    {
        $validated = $request->validated();

        $comment = Comment::create([
            'content' => $validated['content'],
            'status' => 'pending',
            'article_id' => $article->id,
            'user_id' => $request->user()->id,
        ]);

        return $this->respondCreated($comment);
    }
}
