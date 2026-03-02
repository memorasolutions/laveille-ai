<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaTable extends Component
{
    use WithFileUploads, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterType = '';

    #[Url]
    public string $filterFolder = '';

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public ?int $editingMediaId = null;

    public string $editTitle = '';

    public string $editAltText = '';

    public string $editCaption = '';

    public string $editDescription = '';

    public string $editFolder = '';

    protected string $paginationTheme = 'bootstrap';

    #[Validate('required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,csv,mp4,mov')]
    public mixed $file = null;

    public function upload(): void
    {
        $this->validate();

        /** @var \App\Models\User $user */
        $user = auth()->user();

        $tmpPath = $this->file->getRealPath();
        $user->addMedia($tmpPath)
            ->usingFileName($this->file->getClientOriginalName())
            ->toMediaCollection('gallery');

        $this->file = null;
        $this->dispatch('toast', type: 'success', message: 'Fichier uploadé avec succès.');
        $this->dispatch('$refresh');
    }

    public function sort(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterType = '';
        $this->filterFolder = '';
        $this->resetPage();
    }

    public function editMedia(int $id): void
    {
        $media = Media::findOrFail($id);
        $this->editingMediaId = $id;
        $this->editTitle = $media->getCustomProperty('title', '');
        $this->editAltText = $media->getCustomProperty('alt_text', '');
        $this->editCaption = $media->getCustomProperty('caption', '');
        $this->editDescription = $media->getCustomProperty('description', '');
        $this->editFolder = $media->getCustomProperty('folder', '');
    }

    public function updateMedia(): void
    {
        $this->validate([
            'editTitle' => 'nullable|string|max:255',
            'editAltText' => 'nullable|string|max:255',
            'editCaption' => 'nullable|string|max:500',
            'editDescription' => 'nullable|string|max:1000',
            'editFolder' => 'nullable|string|max:100',
        ]);

        $media = Media::findOrFail($this->editingMediaId);
        $media->setCustomProperty('title', $this->editTitle);
        $media->setCustomProperty('alt_text', $this->editAltText);
        $media->setCustomProperty('caption', $this->editCaption);
        $media->setCustomProperty('description', $this->editDescription);
        $media->setCustomProperty('folder', $this->editFolder);
        $media->save();

        $this->dispatch('toast', type: 'success', message: 'Métadonnées mises à jour.');
        $this->cancelEdit();
    }

    public function cancelEdit(): void
    {
        $this->editingMediaId = null;
        $this->editTitle = '';
        $this->editAltText = '';
        $this->editCaption = '';
        $this->editDescription = '';
        $this->editFolder = '';
    }

    public function deleteMedia(int $id): void
    {
        $media = Media::findOrFail($id);
        $media->delete();
        $this->dispatch('toast', type: 'success', message: 'Média supprimé.');
        $this->dispatch('$refresh');
    }

    public function render(): \Illuminate\View\View
    {
        $query = Media::query();

        if ($this->search !== '') {
            $query->where('file_name', 'like', '%'.$this->search.'%');
        }

        if ($this->filterType === 'image') {
            $query->where('mime_type', 'like', 'image/%');
        } elseif ($this->filterType === 'document') {
            $query->whereIn('mime_type', [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
                'text/csv',
            ]);
        } elseif ($this->filterType === 'video') {
            $query->where('mime_type', 'like', 'video/%');
        }

        if ($this->filterFolder !== '') {
            $query->where('custom_properties->folder', $this->filterFolder);
        }

        $media = $query->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        // Get distinct folders for the filter dropdown (DB-agnostic, works with SQLite + MySQL)
        $folders = Media::query()
            ->whereNotNull('custom_properties->folder')
            ->pluck('custom_properties')
            ->map(fn ($props) => is_array($props) ? ($props['folder'] ?? '') : (json_decode((string) $props, true)['folder'] ?? ''))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('backoffice::livewire.media-table', compact('media', 'folders'));
    }
}
