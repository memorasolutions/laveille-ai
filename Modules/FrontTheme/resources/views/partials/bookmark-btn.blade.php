{{-- Bookmark toggle button — réutilisable partout
     Usage: @include('fronttheme::partials.bookmark-btn', ['type' => Article::class, 'id' => $article->id])
     Requires: auth, route bookmark.toggle --}}
@auth
@if(Route::has('bookmark.toggle'))
<div x-data="{ saved: {{ class_exists(\Modules\Core\Models\Bookmark::class) && \Modules\Core\Models\Bookmark::where('user_id', auth()->id())->where('bookmarkable_type', $type)->where('bookmarkable_id', $id)->exists() ? 'true' : 'false' }}, loading: false }"
     style="display: inline-block;">
    <button @click="loading=true; fetch('{{ route('bookmark.toggle') }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json','Accept':'application/json'}, body:JSON.stringify({type:'{{ addslashes($type) }}',id:{{ $id }}})}).then(r=>r.json()).then(d=>{saved=d.bookmarked;loading=false}).catch(()=>loading=false)"
            :disabled="loading"
            :title="saved ? '{{ __('Retirer des favoris') }}' : '{{ __('Ajouter aux favoris') }}'"
            :aria-label="saved ? '{{ __('Retirer des favoris') }}' : '{{ __('Ajouter aux favoris') }}'"
            style="background: none; border: none; cursor: pointer; padding: 6px; transition: transform 0.2s; display: inline-flex; align-items: center; gap: 6px;"
            onmouseover="this.style.transform='scale(1.15)'" onmouseout="this.style.transform='scale(1)'">
        <svg x-show="!saved" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        <svg x-show="saved" width="24" height="24" viewBox="0 0 24 24" fill="#EF4444" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
    </button>
</div>
@endif
@endauth
