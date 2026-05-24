<?php

declare(strict_types=1);

namespace Modules\Authors\View\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class CommentSection extends Component
{
    public function __construct(public Model $commentable, public int $authorProfileId)
    {
    }

    public function render()
    {
        return view('authors::components.comment-section', [
            'commentableType' => $this->commentable::class,
            'commentableId' => $this->commentable->getKey(),
            'authorProfileId' => $this->authorProfileId,
        ]);
    }
}
