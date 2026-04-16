{{-- Carrousel horizontal catégories réutilisable (index + compare) --}}
@if($categories->count() > 1)
<div class="rt-cat-slider" x-data="{ scrollLeft: 0 }">
    <button type="button" class="rt-cat-arrow left" aria-label="{{ __('Catégorie précédente') }}" x-show="scrollLeft > 0" x-cloak @click="$refs.catTrack.scrollBy({ left: -300, behavior: 'smooth' })"><i class="ti-angle-left" aria-hidden="true"></i></button>
    <div class="rt-cat-track" x-ref="catTrack" @scroll="scrollLeft = $refs.catTrack.scrollLeft">
        @foreach($categories as $cat)
            @if(($currentRoute ?? 'index') === 'compare')
                <a href="{{ route('directory.compare', $cat->slug) }}"
                   class="rt-cat-chip {{ (isset($activeSlug) && $activeSlug === $cat->slug) ? 'active' : '' }}"
                   style="text-decoration: none;">
                    {{ $cat->icon ?? '' }} {{ $cat->name }}
                </a>
            @else
                <button type="button" class="rt-cat-chip"
                        :class="{ active: activeCategory === '{{ $cat->slug }}' }"
                        @click="toggleCategory('{{ $cat->slug }}')">
                    {{ $cat->icon ?? '' }} {{ $cat->name }}
                </button>
            @endif
        @endforeach
    </div>
    <button type="button" class="rt-cat-arrow right" aria-label="{{ __('Catégorie suivante') }}" @click="$refs.catTrack.scrollBy({ left: 300, behavior: 'smooth' })"><i class="ti-angle-right" aria-hidden="true"></i></button>
</div>
@endif

<style>
    .rt-cat-slider { position: relative; display: flex; align-items: center; margin-bottom: 20px; }
    .rt-cat-track { display: flex; gap: 8px; overflow-x: auto; scroll-behavior: smooth; scrollbar-width: none; -ms-overflow-style: none; padding: 4px 0; flex: 1; }
    .rt-cat-track::-webkit-scrollbar { display: none; }
    .rt-cat-chip { flex-shrink: 0; display: inline-flex; align-items: center; gap: 4px; padding: 7px 14px; border-radius: 20px; background: #F3F4F6; color: var(--c-dark); font-weight: 600; font-size: 13px; border: none; cursor: pointer; transition: all 0.2s; white-space: nowrap; }
    .rt-cat-chip:hover { background: #E5E7EB; }
    .rt-cat-chip.active { background: var(--c-primary); color: #fff; }
    .rt-cat-arrow { flex-shrink: 0; width: 32px; height: 32px; border-radius: 50%; background: #fff; border: 1px solid #E5E7EB; display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--c-dark); font-size: 12px; margin: 0 4px; }
    .rt-cat-arrow:hover { background: var(--c-primary); color: #fff; border-color: var(--c-primary); }
</style>
