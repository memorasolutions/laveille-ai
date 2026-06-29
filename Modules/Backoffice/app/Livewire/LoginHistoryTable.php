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
use Modules\Auth\Models\LoginAttempt;
use Modules\Backoffice\Livewire\Concerns\WithInfiniteScroll;

class LoginHistoryTable extends Component
{
    use WithInfiniteScroll, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    public function render(): View
    {
        $attempts = LoginAttempt::with('user')
            ->latest('logged_in_at')
            ->paginate($this->perPage);

        return view('backoffice::livewire.login-history-table', compact('attempts'));
    }
}
