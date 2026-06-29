<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Auth\Models\LoginAttempt;
use Modules\Core\Traits\HasBulkActions;
use Modules\Core\Traits\HasTableSorting;
use Modules\Settings\Facades\Settings;
use Spatie\Permission\Models\Role;

class UsersTable extends Component
{
    use HasBulkActions, HasTableSorting, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $filterRole = '';

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    /** @var list<string> Colonnes autorisées pour le tri (liste blanche). */
    protected array $allowedSortColumns = ['name', 'email', 'created_at', 'last_login_at'];

    /**
     * Surcharge du trait HasTableSorting : valide la colonne avant de la stocker.
     */
    public function sort(string $column): void
    {
        if (! in_array($column, $this->allowedSortColumns, true)) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterRole(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->filterStatus = '';
        $this->filterRole = '';
        $this->search = '';
        $this->resetPage();
    }

    protected function getBulkActions(): array
    {
        return [
            'activate' => __('Activer'),
            'deactivate' => __('Désactiver'),
            'delete' => __('Supprimer'),
        ];
    }

    protected function handleBulkAction(string $action, array $ids): void
    {
        match ($action) {
            'activate' => User::whereIn('id', $ids)->update(['is_active' => true]),
            'deactivate' => User::whereIn('id', $ids)->update(['is_active' => false]),
            'delete' => User::whereIn('id', $ids)->where('id', '!=', auth()->id())->delete(),
            default => null,
        };
    }

    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);
        $user->update(['is_active' => ! $user->is_active]);
        $status = $user->is_active ? 'activé' : 'désactivé';
        $this->dispatch('toast', type: 'success', message: "Utilisateur {$user->name} {$status}.");
    }

    public function inlineUpdateName(int $userId, string $name): void
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return;
        }
        User::findOrFail($userId)->update(['name' => $trimmed]);
    }

    protected function getBulkPageIds(): array
    {
        $validSort = in_array($this->sortBy, $this->allowedSortColumns, true) ? $this->sortBy : 'name';

        return User::query()
            ->addSelect(['last_login_at' => $this->lastLoginSubquery()])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
            ->when($this->filterStatus === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->filterStatus === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->filterRole, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $this->filterRole)))
            ->orderBy($validSort, $this->sortDirection)
            ->paginate((int) Settings::get('backoffice.users_per_page', 15))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function render(): \Illuminate\View\View
    {
        $validSort = in_array($this->sortBy, $this->allowedSortColumns, true) ? $this->sortBy : 'name';

        $users = User::query()
            ->with('roles')
            ->addSelect(['last_login_at' => $this->lastLoginSubquery()])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
            ->when($this->filterStatus === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->filterStatus === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($this->filterRole, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $this->filterRole)))
            ->orderBy($validSort, $this->sortDirection)
            ->paginate((int) Settings::get('backoffice.users_per_page', 15));

        $roles = Role::orderBy('name')->get();

        return view('backoffice::livewire.users-table', compact('users', 'roles'));
    }

    /**
     * Sous-requête : date de la dernière connexion réussie de l'utilisateur.
     *
     * @return \Illuminate\Database\Eloquent\Builder<LoginAttempt>
     */
    private function lastLoginSubquery(): \Illuminate\Database\Eloquent\Builder
    {
        return LoginAttempt::query()
            ->select('logged_in_at')
            ->whereColumn('user_id', 'users.id')
            ->where('status', 'success')
            ->latest('logged_in_at')
            ->limit(1);
    }
}
