<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire\Concerns;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Ajoute le scroll infini aux composants Livewire paginés (admin).
 *
 * Intégration (3 étapes) :
 *
 *   1. Déclarer le trait dans la classe :
 *        use WithInfiniteScroll;   // en plus de WithPagination
 *
 *   2. Dans render(), paginer avec $this->perPage (page 1, perPage grandit) :
 *        ->paginate($this->perPage)
 *      Idem dans getBulkPageIds() si le composant l'implémente.
 *
 *   3. Après chaque resetPage() dans les hooks updatingXxx / resetFilters(),
 *      appeler $this->resetInfiniteScroll() pour remettre perPage au pas initial.
 *
 *   4. Dans la vue, remplacer `{{ $items->links() }}` par :
 *        Blade: include('backoffice::partials.infinite-scroll', ['paginator' => $items])
 *
 * Surcharge du pas : déclarer dans la classe enfant :
 *   protected int $perPageStep = 50;
 */
trait WithInfiniteScroll
{
    /** Nombre de lignes visibles (grandit au fil des chargements). */
    public int $perPage = 25;

    /** Incrément ajouté à chaque appel de loadMore(). */
    protected int $perPageStep = 25;

    /**
     * Charge la page suivante : augmente perPage d'un pas.
     * Appelé par wire:click="loadMore" et par la sentinelle Alpine.
     */
    public function loadMore(): void
    {
        $this->perPage += $this->perPageStep;
    }

    /**
     * Remet perPage au pas initial.
     * À appeler systématiquement avec resetPage() lors d'un changement de
     * filtre, de recherche ou de tri, pour repartir de zéro.
     */
    public function resetInfiniteScroll(): void
    {
        $this->perPage = $this->perPageStep;
    }

    /**
     * Indique s'il reste des lignes à charger.
     *
     * @param  LengthAwarePaginator<int, mixed>|Paginator<int, mixed>  $paginator
     */
    protected function hasMoreRows(LengthAwarePaginator|Paginator $paginator): bool
    {
        return $paginator->hasMorePages();
    }
}
