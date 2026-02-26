<?php

declare(strict_types=1);

namespace Modules\Blog\States\Transitions;

use Modules\Blog\Models\Article;
use Modules\Blog\States\PublishedArticleState;
use Spatie\ModelStates\Transition;

class PublishTransition extends Transition
{
    public function __construct(
        private Article $article,
    ) {}

    public function handle(): Article
    {
        $this->article->published_at = $this->article->published_at ?? now();
        $this->article->status = new PublishedArticleState($this->article);
        $this->article->save();

        return $this->article;
    }
}
