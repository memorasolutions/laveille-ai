<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.user-frontend')

@section('title', __('Mes contributions') . ' - ' . config('app.name'))

@section('user-content')

<h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">
    🤝 {{ __('Mes contributions') }}
</h2>
<p style="color: var(--c-text-muted); margin: 0 0 25px;">{{ __('Suivez vos suggestions et votes sur le site.') }}</p>

{{-- Cartes statistiques --}}
<div class="row" style="margin-bottom: 20px;">
    <div class="col-sm-3" style="margin-bottom: 15px;">
        <div class="user-stat-card">
            <div>
                <span style="font-size: 1.5rem;">💡</span>
                <h3 style="margin: 5px 0 0;">{{ $suggestions->count() }}</h3>
                <small>{{ __('Suggestions') }}</small>
            </div>
        </div>
    </div>
    <div class="col-sm-3" style="margin-bottom: 15px;">
        <div class="user-stat-card">
            <div>
                <span style="font-size: 1.5rem;">👍</span>
                <h3 style="margin: 5px 0 0;">{{ $votes->count() }}</h3>
                <small>{{ __('Votes roadmap') }}</small>
            </div>
        </div>
    </div>
    <div class="col-sm-3" style="margin-bottom: 15px;">
        <div class="user-stat-card">
            <div>
                <span style="font-size: 1.5rem;">📚</span>
                <h3 style="margin: 5px 0 0;">{{ $resources->count() }}</h3>
                <small>{{ __('Ressources') }}</small>
            </div>
        </div>
    </div>
    <div class="col-sm-3" style="margin-bottom: 15px;">
        <div class="user-stat-card">
            <div>
                <span style="font-size: 1.5rem;">✨</span>
                <h3 style="margin: 5px 0 0;">{{ $savedPrompts->count() }}</h3>
                <small>{{ __('Prompts') }}</small>
            </div>
        </div>
    </div>
    <div class="col-sm-3" style="margin-bottom: 15px;">
        <div class="user-stat-card">
            <div>
                <span style="font-size: 1.5rem;">👥</span>
                <h3 style="margin: 5px 0 0;">{{ $savedTeamPresets->count() }}</h3>
                <small>{{ __('Presets équipes') }}</small>
            </div>
        </div>
    </div>
</div>

