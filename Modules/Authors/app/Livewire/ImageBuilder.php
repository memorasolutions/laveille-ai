<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Livewire\Component;
use Modules\Authors\Services\ImagePromptBuilderService;

class ImageBuilder extends Component
{
    public string $subject = '';
    public string $style = 'realiste';
    public string $composition = 'paysage_16_9';
    public string $mood = 'lumineux';
    public string $usage = 'cover_article';
    public string $targetAi = 'dalle3';
    public string $aspectRatio = '16:9';
    public string $quality = 'standard';

    public array $colors = ['teal', 'orange'];

    public ?array $generatedResult = null;

    public function generate(): void
    {
        $params = [
            'subject' => $this->subject,
            'style' => $this->style,
            'composition' => $this->composition,
            'colors' => $this->colors,
            'mood' => $this->mood,
            'usage' => $this->usage,
            'target_ai' => $this->targetAi,
            'aspect_ratio' => $this->aspectRatio,
            'quality' => $this->quality,
        ];

        $this->generatedResult = app(ImagePromptBuilderService::class)->build($params);
    }

    public function render()
    {
        return view('authors::livewire.image-builder');
    }
}
