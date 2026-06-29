<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use App\Models\ContactMessage;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Backoffice\Livewire\Concerns\WithInfiniteScroll;

class ContactMessagesTable extends Component
{
    use WithInfiniteScroll, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    /** Filtre de statut : '', 'new', 'read' ou 'spam'. */
    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $search = '';

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    public function resetFilters(): void
    {
        $this->filterStatus = '';
        $this->search = '';
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    /**
     * Supprime un message de contact.
     * Requiert la permission delete_contacts (cohérent avec la route DELETE).
     */
    public function deleteMessage(int $id): void
    {
        abort_if(! auth()->user()?->can('delete_contacts'), 403);
        ContactMessage::findOrFail($id)->delete();
        $this->dispatch('toast', type: 'success', message: __('Message supprimé.'));
    }

    /**
     * Réhabilite un message spam (faux positif) : status 'spam' -> 'new'.
     */
    public function markLegit(int $id): void
    {
        $msg = ContactMessage::findOrFail($id);
        if ($msg->isSpam()) {
            $msg->update(['status' => 'new', 'spam_reason' => null]);
        }
        $this->dispatch('toast', type: 'success', message: __('Message marqué comme légitime.'));
    }

    public function render(): View
    {
        $query = ContactMessage::query()->latest();

        if ($this->filterStatus === 'spam') {
            $query->where('status', 'spam');
        } elseif (in_array($this->filterStatus, ['new', 'read'], true)) {
            $query->where('status', $this->filterStatus);
        } else {
            // Boîte par défaut : légitimes uniquement (new + read), sans spam
            $query->whereIn('status', ['new', 'read']);
        }

        if ($this->search !== '') {
            $s = $this->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('subject', 'like', "%{$s}%");
            });
        }

        $messages = $query->paginate($this->perPage);
        $unreadCount = ContactMessage::unread()->count();
        $spamCount = ContactMessage::spam()->count();

        return view('backoffice::livewire.contact-messages-table', compact('messages', 'unreadCount', 'spamCount'));
    }
}
