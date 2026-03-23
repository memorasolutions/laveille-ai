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
            style="background: none; border: 1px solid #E5E7EB; border-radius: var(--r-btn, 6px); padding: 6px 14px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px;"
            :style="saved ? 'background: #FEF3C7; border-color: #F59E0B; color: #92400E;' : 'background: #fff; color: #6B7280;'"
            onmouseover="this.style.borderColor='var(--c-primary)'" onmouseout="if(!this.__x.$data.saved)this.style.borderColor='#E5E7EB'">
        <span x-text="saved ? '🔖' : '🏷️'"></span>
        <span x-text="saved ? '{{ __('Sauvegardé') }}' : '{{ __('Sauvegarder') }}'"></span>
    </button>
</div>
@endif
@endauth
