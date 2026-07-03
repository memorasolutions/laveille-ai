<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Livewire\Component;
use Modules\Authors\Models\AuthorProfile;

class AuthorSettings extends Component
{
    public ?int $authorProfileId = null;

    public array $modulesVisible = [];

    public string $savedMessage = '';

    public function mount(?int $authorProfileId = null): void
    {
        $author = $authorProfileId !== null ? AuthorProfile::findOrFail($authorProfileId) : null;
        abort_if($author !== null && $author->user_id !== auth()->id(), 403);

        $this->authorProfileId = $authorProfileId;

        $this->modulesVisible = $author?->modules_visible ?? AuthorProfile::MODULES_DEFAULTS;

        foreach (array_keys(AuthorProfile::TOGGLABLE_MODULES) as $key) {
            if (! isset($this->modulesVisible[$key])) {
                $this->modulesVisible[$key] = AuthorProfile::MODULES_DEFAULTS[$key] ?? true;
            }
        }
    }

    public function toggleModule(string $key): void
    {
        $this->modulesVisible[$key] = ! ($this->modulesVisible[$key] ?? true);
        $this->save();
    }

    public function save(): void
    {
        $author = AuthorProfile::findOrFail($this->authorProfileId);
        $author->modules_visible = $this->modulesVisible;
        $author->save();

        $this->savedMessage = '✓ Sauvegardé';
        $this->dispatch('author-modules-saved');
    }

    public function resetDefaults(): void
    {
        $this->modulesVisible = AuthorProfile::MODULES_DEFAULTS;
        $this->save();
    }

    public function render()
    {
        return view('authors::livewire.author-settings', [
            'togglableModules' => AuthorProfile::TOGGLABLE_MODULES,
        ]);
    }
}
