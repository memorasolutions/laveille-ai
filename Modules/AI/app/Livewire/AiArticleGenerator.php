<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Livewire;

use Livewire\Component;
use Modules\AI\Services\AiService;
use Modules\Settings\Models\Setting;

class AiArticleGenerator extends Component
{
    public bool $showModal = false;

    public string $topic = '';

    public string $tone = 'professional';

    public string $length = 'medium';

    public string $locale = 'fr';

    /** @var array<string, mixed> */
    public array $generatedContent = [];

    public bool $isGenerating = false;

    public string $error = '';

    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->topic = '';
        $this->tone = 'professional';
        $this->length = 'medium';
        $this->locale = 'fr';
        $this->generatedContent = [];
        $this->isGenerating = false;
        $this->error = '';
    }

    public function generate(): void
    {
        $this->validate([
            'topic' => 'required|string|min:3|max:200',
        ]);

        $this->isGenerating = true;
        $this->error = '';
        $this->generatedContent = [];

        try {
            /** @var AiService $service */
            $service = app(AiService::class);
            $this->generatedContent = $service->generateArticle(
                $this->topic,
                $this->tone,
                $this->length,
                $this->locale
            );
        } catch (\Exception $e) {
            $this->error = __('Une erreur est survenue. Veuillez réessayer.');
        }

        $this->isGenerating = false;
    }

    public function applyField(string $field): void
    {
        if (array_key_exists($field, $this->generatedContent)) {
            $this->dispatch('ai-article-fill', field: $field, value: $this->generatedContent[$field]);
        }
    }

    public function applyAll(): void
    {
        $this->dispatch('ai-article-fill-all', data: $this->generatedContent);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $enabled = (bool) Setting::get('ai.chatbot_enabled', false);

        return view('ai::livewire.ai-article-generator', ['enabled' => $enabled]);
    }
}
