{{--
    Partial réutilisable : scroll infini + bouton de secours.

    Usage :
      @include('backoffice::partials.infinite-scroll', ['paginator' => $items])

    Le composant Livewire parent doit implémenter le trait WithInfiniteScroll
    et exposer la méthode loadMore().

    La sentinelle Alpine utilise IntersectionObserver natif (aucun plugin requis).
--}}

@if ($paginator->hasMorePages())
    <div class="d-flex flex-column align-items-center gap-2 mt-3"
         role="region"
         aria-label="{{ __('Chargement de résultats supplémentaires') }}">

        {{-- Bouton accessible : secours si JS est lent ou la sentinelle échoue --}}
        <button
            type="button"
            class="btn btn-outline-secondary btn-sm"
            wire:click="loadMore"
            wire:loading.attr="disabled"
            aria-label="{{ __('Charger les résultats suivants') }}"
        >
            <span wire:loading.remove wire:target="loadMore">{{ __('Charger plus') }}</span>
            <span wire:loading wire:target="loadMore" aria-live="polite">{{ __('Chargement…') }}</span>
        </button>

        {{--
            Sentinelle IntersectionObserver.
            Déclenchement : quand l'élément entre dans le viewport (marge 150px).
            Le drapeau `loading` évite les appels concurrents.
        --}}
        <div
            x-data="{ loading: false }"
            x-init="
                (function(sentinel) {
                    var obs = new IntersectionObserver(function(entries) {
                        if (entries[0].isIntersecting && !$data.loading) {
                            $data.loading = true;
                            $wire.loadMore().then(function() {
                                $data.loading = false;
                            });
                        }
                    }, { rootMargin: '150px' });
                    obs.observe(sentinel);
                    sentinel._infiniteScrollObs = obs;
                })($el)
            "
            style="height: 2px; width: 100%;"
            aria-hidden="true"
        ></div>
    </div>
@endif