{{-- Onglets modernes Alpine.js --}}
<div x-data="{ tab: new URLSearchParams(window.location.search).get('tab') || 'suggestions' }">

    {{-- Navigation onglets avec badges --}}
    <div style="display: flex !important; flex-wrap: wrap !important; gap: 8px; margin-bottom: 24px;">
        <button @click="tab = 'suggestions'" class="btn"
                :style="tab === 'suggestions'
                    ? 'background: #fff; color: var(--c-dark); border: 2px solid var(--c-primary); border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600; box-shadow: 0 4px 12px rgba(11,114,133,0.2); transform: translateY(-1px);'
                    : 'background: rgba(255,255,255,0.7); color: var(--c-text-muted); border: 2px solid transparent; border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600;'">
            💡 {{ __('Suggestions') }}
            @if($suggestions->count() > 0)
                <span style="background: linear-gradient(135deg, #f0ad4e, #e09b3d); color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 11px; margin-left: 6px;">{{ $suggestions->count() }}</span>
            @endif
        </button>
        <button @click="tab = 'votes'" class="btn"
                :style="tab === 'votes'
                    ? 'background: #fff; color: var(--c-dark); border: 2px solid var(--c-primary); border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600; box-shadow: 0 4px 12px rgba(11,114,133,0.2); transform: translateY(-1px);'
                    : 'background: rgba(255,255,255,0.7); color: var(--c-text-muted); border: 2px solid transparent; border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600;'">
            👍 {{ __('Votes') }}
            @if($votes->count() > 0)
                <span style="background: linear-gradient(135deg, var(--c-primary), var(--c-primary-hover)); color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 11px; margin-left: 6px;">{{ $votes->count() }}</span>
            @endif
        </button>
        <button @click="tab = 'resources'" class="btn"
                :style="tab === 'resources'
                    ? 'background: #fff; color: var(--c-dark); border: 2px solid var(--c-primary); border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600; box-shadow: 0 4px 12px rgba(11,114,133,0.2); transform: translateY(-1px);'
                    : 'background: rgba(255,255,255,0.7); color: var(--c-text-muted); border: 2px solid transparent; border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600;'">
            📚 {{ __('Ressources') }}
            @if($resources->count() > 0)
                <span style="background: linear-gradient(135deg, #0891B2, #0e7490); color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 11px; margin-left: 6px;">{{ $resources->count() }}</span>
            @endif
        </button>
        <button @click="tab = 'prompts'" class="btn"
                :style="tab === 'prompts'
                    ? 'background: #fff; color: var(--c-dark); border: 2px solid var(--c-primary); border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600; box-shadow: 0 4px 12px rgba(11,114,133,0.2); transform: translateY(-1px);'
                    : 'background: rgba(255,255,255,0.7); color: var(--c-text-muted); border: 2px solid transparent; border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600;'">
            ✨ {{ __('Prompts') }}
            @if($savedPrompts->count() > 0)
                <span style="background: linear-gradient(135deg, #8B5CF6, #7C3AED); color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 11px; margin-left: 6px;">{{ $savedPrompts->count() }}</span>
            @endif
        </button>
        <button @click="tab = 'team-presets'" class="btn"
                :style="tab === 'team-presets'
                    ? 'background: #fff; color: var(--c-dark); border: 2px solid var(--c-primary); border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600; box-shadow: 0 4px 12px rgba(11,114,133,0.2); transform: translateY(-1px);'
                    : 'background: rgba(255,255,255,0.7); color: var(--c-text-muted); border: 2px solid transparent; border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600;'">
            👥 {{ __('Presets équipes') }}
            @if($savedTeamPresets->count() > 0)
                <span style="background: linear-gradient(135deg, #0B7285, #0e7490); color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 11px; margin-left: 6px;">{{ $savedTeamPresets->count() }}</span>
            @endif
        </button>
    </div>

    {{-- Onglet suggestions --}}
    <div x-show="tab === 'suggestions'" x-transition>
        @if($suggestions->isEmpty())
            <div style="text-align: center; padding: 60px 20px; color: var(--c-text-muted);">
                <div style="width: 80px; height: 80px; margin: 0 auto 16px; background: linear-gradient(135deg, #F3F4F6, #E5E7EB); border-radius: 20px; display: flex !important; align-items: center !important; justify-content: center !important;">
                    <span style="font-size: 2rem;">💡</span>
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
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: {{ $source['color'] }}; display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0;">
                        <span style="font-size: 18px;">💡</span>
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
                    <span style="font-size: 2rem;">👍</span>
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
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: var(--c-primary); display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0;">
                        <span style="font-size: 18px;">👍</span>
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
                    <span style="font-size: 2rem;">📚</span>
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
                    @if($resource->thumbnail)
                        <img src="{{ $resource->thumbnail }}" alt="" style="width: 64px; height: 42px; border-radius: 8px; object-fit: cover; flex-shrink: 0;">
                    @else
                        @php
                            $resEmoji = match(strtolower($resource->type ?? '')) {
                                'video', 'vidéo' => '🎬',
                                'article', 'blog' => '📄',
                                'tutorial', 'tutoriel' => '📖',
                                default => '🔗',
                            };
                        @endphp
                        <div style="width: 42px; height: 42px; border-radius: 10px; background: var(--c-primary); display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0;">
                            <span style="font-size: 18px;">{{ $resEmoji }}</span>
                        </div>
                    @endif
                    <div style="flex: 1 !important; min-width: 0;">
                        @php
                            $toolLink = ($resource->tool && Route::has('directory.show'))
                                ? route('directory.show', $resource->tool->slug) . '#resources'
                                : '#';
                        @endphp
                        <a href="{{ $toolLink }}" style="font-weight: 600; color: var(--c-dark); text-decoration: none; font-size: 14px;">
                            {{ Str::limit($resource->title, 50) }}
                        </a>
                        <div style="font-size: 12px; color: var(--c-text-muted); margin-top: 2px;">
                            {{ $resource->tool->name ?? '—' }}
                            <span style="background: rgba(8,145,178,0.1); color: #0891B2; padding: 1px 8px; border-radius: 8px; font-size: 11px; margin-left: 6px;">{{ $resource->type }}</span>
                            <a href="{{ $resource->url }}" target="_blank" rel="nofollow noopener" style="color: var(--c-text-muted); margin-left: 6px; font-size: 11px;" title="{{ __('Voir la source') }}">↗</a>
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

    {{-- Onglet prompts sauvegardés --}}
    <div x-show="tab === 'prompts'" x-transition x-cloak>
        @if($savedPrompts->isEmpty())
            <div style="text-align: center; padding: 60px 20px; color: var(--c-text-muted);">
                <div style="width: 80px; height: 80px; margin: 0 auto 16px; background: linear-gradient(135deg, #F3F4F6, #E5E7EB); border-radius: 20px; display: flex !important; align-items: center !important; justify-content: center !important;">
                    <span style="font-size: 2rem;">✨</span>
                </div>
                <h4 style="font-family: var(--f-heading); color: var(--c-dark); margin: 0 0 8px;">{{ __('Aucun prompt sauvegarde') }}</h4>
                <p style="margin: 0 0 16px; font-size: 14px;">{{ __('Utilisez le constructeur de prompts pour creer et sauvegarder vos prompts.') }}</p>
                @if(Route::has('tools.show'))
                    <a href="{{ route('tools.show', 'constructeur-prompts') }}" class="btn btn-primary btn-sm" style="border-radius: var(--r-btn);">{{ __('Creer un prompt') }}</a>
                @endif
            </div>
        @else
            <div style="display: flex !important; flex-direction: column !important; gap: 10px;">
                @foreach($savedPrompts as $sp)
                <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: var(--r-base); padding: 16px 20px; transition: all 0.2s;"
                     onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 12px rgba(0,0,0,0.08)'"
                     onmouseout="this.style.transform='none';this.style.boxShadow='none'">
                    <div style="display: flex !important; align-items: center !important; gap: 14px;">
                        <div style="width: 42px; height: 42px; border-radius: 10px; background: linear-gradient(135deg, #8B5CF6, #7C3AED); display: flex !important; align-items: center !important; justify-content: center !important; flex-shrink: 0;">
                            <span style="font-size: 18px; color: #fff;">✨</span>
                        </div>
                        <div style="flex: 1 !important; min-width: 0; overflow: hidden;">
                            <strong style="font-size: 14px; color: var(--c-dark); display: block;">{{ $sp->name }}</strong>
                            <div style="font-size: 12px; color: var(--c-text-muted); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ Str::limit($sp->prompt_text, 120) }}</div>
                        </div>
                        <div style="flex-shrink: 0; display: flex !important; align-items: center !important; gap: 10px;">
                            <div style="font-size: 11px; color: var(--c-text-muted);">{{ $sp->created_at->format('d/m/Y') }}</div>
                            <div x-data="{ open: false }" style="position: relative;" @click.outside="open = false">
                                <button @click="open = !open" style="background: transparent; border: 1px solid #e5e7eb; border-radius: 8px; padding: 4px 10px; line-height: 1; font-size: 18px; color: #6b7280; cursor: pointer;">&#8942;</button>
                                <div x-show="open" x-cloak x-transition.opacity style="position: absolute; right: 0; top: 100%; margin-top: 4px; min-width: 170px; background: #fff; border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.12); border: 1px solid #e5e7eb; padding: 4px 0; z-index: 50;">
                                    <a href="#" class="copy-prompt-btn" data-prompt-id="{{ $sp->public_id }}" @click="open = false" style="display: flex; align-items: center; gap: 8px; padding: 9px 16px; font-size: 13px; color: var(--c-dark, #1a1a2e); text-decoration: none;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>{{ __('Copier') }}
                                    </a>
                                    @if(Route::has('tools.show'))
                                    <a href="{{ route('tools.show', 'constructeur-prompts') }}?edit={{ $sp->public_id }}" style="display: flex; align-items: center; gap: 8px; padding: 9px 16px; font-size: 13px; color: var(--c-dark, #1a1a2e); text-decoration: none;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>{{ __('Editer') }}
                                    </a>
                                    @endif
                                    <div style="border-top: 1px solid #f3f4f6; margin: 2px 0;"></div>
                                    <a href="#" @click.prevent="open = false; if(confirm('{{ __('Supprimer ce prompt ?') }}')){fetch('/api/prompts/{{ $sp->public_id }}',{method:'DELETE',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}}).then(()=>$el.closest('[style*=border-radius]').parentElement.remove())}" style="display: flex; align-items: center; gap: 8px; padding: 9px 16px; font-size: 13px; color: #ef4444; text-decoration: none;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>{{ __('Supprimer') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        <script type="application/json" class="prompt-data-{{ $sp->public_id }}">@json($sp->prompt_text)</script>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
