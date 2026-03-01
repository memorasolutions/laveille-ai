<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Backoffice\Models\FeatureFlagCondition;
use Spatie\Permission\Models\Role;

class FeatureFlagsTable extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    public string $editingCondition = '';

    public string $conditionType = 'always';

    /** @var array<string, mixed> */
    public array $conditionConfig = [];

    public array $knownFeatures = [
        'module-blog',
        'module-newsletter',
        'module-saas',
        'module-tenancy',
        'module-webhooks',
        'module-search',
        'dark-mode',
        'two-factor-auth',
        'api-access',
        'export-csv',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function editCondition(string $name): void
    {
        /** @var FeatureFlagCondition|null $condition */
        $condition = FeatureFlagCondition::where('feature_name', $name)->first();
        $this->editingCondition = $name;
        $this->conditionType = $condition ? $condition->condition_type : 'always';
        $this->conditionConfig = $condition ? ($condition->condition_config ?? []) : [];
    }

    public function saveCondition(): void
    {
        $this->validate([
            'conditionType' => 'required|in:always,percentage,roles,environment,schedule',
        ]);

        FeatureFlagCondition::updateOrCreate(
            ['feature_name' => $this->editingCondition],
            [
                'condition_type' => $this->conditionType,
                'condition_config' => $this->conditionConfig ?: null,
            ]
        );

        $this->cancelEdit();
        $this->dispatch('toast', type: 'success', message: 'Conditions mises à jour.');
    }

    public function cancelEdit(): void
    {
        $this->editingCondition = '';
        $this->conditionType = 'always';
        $this->conditionConfig = [];
    }

    public function render(): \Illuminate\View\View
    {
        $features = DB::table('features')
            ->where('scope', 'global')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(20);

        $knownFeatures = $this->knownFeatures;
        $conditions = FeatureFlagCondition::all()->keyBy('feature_name');
        $availableTypes = FeatureFlagCondition::availableTypes();
        $roles = Role::pluck('name');

        return view('backoffice::livewire.feature-flags-table', compact(
            'features',
            'knownFeatures',
            'conditions',
            'availableTypes',
            'roles'
        ));
    }
}
