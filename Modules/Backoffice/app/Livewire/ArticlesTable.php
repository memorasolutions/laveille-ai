<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Blog\Models\Article;
use Modules\Settings\Facades\Settings;
use Modules\Blog\Models\Category;
use Modules\Blog\States\ArchivedArticleState;
use Modules\Blog\States\DraftArticleState;
use Modules\Blog\States\PublishedArticleState;
use Modules\Core\Traits\HasBulkActions;
use Modules\Core\Traits\HasTableSorting;
use Spatie\ModelStates\Exceptions\CouldNotPerformTransition;

class ArticlesTable extends Component
{
    use HasBulkActions, HasTableSorting, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterCategory = '';

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCategory(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->filterCategory = '';
        $this->resetPage();
    }

    public function changeStatus(int $articleId, string $status): void
    {
        $stateMap = [
            'draft' => DraftArticleState::class,
            'published' => PublishedArticleState::class,
            'archived' => ArchivedArticleState::class,
        ];

        if (! isset($stateMap[$status])) {
            return;
        }

        $article = Article::findOrFail($articleId);

        try {
            $article->status->transitionTo($stateMap[$status]);
            $this->dispatch('toast', type: 'success', message: "Article « {$article->title} » → {$status}.");
        } catch (CouldNotPerformTransition) {
            $this->dispatch('toast', type: 'error', message: "Transition vers « {$status} » non autorisée.");
        }
    }

    protected function getBulkActions(): array
    {
        return [
            'publish' => __('Publier'),
            'draft' => __('Brouillon'),
            'archive' => __('Archiver'),
            'delete' => __('Supprimer'),
        ];
    }

    protected function handleBulkAction(string $action, array $ids): void
    {
        $stateMap = [
            'publish' => PublishedArticleState::class,
            'draft' => DraftArticleState::class,
            'archive' => ArchivedArticleState::class,
        ];

        if ($action === 'delete') {
            Article::whereIn('id', $ids)->delete();

            return;
        }

        if (isset($stateMap[$action])) {
            foreach (Article::whereIn('id', $ids)->get() as $article) {
                try {
                    $article->status->transitionTo($stateMap[$action]);
                } catch (CouldNotPerformTransition) {
                    // Skip articles that can't transition
                }
            }
        }
    }

    protected function getBulkPageIds(): array
    {
        return Article::query()
            ->when($this->search, fn ($q) => $q->where('title->'.app()->getLocale(), 'like', '%'.$this->search.'%'))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate((int) Settings::get('backoffice.articles_per_page', 15))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function render(): \Illuminate\View\View
    {
        $articles = Article::with(['user', 'blogCategory'])
            ->when($this->search, fn ($q) => $q->where('title->'.app()->getLocale(), 'like', '%'.$this->search.'%'))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterCategory, fn ($q) => $q->where('category_id', $this->filterCategory))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate((int) Settings::get('backoffice.articles_per_page', 15));

        $categories = Category::active()->orderBy('name')->get();

        return view('backoffice::livewire.articles-table', compact('articles', 'categories'));
    }
}
