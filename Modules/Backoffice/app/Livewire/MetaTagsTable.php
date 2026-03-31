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
use Modules\Core\Traits\HasTableSorting;
use Modules\SEO\Models\MetaTag;
use Modules\Settings\Facades\Settings;

class MetaTagsTable extends Component
{
    use HasTableSorting, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterActive = '';

    public string $sortBy = 'url_pattern';

    public string $sortDirection = 'asc';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterActive(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterActive = '';
        $this->resetPage();
    }

    public function toggleActive(int $metaTagId): void
    {
        $metaTag = MetaTag::findOrFail($metaTagId);
        $metaTag->update(['is_active' => ! $metaTag->is_active]);
        $status = $metaTag->is_active ? 'activé' : 'désactivé';
        $this->dispatch('toast', type: 'success', message: "Meta tag {$metaTag->url_pattern} {$status}.");
    }

    public function render(): \Illuminate\View\View
    {
        $metaTags = MetaTag::when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
            ->where('url_pattern', 'like', "%{$this->search}%")
            ->orWhere('title', 'like', "%{$this->search}%")
        ))
            ->when($this->filterActive !== '', fn ($q) => $q->where('is_active', (bool) $this->filterActive))
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate((int) Settings::get('backoffice.meta_tags_per_page', 15));

        return view('backoffice::livewire.meta-tags-table', compact('metaTags'));
    }
}
