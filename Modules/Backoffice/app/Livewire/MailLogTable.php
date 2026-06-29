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
use Modules\Backoffice\Livewire\Concerns\WithInfiniteScroll;
use Modules\Notifications\Models\SentEmail;

class MailLogTable extends Component
{
    use WithInfiniteScroll, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public function render(): View
    {
        $emails = SentEmail::latest('sent_at')->paginate($this->perPage);

        return view('backoffice::livewire.mail-log-table', compact('emails'));
    }
}
