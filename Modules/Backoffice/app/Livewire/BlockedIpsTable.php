<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Auth\Models\BlockedIp;
use Modules\Backoffice\Livewire\Concerns\WithInfiniteScroll;

class BlockedIpsTable extends Component
{
    use WithInfiniteScroll, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    /**
     * Débloque une IP : supprime l'entrée de la liste.
     * Requiert la permission manage_security (cohérent avec la route DELETE).
     */
    public function unblock(int $id): void
    {
        abort_if(! auth()->user()?->can('manage_security'), 403);
        $ip = BlockedIp::findOrFail($id);
        $address = $ip->ip_address;
        $ip->delete();
        $this->dispatch('toast', type: 'success', message: "IP {$address} débloquée.");
    }

    public function render(): View
    {
        $blockedIps = BlockedIp::latest()->paginate($this->perPage);

        return view('backoffice::livewire.blocked-ips-table', compact('blockedIps'));
    }
}
