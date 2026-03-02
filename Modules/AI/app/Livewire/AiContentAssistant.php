<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Livewire;

use Livewire\Component;
use Modules\AI\Services\AiService;

class AiContentAssistant extends Component
{
    public string $content = '';

    public string $result = '';

    public string $action = 'rewrite';

    public string $style = 'professional';

    public string $targetLocale = 'fr';

    public bool $loading = false;

    public function process(): void
    {
        $this->validate([
            'content' => 'required|string|min:1',
        ]);

        $this->loading = true;

        /** @var AiService $aiService */
        $aiService = app(AiService::class);

        $this->result = match ($this->action) {
            'rewrite' => $aiService->rewriteContent($this->content, $this->style, $this->targetLocale),
            'improve' => $aiService->improveContent($this->content, $this->targetLocale),
            'summarize' => $aiService->generateSummary($this->content, $this->targetLocale),
            'translate' => $aiService->translateContent($this->content, 'fr', $this->targetLocale),
            default => $this->content,
        };

        $this->loading = false;
    }

    public function applyResult(): void
    {
        $this->content = $this->result;
        $this->result = '';
    }

    public function clear(): void
    {
        $this->result = '';
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('ai::livewire.ai-content-assistant');
    }
}
