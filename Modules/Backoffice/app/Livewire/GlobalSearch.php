<?php

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Livewire\Component;
use Modules\Search\Services\SearchService;

class GlobalSearch extends Component
{
    public string $query = '';

    public function render()
    {
        if (strlen($this->query) < 2) {
            return view('backoffice::livewire.global-search', [
                'users' => collect(),
                'articles' => collect(),
                'settings' => collect(),
            ]);
        }

        $service = app(SearchService::class);
        $results = $service->searchNavbar($this->query, 3);

        return view('backoffice::livewire.global-search', $results);
    }
}
