<?php

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

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

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
        $this->resetPage();
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

        $media = $query->orderBy($this->sortBy, $this->sortDirection)->paginate(20);

        return view('backoffice::livewire.media-table', compact('media'));
    }
}
