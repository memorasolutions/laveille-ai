<?php

declare(strict_types=1);

namespace Modules\Authors\Livewire;

use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Authors\Models\AuthorAffiliateLink;

class AffiliateLinkManager extends Component
{
    use WithPagination;

    public int $authorProfileId;

    public ?int $editingId = null;

    public string $slug = '';

    public string $destinationUrl = '';

    public string $label = '';

    public function mount(int $authorProfileId): void
    {
        $this->authorProfileId = $authorProfileId;
    }

    public function resetForm(): void
    {
        $this->reset(['slug', 'destinationUrl', 'label', 'editingId']);
    }

    public function startCreate(): void
    {
        $this->resetForm();
    }

    public function startEdit(int $linkId): void
    {
        $link = AuthorAffiliateLink::where('author_profile_id', $this->authorProfileId)
            ->findOrFail($linkId);

        $this->editingId = $link->id;
        $this->slug = $link->slug;
        $this->destinationUrl = $link->destination_url;
        $this->label = $link->label ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                $this->editingId
                    ? Rule::unique('author_affiliate_links', 'slug')
                        ->ignore($this->editingId)
                        ->whereNull('deleted_at')
                    : Rule::unique('author_affiliate_links', 'slug')
                        ->whereNull('deleted_at'),
            ],
            'destinationUrl' => ['required', 'url', 'max:500'],
            'label' => ['nullable', 'string', 'max:200'],
        ]);

        if ($this->editingId) {
            AuthorAffiliateLink::where('id', $this->editingId)
                ->where('author_profile_id', $this->authorProfileId)
                ->update([
                    'slug' => $this->slug,
                    'destination_url' => $this->destinationUrl,
                    'label' => $this->label ?: null,
                ]);
        } else {
            AuthorAffiliateLink::create([
                'author_profile_id' => $this->authorProfileId,
                'slug' => $this->slug,
                'destination_url' => $this->destinationUrl,
                'label' => $this->label ?: null,
            ]);
        }

        $msg = $this->editingId ? 'Lien modifié.' : 'Lien créé.';
        $this->resetForm();
        session()->flash('success', $msg);
    }

    public function delete(int $linkId): void
    {
        AuthorAffiliateLink::where('id', $linkId)
            ->where('author_profile_id', $this->authorProfileId)
            ->delete();

        session()->flash('success', 'Lien supprimé.');
    }

    public function render()
    {
        $links = AuthorAffiliateLink::where('author_profile_id', $this->authorProfileId)
            ->latest()
            ->paginate(20);

        return view('authors::livewire.affiliate-link-manager', compact('links'));
    }
}
