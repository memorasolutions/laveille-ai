<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Blog\Livewire;

use Illuminate\Support\Collection;
use Livewire\Component;
use Modules\Blog\Models\Article;

class BlogSearch extends Component
{
    public string $search = '';

    public function render()
    {
        $results = $this->getResults();

        return view('blog::livewire.blog-search', [
            'results' => $results,
        ]);
    }

    private function getResults(): Collection
    {
        if ($this->search === '') {
            return collect();
        }

        return Article::published()
            ->where(fn ($query) => $query
                ->where('title->'.app()->getLocale(), 'like', "%{$this->search}%")
                ->orWhere('content', 'like', "%{$this->search}%")
                ->orWhere('excerpt', 'like', "%{$this->search}%")
            )
            ->limit(8)
            ->get();
    }
}
