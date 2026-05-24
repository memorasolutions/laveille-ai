<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Livewire\Component;
use Modules\Authors\Services\ArticlePromptBuilderService;

class ArticleBuilder extends Component
{
    public int $currentStep = 1;

    public string $subject = '';
    public string $audience = '';
    public string $tone = 'pedagogue';
    public string $angle = 'guide_pratique';
    public string $length = 'moyen_1500';
    public string $techLevel = 'intermediaire';
    public string $targetAi = 'claude';

    public string $keywordsInput = '';
    public array $selectedSources = [];
    public ?array $generatedResult = null;

    public function nextStep(): void
    {
        if ($this->currentStep < 4) {
            $this->currentStep++;
        }
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function generate(): void
    {
        $keywords = array_filter(array_map('trim', explode(',', $this->keywordsInput)));

        $params = [
            'subject' => $this->subject,
            'audience' => $this->audience,
            'tone' => $this->tone,
            'angle' => $this->angle,
            'length' => $this->length,
            'tech_level' => $this->techLevel,
            'target_ai' => $this->targetAi,
            'keywords' => $keywords,
            'sources' => $this->selectedSources,
        ];

        try {
            $this->generatedResult = app(ArticlePromptBuilderService::class)->build($params);
        } catch (\InvalidArgumentException $e) {
            $this->addError('subject', $e->getMessage());
        }
    }

    public function render()
    {
        return view('authors::livewire.article-builder');
    }
}
