<?php

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Livewire\Component;
use Modules\Settings\Models\Setting;

class LookerStudioStats extends Component
{
    public string $lookerUrl = '';

    public function mount(): void
    {
        $this->lookerUrl = Setting::where('key', 'looker_studio_url')->value('value') ?? '';
    }

    public function render(): \Illuminate\View\View
    {
        return view('backoffice::livewire.looker-studio-stats');
    }
}
