<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Livewire\Component;
use Modules\Authors\Models\AuthorProfile;

class AuthorDashboard extends Component
{
    public string $activeTab = 'composer';

    public ?int $authorProfileId = null;

    public function mount(?int $authorProfileId = null): void
    {
        $this->authorProfileId = $authorProfileId;
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['composer', 'articles', 'curation', 'builders', 'parametres', 'stats'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function render()
    {
        $author = $this->authorProfileId ? AuthorProfile::find($this->authorProfileId) : null;

        return view('authors::livewire.author-dashboard', compact('author'));
    }
}
