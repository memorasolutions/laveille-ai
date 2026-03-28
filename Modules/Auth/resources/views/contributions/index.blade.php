<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('title', __('Mes contributions') . ' - ' . config('app.name'))

@section('user-content')

<h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">
    <i class="fa fa-handshake-o"></i> {{ __('Mes contributions') }}
</h2>
<p style="color: var(--c-text-muted); margin: 0 0 25px;">{{ __('Suivez vos suggestions et votes sur le site.') }}</p>

{{-- Cartes statistiques --}}
<div class="row" style="margin-bottom: 20px;">
    <div class="col-sm-4" style="margin-bottom: 15px;">
        <div class="user-stat-card">
            <div>
                <i class="fa fa-lightbulb-o fa-2x" style="color: #f0ad4e;"></i>
                <h3 style="margin: 5px 0 0;">{{ $suggestions->count() }}</h3>
                <small>{{ __('Suggestions') }}</small>
            </div>
        </div>
    </div>
    <div class="col-sm-4" style="margin-bottom: 15px;">
        <div class="user-stat-card">
            <div>
                <i class="fa fa-thumbs-up fa-2x" style="color: var(--c-primary);"></i>
                <h3 style="margin: 5px 0 0;">{{ $votes->count() }}</h3>
                <small>{{ __('Votes roadmap') }}</small>
            </div>
        </div>
    </div>
    <div class="col-sm-4" style="margin-bottom: 15px;">
        <div class="user-stat-card">
            <div>
                <i class="fa fa-book fa-2x" style="color: #0891B2;"></i>
                <h3 style="margin: 5px 0 0;">{{ $resources->count() }}</h3>
                <small>{{ __('Ressources') }}</small>
            </div>
        </div>
    </div>
</div>

