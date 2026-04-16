<section class="rt-related-collections" style="padding:30px 0; border-top:1px solid #eee; margin-top:30px">
    @if($relatedCollections->isNotEmpty())
        <h3 style="font-size:18px; font-weight:600; color:#222; margin:0 0 6px">Dans les collections</h3>
        <p style="font-size:14px; color:#666; margin:0">
            Cet outil apparaît dans {{ $relatedCollections->count() }} collection{{ $relatedCollections->count() > 1 ? 's' : '' }} publique{{ $relatedCollections->count() > 1 ? 's' : '' }}
        </p>

        <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:16px">
            @foreach($relatedCollections->take(5) as $collection)
                <a href="{{ route('collections.show', $collection->slug) }}"
                   rel="nofollow"
                   aria-label="Collection {{ $collection->name }} - {{ $collection->tools_count }} outil{{ $collection->tools_count > 1 ? 's' : '' }}"
                   style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; background:#f5f5f5; color:#333; border-radius:20px; text-decoration:none; font-size:13px; font-weight:500; border:1px solid #e0e0e0; transition:all .2s"
                   onmouseover="this.style.background='#ebebeb'; this.style.borderColor='#ccc'"
                   onmouseout="this.style.background='#f5f5f5'; this.style.borderColor='#e0e0e0'">
                    <span aria-hidden="true">📁</span>
                    <span>{{ $collection->name }}</span>
                    <span style="background:#e0e0e0; color:#555; font-size:11px; padding:2px 8px; border-radius:10px; font-weight:600">{{ $collection->tools_count }} outil{{ $collection->tools_count > 1 ? 's' : '' }}</span>
                </a>
            @endforeach
        </div>

        @if($relatedCollections->count() > 5)
            <div style="margin-top:14px">
                <a href="{{ route('collections.index') }}"
                   rel="nofollow"
                   aria-label="Voir toutes les collections"
                   style="font-size:13px; color:var(--c-primary, #0B7285); text-decoration:none; font-weight:500"
                   onmouseover="this.style.textDecoration='underline'"
                   onmouseout="this.style.textDecoration='none'">
                    + Voir toutes les collections &rarr;
                </a>
            </div>
        @endif
    @endif

    <div style="margin-top:24px; padding:16px 20px; background:#fafafa; border:1px solid #eee; border-radius:8px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px">
        <span style="font-size:13px; color:#555">Créer une collection avec {{ $tool->name }}</span>
        @auth
            <a href="{{ route('collections.my') }}"
               aria-label="Aller à mes collections"
               style="font-size:13px; font-weight:600; color:var(--c-primary, #0B7285); text-decoration:none; padding:6px 14px; border:1px solid var(--c-primary, #0B7285); border-radius:16px"
               onmouseover="this.style.background='var(--c-primary, #0B7285)'; this.style.color='#fff'"
               onmouseout="this.style.background='transparent'; this.style.color='var(--c-primary, #0B7285)'">
                Mes collections
            </a>
        @else
            <a href="{{ Route::has('login') ? route('login') : '/login' }}"
               aria-label="Se connecter pour créer une collection"
               style="font-size:13px; font-weight:600; color:var(--c-primary, #0B7285); text-decoration:none; padding:6px 14px; border:1px solid var(--c-primary, #0B7285); border-radius:16px"
               onmouseover="this.style.background='var(--c-primary, #0B7285)'; this.style.color='#fff'"
               onmouseout="this.style.background='transparent'; this.style.color='var(--c-primary, #0B7285)'">
                Se connecter
            </a>
        @endauth
    </div>
</section>
