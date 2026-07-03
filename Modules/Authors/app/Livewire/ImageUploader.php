<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Authors\Models\AuthorProfile;
use Modules\Authors\Services\ImagePipelineService;

class ImageUploader extends Component
{
    use WithFileUploads;

    public $image = null;

    public string $altText = '';

    public ?int $authorProfileId = null;

    public ?array $result = null;

    public bool $processing = false;

    public function mount(?int $authorProfileId = null): void
    {
        if ($authorProfileId !== null) {
            $profile = AuthorProfile::findOrFail($authorProfileId);
            abort_if($profile->user_id !== auth()->id(), 403);
        }

        $this->authorProfileId = $authorProfileId;
    }

    public function process(): void
    {
        if ($this->image === null || $this->authorProfileId === null) {
            return;
        }

        $this->processing = true;
        $this->result = null;

        try {
            $this->result = app(ImagePipelineService::class)->process(
                $this->image->getRealPath(),
                $this->authorProfileId,
                $this->altText
            );
        } finally {
            $this->processing = false;
        }
    }

    public function render()
    {
        return view('authors::livewire.image-uploader');
    }
}
