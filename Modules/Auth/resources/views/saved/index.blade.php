@extends('auth::layouts.user-frontend')

@section('title', __('Mes sauvegardes') . ' - ' . config('app.name'))

@section('user-content')

<h2 style="font-family: var(--f-heading, inherit); font-weight: 700; margin: 0 0 5px;">
    💾 {{ __('Mes sauvegardes') }}
</h2>
<p style="color: var(--c-text-muted); margin: 0 0 25px;">{{ __('Vos prompts et configurations d\'outils sauvegardés.') }}</p>

{{-- Onglets --}}
<div x-data="{ tab: new URLSearchParams(window.location.search).get('tab') || 'prompts' }">

    <div style="display: flex !important; flex-wrap: wrap !important; gap: 8px; margin-bottom: 24px;">
        <button @click="tab = 'prompts'" class="btn"
                :style="tab === 'prompts'
                    ? 'background: #fff; color: var(--c-dark); border: 2px solid var(--c-primary); border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600; box-shadow: 0 4px 12px rgba(11,114,133,0.2); transform: translateY(-1px);'
                    : 'background: rgba(255,255,255,0.7); color: var(--c-text-muted); border: 2px solid transparent; border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600;'">
            ✨ {{ __('Prompts') }}
            @if($savedPrompts->count() > 0)
                <span style="background: linear-gradient(135deg, #8B5CF6, #7C3AED); color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 11px; margin-left: 6px;">{{ $savedPrompts->count() }}</span>
            @endif
        </button>
        <button @click="tab = 'team-configs'" class="btn"
                :style="tab === 'team-configs'
                    ? 'background: #fff; color: var(--c-dark); border: 2px solid var(--c-primary); border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600; box-shadow: 0 4px 12px rgba(11,114,133,0.2); transform: translateY(-1px);'
                    : 'background: rgba(255,255,255,0.7); color: var(--c-text-muted); border: 2px solid transparent; border-radius: 12px; padding: 10px 20px; font-family: var(--f-heading); font-weight: 600;'">
            👥 {{ __('Configurations équipes') }}
            @if($savedTeamPresets->count() > 0)
                <span style="background: linear-gradient(135deg, #0B7285, #0e7490); color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 11px; margin-left: 6px;">{{ $savedTeamPresets->count() }}</span>
            @endif
        </button>
    </div>

    {{-- Onglet prompts --}}
    <div x-show="tab === 'prompts'" x-transition x-cloak>
        @if($savedPrompts->isEmpty())
            <div style="text-align: center; padding: 60px 20px; color: var(--c-text-muted);">
                <div style="font-size: 2.5rem; margin-bottom: 12px;">✨</div>
                <h3 style="font-family: var(--f-heading); margin-bottom: 8px;">{{ __('Aucun prompt sauvegardé') }}</h3>
                <p>{{ __('Utilisez le constructeur de prompts pour créer et sauvegarder vos prompts.') }}</p>
                @if(Route::has('tools.show'))
                    <a href="{{ route('tools.show', 'constructeur-prompts') }}" class="btn btn-primary btn-sm" style="border-radius: var(--r-btn);">{{ __('Créer un prompt') }}</a>
                @endif
            </div>
        @else
            <div style="display: flex !important; flex-direction: column !important; gap: 10px;">
                @foreach($savedPrompts as $sp)
                <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: 10px; padding: 16px 20px;">
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
                                    <a href="#" class="js-copy-prompt" data-prompt-id="{{ $sp->public_id }}" @click="open = false" style="display: flex; align-items: center; gap: 8px; padding: 9px 16px; font-size: 13px; color: var(--c-dark); text-decoration: none;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                                        <i class="fa fa-copy"></i> {{ __('Copier') }}
                                    </a>
                                    @if(Route::has('tools.show'))
                                    <a href="{{ route('tools.show', 'constructeur-prompts') }}?edit={{ $sp->public_id }}" style="display: flex; align-items: center; gap: 8px; padding: 9px 16px; font-size: 13px; color: var(--c-dark); text-decoration: none;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">
                                        <i class="fa fa-pencil"></i> {{ __('Modifier') }}
                                    </a>
                                    @endif
                                    <div style="border-top: 1px solid #f3f4f6; margin: 2px 0;"></div>
                                    <a href="#" @click.prevent="open = false; if(confirm('{{ __('Supprimer ce prompt?') }}')){fetch('/api/prompts/{{ $sp->public_id }}',{method:'DELETE',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Accept':'application/json'}}).then(()=>$el.closest('[style*=border-radius]').parentElement.remove())}" style="display: flex; align-items: center; gap: 8px; padding: 9px 16px; font-size: 13px; color: #ef4444; text-decoration: none;" onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='transparent'">
                                        <i class="fa fa-trash"></i> {{ __('Supprimer') }}
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

    {{-- Onglet configurations équipes --}}
    <div x-show="tab === 'team-configs'" x-transition x-cloak>
        @if($savedTeamPresets->isEmpty())
            <div style="text-align: center; padding: 60px 20px; color: var(--c-text-muted);">
                <div style="font-size: 2.5rem; margin-bottom: 12px;">👥</div>
                <h3 style="font-family: var(--f-heading); margin-bottom: 8px;">{{ __('Aucune configuration sauvegardée') }}</h3>
                <p>{{ __('Créez des configurations dans le générateur d\'équipes pour les retrouver ici.') }}</p>
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
                                {{ $tp->created_at->format('d/m/Y') }} — {{ Str::limit($tp->config_text, 60) }}
                            </p>
                            @php $params = $tp->params ?? []; @endphp
                            <p style="font-size: 13px; margin-bottom: 8px;">
                                {{ ($params['mode'] ?? 'count') === 'count' ? ($params['teamCount'] ?? 2) . ' équipes' : ($params['teamSize'] ?? 3) . ' pers./équipe' }}
                                @if(!empty($params['exclusions'])) — {{ count($params['exclusions']) }} exclusion(s) @endif
                            </p>
                            <div class="d-flex gap-2">
                                <a href="{{ route('tools.show', 'generateur-equipes') }}?edit={{ $tp->public_id }}" class="btn btn-sm btn-outline-primary" style="border-radius: 6px; font-size: 12px;">{{ __('Charger') }}</a>
                                <button class="btn btn-sm btn-outline-danger js-delete-config" data-id="{{ $tp->public_id }}" style="border-radius: 6px; font-size: 12px;">{{ __('Supprimer') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
document.querySelectorAll('.js-copy-prompt').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var id = this.dataset.promptId;
        var el = document.querySelector('.prompt-data-' + id);
        if (el) {
            navigator.clipboard.writeText(JSON.parse(el.textContent));
            this.innerHTML = '<i class="fa fa-check"></i> {{ __("Copié") }}';
            var self = this;
            setTimeout(function() { self.innerHTML = '<i class="fa fa-copy"></i> {{ __("Copier") }}'; }, 2000);
        }
    });
});
document.querySelectorAll('.js-delete-config').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var id = this.dataset.id;
        if (!confirm('{{ __("Supprimer cette configuration?") }}')) return;
        var card = this.closest('.col-sm-6');
        fetch('/api/team-presets/' + id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' } })
            .then(function() { if (card) card.remove(); });
    });
});
</script>
@endpush
@endsection
