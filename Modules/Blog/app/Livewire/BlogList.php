<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Livewire;

use Livewire\Component;
use Modules\Blog\Models\Article;

class BlogList extends Component
{
    public ?string $category = null;

    public int $perPage = 9;

    public bool $hasMore = false;

    public function mount(?string $category = null): void
    {
        $this->category = $category;
    }

    public function loadMore(): void
    {
        $this->perPage += 9;
    }

    public function render(): \Illuminate\View\View
    {
        $query = Article::published()->latest('published_at');

        if ($this->category) {
            $query->where('category', $this->category);
        }

        $total = $query->count();
        $articles = $query->take($this->perPage)->get();

        $this->hasMore = $total > $this->perPage;

        return view('blog::livewire.blog-list', compact('articles'));
    }
}