document.querySelectorAll('.copy-prompt-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.dataset.promptId;
        var el = document.querySelector('.prompt-data-' + id);
        if (el) {
            var text = JSON.parse(el.textContent);
            navigator.clipboard.writeText(text);
            this.textContent = '{{ __("Copie !") }}';
            var self = this;
            setTimeout(function() { self.textContent = '{{ __("Copier") }}'; }, 2000);
        }
    });
});
</script>
@endpush
    {{-- Onglet presets équipes --}}
    <div x-show="tab === 'team-presets'" x-transition x-cloak>
        @if($savedTeamPresets->isEmpty())
            <div style="text-align: center; padding: 40px 20px; color: var(--c-text-muted);">
                <div style="font-size: 3rem; margin-bottom: 12px;">👥</div>
                <h3 style="font-family: var(--f-heading); margin-bottom: 8px;">{{ __('Aucun preset sauvegardé') }}</h3>
                <p>{{ __('Créez des presets dans le générateur d\'équipes pour les retrouver ici.') }}</p>
                @if(Route::has('tools.show'))
                    <a href="{{ route('tools.show', 'generateur-equipes') }}" style="display: inline-block; background: var(--c-primary); color: #fff; padding: 10px 24px; border-radius: var(--r-btn); font-weight: 600; text-decoration: none; margin-top: 12px;">{{ __('Aller au générateur') }}</a>
                @endif
            </div>
        @else
            <div class="row">
                @foreach($savedTeamPresets as $tp)
                <div class="col-sm-6 col-md-4" style="margin-bottom: 16px;">
                    <div class="panel panel-default" style="border-radius: 10px; overflow: hidden; margin-bottom: 0;">
                        <div class="panel-heading" style="background: var(--c-primary); color: #fff; padding: 10px 14px;">
                            <strong>{{ $tp->name }}</strong>
                        </div>
                        <div class="panel-body" style="padding: 12px 14px;">
                            <p class="text-muted" style="font-size: 12px; margin-bottom: 6px;">
                                {{ $tp->created_at->format('d/m/Y') }}
                                — {{ Str::limit($tp->config_text, 60) }}
                            </p>
                            @php $params = $tp->params ?? []; @endphp
                            <p style="font-size: 13px; margin-bottom: 8px;">
                                {{ ($params['mode'] ?? 'count') === 'count' ? ($params['teamCount'] ?? 2) . ' équipes' : ($params['teamSize'] ?? 3) . ' pers./équipe' }}
                                @if(!empty($params['exclusions'])) — {{ count($params['exclusions']) }} exclusion(s) @endif
                            </p>
                            <div class="d-flex gap-2">
                                <a href="{{ route('tools.show', 'generateur-equipes') }}?edit={{ $tp->public_id }}" class="btn btn-sm btn-outline-primary" style="border-radius: 6px; font-size: 12px;">{{ __('Charger') }}</a>
                                <button class="btn btn-sm btn-outline-danger js-delete-preset" data-id="{{ $tp->public_id }}" style="border-radius: 6px; font-size: 12px;">{{ __('Supprimer') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

@push('scripts')
<script>
document.querySelectorAll('.js-delete-preset').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.dataset.id;
        if (!confirm('{{ __("Supprimer ce preset?") }}')) return;
        var card = this.closest('.col-sm-6');
        fetch('/api/team-presets/' + id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' } })
            .then(function() { if (card) card.remove(); });
    });
});
</script>
@endpush
@endsection