{{-- Onglets modernes Alpine.js --}}
<div x-data="{ tab: 'suggestions' }">

    {{-- Navigation onglets avec badges --}}
    <div style="display: flex !important; flex-wrap: wrap !important; gap: 8px; margin-bottom: 24px;">
        <button @click="tab = 'suggestions'" class="btn"
                :style="tab === 'suggestions'
                    ? 'background: #fff; color: var(--c-dark); border: 2px solid var(--c-primary); border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600; box-shadow: 0 4px 12px rgba(11,114,133,0.2); transform: translateY(-1px);'
                    : 'background: rgba(255,255,255,0.7); color: var(--c-text-muted); border: 2px solid transparent; border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600;'">
            <i class="fa fa-lightbulb-o"></i> {{ __('Suggestions') }}
            @if($suggestions->count() > 0)
                <span style="background: linear-gradient(135deg, #f0ad4e, #e09b3d); color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 11px; margin-left: 6px;">{{ $suggestions->count() }}</span>
            @endif
        </button>
        <button @click="tab = 'votes'" class="btn"
                :style="tab === 'votes'
                    ? 'background: #fff; color: var(--c-dark); border: 2px solid var(--c-primary); border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600; box-shadow: 0 4px 12px rgba(11,114,133,0.2); transform: translateY(-1px);'
                    : 'background: rgba(255,255,255,0.7); color: var(--c-text-muted); border: 2px solid transparent; border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600;'">
            <i class="fa fa-thumbs-up"></i> {{ __('Votes') }}
            @if($votes->count() > 0)
                <span style="background: linear-gradient(135deg, var(--c-primary), var(--c-primary-hover)); color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 11px; margin-left: 6px;">{{ $votes->count() }}</span>
            @endif
        </button>
        <button @click="tab = 'resources'" class="btn"
                :style="tab === 'resources'
                    ? 'background: #fff; color: var(--c-dark); border: 2px solid var(--c-primary); border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600; box-shadow: 0 4px 12px rgba(11,114,133,0.2); transform: translateY(-1px);'
                    : 'background: rgba(255,255,255,0.7); color: var(--c-text-muted); border: 2px solid transparent; border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600;'">
            <i class="fa fa-book"></i> {{ __('Ressources') }}
            @if($resources->count() > 0)
                <span style="background: linear-gradient(135deg, #0891B2, #0e7490); color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 11px; margin-left: 6px;">{{ $resources->count() }}</span>
            @endif
        </button>
    </div>

    {{-- Onglet suggestions --}}
    <div x-show="tab === 'suggestions'" x-transition>
        @if($suggestions->isEmpty())
            <div style="text-align: center; padding: 60px 20px; color: var(--c-text-muted);">
                <div style="width: 80px; height: 80px; margin: 0 auto 16px; background: linear-gradient(135deg, #F3F4F6, #E5E7EB); border-radius: 20px; display: flex !important; align-items: center !important; justify-content: center !important;">
                    <i class="fa fa-lightbulb-o fa-2x" style="color: #D1D5DB;"></i>
                </div>
                <h4 style="font-family: var(--f-heading); color: var(--c-dark); margin: 0 0 8px;">{{ __('Aucune suggestion') }}</h4>
                <p style="margin: 0; font-size: 14px;">{{ __('Visitez le glossaire, le répertoire ou les acronymes pour proposer des modifications.') }}</p>
            </div>
        @else
            <div style="display: flex !important; flex-direction: column !important; gap: 10px;">
                @foreach($suggestions as $suggestion)
                <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: var(--r-base); padding: 16px 20px; display: flex !important; align-items: center !important; gap: 14px; transition: all 0.2s;"
                     onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'"
                     onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                    @php
                        $route = '#';
                        if ($suggestion->suggestable) {
                            $type = class_basename($suggestion->suggestable_type);
                            $slug = $suggestion->suggestable->slug ?? '';
                            if ($type === 'Tool' && Route::has('directory.show')) $route = route('directory.show', $slug);
                            elseif ($type === 'Term' && Route::has('dictionary.show')) $route = route('dictionary.show', $slug);
                            elseif ($type === 'Acronym' && Route::has('acronyms.show')) $route = route('acronyms.show', $slug);
                        }
                        $source = $suggestion->getSourceLabel();
                    @endphp
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: {{ $source['color'] }}15; display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0;">
                        <i class="fa fa-lightbulb-o" style="color: {{ $source['color'] }}; font-size: 18px;"></i>
                    </div>
                    <div style="flex: 1 !important; min-width: 0;">
                        <a href="{{ $route }}" style="font-weight: 600; color: var(--c-dark); text-decoration: none; font-size: 14px;">{{ $suggestion->getItemName() }}</a>
                        <div style="font-size: 12px; color: var(--c-text-muted); margin-top: 2px;">
                            {{ \Modules\Directory\Models\ToolSuggestion::fieldLabels()[$suggestion->field] ?? $suggestion->field }}
                            — <span title="{{ $suggestion->suggested_value }}">{{ Str::limit($suggestion->suggested_value, 60) }}</span>
                        </div>
                    </div>
                    <div style="flex-shrink: 0; text-align: right;">
                        @if($suggestion->status === 'pending')
                            <span class="status-badge" style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #92400e; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;">{{ __('En attente') }}</span>
                        @elseif($suggestion->status === 'approved')
                            <span class="status-badge" style="background: linear-gradient(135deg, #10b981, #059669); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;">{{ __('Approuvée') }}</span>
                        @elseif($suggestion->status === 'rejected')
                            <span class="status-badge" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;">{{ __('Rejetée') }}</span>
                        @endif
                        <div style="font-size: 11px; color: var(--c-text-muted); margin-top: 4px;">{{ $suggestion->created_at->format('d/m/Y') }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Onglet votes --}}
    <div x-show="tab === 'votes'" x-transition x-cloak>
        @if($votes->isEmpty())
            <div style="text-align: center; padding: 60px 20px; color: var(--c-text-muted);">
                <div style="width: 80px; height: 80px; margin: 0 auto 16px; background: linear-gradient(135deg, #F3F4F6, #E5E7EB); border-radius: 20px; display: flex !important; align-items: center !important; justify-content: center !important;">
                    <i class="fa fa-thumbs-up fa-2x" style="color: #D1D5DB;"></i>
                </div>
                <h4 style="font-family: var(--f-heading); color: var(--c-dark); margin: 0 0 8px;">{{ __('Aucun vote') }}</h4>
                <p style="margin: 0 0 16px; font-size: 14px;">{{ __('Vous n\'avez pas encore voté sur la roadmap.') }}</p>
                @if(Route::has('directory.roadmap'))
                    <a href="{{ route('directory.roadmap') }}" class="btn btn-primary btn-sm" style="border-radius: var(--r-btn);">{{ __('Voir la roadmap') }}</a>
                @endif
            </div>
        @else
            <div style="display: flex !important; flex-direction: column !important; gap: 10px;">
                @foreach($votes as $vote)
                @if($vote->idea)
                <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: var(--r-base); padding: 16px 20px; display: flex !important; align-items: center !important; gap: 14px; transition: all 0.2s;"
                     onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'"
                     onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(11,114,133,0.1); display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0;">
                        <i class="fa fa-thumbs-up" style="color: var(--c-primary); font-size: 18px;"></i>
                    </div>
                    <div style="flex: 1 !important; min-width: 0;">
                        <div style="font-weight: 600; color: var(--c-dark); font-size: 14px;">{{ $vote->idea->title }}</div>
                        <div style="font-size: 12px; color: var(--c-text-muted); margin-top: 2px;">{{ __('Voté le') }} {{ $vote->created_at->format('d/m/Y') }}</div>
                    </div>
                    <div style="flex-shrink: 0;">
                        @php $ideaColor = $vote->idea->status->color(); @endphp
                        <span style="background: {{ $ideaColor }}; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;">{{ $vote->idea->status->label() }}</span>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
        @endif
    </div>

    {{-- Onglet ressources --}}
    <div x-show="tab === 'resources'" x-transition x-cloak>
        @if($resources->isEmpty())
            <div style="text-align: center; padding: 60px 20px; color: var(--c-text-muted);">
                <div style="width: 80px; height: 80px; margin: 0 auto 16px; background: linear-gradient(135deg, #F3F4F6, #E5E7EB); border-radius: 20px; display: flex !important; align-items: center !important; justify-content: center !important;">
                    <i class="fa fa-book fa-2x" style="color: #D1D5DB;"></i>
                </div>
                <h4 style="font-family: var(--f-heading); color: var(--c-dark); margin: 0 0 8px;">{{ __('Aucune ressource') }}</h4>
                <p style="margin: 0 0 16px; font-size: 14px;">{{ __('Vous n\'avez pas encore soumis de ressources.') }}</p>
                @if(Route::has('directory.index'))
                    <a href="{{ route('directory.index') }}" class="btn btn-primary btn-sm" style="border-radius: var(--r-btn);">{{ __('Voir le répertoire') }}</a>
                @endif
            </div>
        @else
            <div style="display: flex !important; flex-direction: column !important; gap: 10px;">
                @foreach($resources as $resource)
                <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: var(--r-base); padding: 16px 20px; display: flex !important; align-items: center !important; gap: 14px; transition: all 0.2s;"
                     onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'"
                     onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: rgba(8,145,178,0.1); display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0;">
                        <i class="fa fa-book" style="color: #0891B2; font-size: 18px;"></i>
                    </div>
                    <div style="flex: 1 !important; min-width: 0;">
                        <a href="{{ $resource->url }}" target="_blank" rel="nofollow noopener" style="font-weight: 600; color: var(--c-dark); text-decoration: none; font-size: 14px;">
                            {{ Str::limit($resource->title, 50) }} <i class="fa fa-external-link" style="font-size: 10px; color: var(--c-text-muted);"></i>
                        </a>
                        <div style="font-size: 12px; color: var(--c-text-muted); margin-top: 2px;">
                            {{ $resource->tool->name ?? '—' }}
                            <span style="background: rgba(8,145,178,0.1); color: #0891B2; padding: 1px 8px; border-radius: 8px; font-size: 11px; margin-left: 6px;">{{ $resource->type }}</span>
                        </div>
                    </div>
                    <div style="flex-shrink: 0; text-align: right;">
                        @if($resource->is_approved)
                            <span style="background: linear-gradient(135deg, #10b981, #059669); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;">{{ __('Approuvée') }}</span>
                        @else
                            <span style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #92400e; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600;">{{ __('En attente') }}</span>
                        @endif
                        <div style="font-size: 11px; color: var(--c-text-muted); margin-top: 4px;">{{ $resource->created_at->format('d/m/Y') }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

@endsection
