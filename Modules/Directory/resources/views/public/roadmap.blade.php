<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Roadmap') . ' - ' . config('app.name'))
@section('meta_description', __('Votez pour prioriser les améliorations du répertoire techno. Roadmap publique de La veille.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Roadmap')])
@endsection

@php
    $pending = $suggestions->get('pending', collect());
    $planned = $suggestions->get('approved', collect())->merge($suggestions->get('planned', collect()))->merge($suggestions->get('in_progress', collect()));
    $done = $suggestions->get('done', collect());
    $total = $pending->count() + $planned->count() + $done->count();
@endphp

@section('content')
<section class="section-padding" style="padding-top: 20px;">
<div class="container">

    <div style="text-align: center; margin-bottom: 32px;">
        <h1 style="font-family: var(--f-heading); font-weight: 800; font-size: 2rem; color: var(--c-dark);">🗺️ {{ __('Roadmap publique') }}</h1>
        <p style="color: #6B7280; font-size: 1.1rem;">{{ __('Votez pour prioriser les améliorations du répertoire') }}</p>
    </div>

    @if(!$total)
        <div style="text-align: center; padding: 60px 20px; background: #F9FAFB; border-radius: 16px; border: 1px dashed #D1D5DB;">
            <div style="font-size: 48px; margin-bottom: 12px;">🗺️</div>
            <h3 style="font-family: var(--f-heading); color: var(--c-dark);">{{ __('La roadmap est vide') }}</h3>
            <p style="color: #6B7280;">{{ __('Suggérez des améliorations sur les fiches outils pour alimenter cette page !') }}</p>
            <a href="{{ route('directory.index') }}" style="display: inline-block; background: var(--c-primary); color: #fff; padding: 10px 24px; border-radius: var(--r-btn); font-weight: 600; text-decoration: none; margin-top: 12px;">{{ __('Voir le répertoire') }}</a>
        </div>
    @else
        <div class="row">
            @foreach([
                ['🟡 ' . __('Soumis'), $pending, '#F59E0B'],
                ['🔵 ' . __('Planifié / En cours'), $planned, '#3B82F6'],
                ['🟢 ' . __('Terminé'), $done, '#10B981'],
            ] as $col)
            <div class="col-md-4" style="margin-bottom: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;">
                    <h3 style="font-family: var(--f-heading); font-size: 15px; font-weight: 700; margin: 0; color: var(--c-dark);">{!! $col[0] !!}</h3>
                    <span style="background: {{ $col[2] }}; color: #fff; padding: 3px 10px; border-radius: 99px; font-size: 12px; font-weight: 700;">{{ $col[1]->count() }}</span>
                </div>

                @forelse($col[1]->sortByDesc('votes_count') as $s)
                <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 12px; padding: 14px; margin-bottom: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                    <div style="display: flex; gap: 10px;">
                        <div style="min-width: 50px; text-align: center;" x-data="{ votes: {{ (int) $s->votes_count }}, loading: false }">
                            @auth
                            <button @click="loading=true; fetch('{{ route('directory.roadmap.vote', $s->id) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}}).then(r=>r.json()).then(d=>{votes=d.votes;loading=false}).catch(()=>loading=false)"
                                    :disabled="loading"
                                    style="width: 50px; border-radius: 10px; border: 1px solid #E5E7EB; background: #F9FAFB; cursor: pointer; padding: 6px 0; font-weight: 700; font-size: 13px; color: var(--c-primary); transition: all 0.2s;"
                                    onmouseover="this.style.borderColor='var(--c-primary)'" onmouseout="this.style.borderColor='#E5E7EB'">
                                ▲ <span x-text="votes"></span>
                            </button>
                            @else
                            <div style="width: 50px; border-radius: 10px; border: 1px solid #E5E7EB; background: #F9FAFB; padding: 6px 0; font-weight: 700; font-size: 13px; color: #6B7280;">
                                ▲ <span x-text="votes"></span>
                            </div>
                            @endauth
                        </div>
                        <div style="flex: 1;">
                            <div style="margin-bottom: 6px;">
                                @php $src = $s->getSourceLabel(); @endphp
                                <span style="background: {{ $src['color'] }}22; color: {{ $src['color'] }}; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700;">{{ $src['emoji'] }} {{ $src['name'] }}</span>
                                <span style="background: var(--c-primary-badge, #E0E7FF); color: var(--c-primary); padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">{{ $s->getItemName() }}</span>
                                <span style="background: #F3F4F6; color: #6B7280; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 4px;">{{ \Modules\Directory\Models\ToolSuggestion::fieldLabels()[$s->field] ?? $s->field }}</span>
                            </div>
                            <div style="font-weight: 600; color: var(--c-dark); font-size: 14px; line-height: 1.4;">{{ Str::limit($s->suggested_value, 80) }}</div>
                            <div style="margin-top: 6px; color: #9CA3AF; font-size: 11px;">{{ __('Par') }} <strong>{{ $s->user->name ?? __('Anonyme') }}</strong> · {{ $s->created_at->format('d/m/Y') }}</div>
                        </div>
                    </div>
                </div>
                @empty
                <div style="background: #F9FAFB; border: 1px dashed #D1D5DB; border-radius: 12px; padding: 20px; text-align: center; color: #9CA3AF; font-size: 14px;">
                    {{ __('Rien ici pour le moment') }}
                </div>
                @endforelse
            </div>
            @endforeach
        </div>
    @endif
</div>
</section>
@endsection
