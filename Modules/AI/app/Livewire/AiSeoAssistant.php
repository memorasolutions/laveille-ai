<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Livewire;

use Livewire\Component;
use Modules\AI\Services\AiService;

class AiSeoAssistant extends Component
{
    public string $title = '';

    public string $content = '';

    /** @var array<string, string> */
    public array $seoResult = [];

    public bool $loading = false;

    public function generate(): void
    {
        $this->validate([
            'title' => 'required|string',
            'content' => 'required|string',
        ]);

        $this->loading = true;
        $this->seoResult = app(AiService::class)->generateSeoMeta($this->title, $this->content);
        $this->loading = false;
    }

    public function clear(): void
    {
        $this->seoResult = [];
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('ai::livewire.ai-seo-assistant');
    }
}
