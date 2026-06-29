<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Backoffice\Livewire\Concerns\WithInfiniteScroll;
use Modules\Blog\Models\Tag;

class TagsTable extends Component
{
    use WithInfiniteScroll, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    /**
     * Supprime un tag.
     * Requiert la permission delete_articles (cohérent avec la route DELETE du module Blog).
     */
    public function deleteTag(int $id): void
    {
        abort_if(! auth()->user()?->can('delete_articles'), 403);
        Tag::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: __('Tag supprimé.'));
    }

    public function render(): View
    {
        $tags = Tag::withCount('articles')
            ->orderBy('name')
            ->paginate($this->perPage);

        return view('backoffice::livewire.tags-table', compact('tags'));
    }
}
