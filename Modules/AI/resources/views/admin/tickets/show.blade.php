<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('backoffice::themes.backend.layouts.admin')

@section('title', __('Ticket') . ' #' . $ticket->id)

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Tableau de bord') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.ai.tickets.index') }}">{{ __('Tickets') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Ticket') }} #{{ $ticket->id }}</li>
    </ol>
</nav>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
</div>
@endif

@php
    $statusColors = [
        'open' => 'primary',
        'in_progress' => 'info',
        'waiting_customer' => 'warning',
        'resolved' => 'success',
        'closed' => 'secondary',
    ];
    $priorityColors = [
        'low' => 'light',
        'medium' => 'info',
        'high' => 'warning',
        'urgent' => 'danger',
    ];
@endphp

<div class="row">
    {{-- LEFT: Ticket info + Replies --}}
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">{{ $ticket->title }}</h5>
                <div>
                    <span class="badge bg-{{ $statusColors[$ticket->status->value] ?? 'secondary' }} {{ in_array($ticket->status->value, ['waiting_customer']) ? 'text-dark' : '' }}">
                        {{ __($ticket->status->value) }}
                    </span>
                    <span class="badge bg-{{ $priorityColors[$ticket->priority->value] ?? 'light' }} {{ in_array($ticket->priority->value, ['low', 'high']) ? 'text-dark' : '' }}">
                        {{ __($ticket->priority->value) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-3">
                    <div><strong>{{ __('Utilisateur') }} :</strong> {{ $ticket->user->name ?? '-' }}</div>
                    <div><strong>{{ __('Agent') }} :</strong> {{ $ticket->agent->name ?? __('Non assigné') }}</div>
                    <div><strong>{{ __('Créé le') }} :</strong> {{ $ticket->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div class="mb-3">
                    <strong>{{ __('Description') }} :</strong>
                    <p class="mt-2" style="white-space:pre-wrap;">{{ $ticket->description }}</p>
                </div>
                @if($ticket->tags->isNotEmpty())
                <div>
                    <strong>{{ __('Tags') }} :</strong>
                    @foreach($ticket->tags as $tag)
                    <span class="badge bg-light text-dark border me-1">{{ $tag->name }}</span>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Replies thread --}}
        <div class="card mb-3">
            <div class="card-header"><strong>{{ __('Réponses') }}</strong></div>
            <div class="card-body p-0">
                <div id="repliesContainer" class="p-3" style="max-height:500px;overflow-y:auto;">
                    @forelse($ticket->replies->sortBy('created_at') as $reply)
                    <div class="border-start border-{{ $reply->is_internal ? 'warning' : 'transparent' }} border-3 ps-3 mb-3">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $reply->user->name ?? '-' }}</strong>
                            <small class="text-muted">{{ $reply->created_at->format('d/m/Y H:i') }}</small>
                        </div>
                        @if($reply->is_internal)
                        <span class="badge bg-warning text-dark mb-1">{{ __('Note interne') }}</span>
                        @endif
                        <p class="mb-0" style="white-space:pre-wrap;">{{ $reply->content }}</p>
                    </div>
                    @empty
                    <p class="text-muted text-center py-3 mb-0">{{ __('Aucune réponse.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Reply form --}}
        <div class="card">
            <div class="card-header"><strong>{{ __('Répondre') }}</strong></div>
            <div class="card-body">
                <form action="{{ route('admin.ai.tickets.reply', $ticket) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <textarea class="form-control" name="content" rows="4" required placeholder="{{ __('Votre réponse...') }}" maxlength="5000"></textarea>
                    </div>
                    {{-- AI Assist buttons --}}
                    <div class="mb-3">
                        <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSuggest">
                                <i data-lucide="sparkles" style="width:14px;height:14px;"></i> {{ __('Suggérer des réponses') }}
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnSentiment">
                                <i data-lucide="heart-pulse" style="width:14px;height:14px;"></i> {{ __('Analyser le sentiment') }}
                            </button>
                            <span id="aiSentiment"></span>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i data-lucide="wand-2" style="width:14px;height:14px;"></i> {{ __('Améliorer') }}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item ai-rewrite" href="#" data-style="professional">{{ __('Professionnel') }}</a></li>
                                    <li><a class="dropdown-item ai-rewrite" href="#" data-style="empathetic">{{ __('Empathique') }}</a></li>
                                    <li><a class="dropdown-item ai-rewrite" href="#" data-style="concise">{{ __('Concis') }}</a></li>
                                </ul>
                            </div>
                        </div>
                        <div id="aiSuggestions" class="d-flex flex-wrap gap-2"></div>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_internal" name="is_internal" value="1">
                        <label class="form-check-label" for="is_internal">{{ __('Note interne (visible uniquement par les agents)') }}</label>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="send" style="width:14px;height:14px;"></i> {{ __('Envoyer') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- RIGHT: Status update, SLA, actions --}}
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><strong>{{ __('Mettre à jour') }}</strong></div>
            <div class="card-body">
                <form action="{{ route('admin.ai.tickets.update', $ticket) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">{{ __('Statut') }}</label>
                        <select class="form-select" name="status">
                            @foreach(['open', 'in_progress', 'waiting_customer', 'resolved', 'closed'] as $s)
                            <option value="{{ $s }}" {{ $ticket->status->value === $s ? 'selected' : '' }}>{{ __($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Priorité') }}</label>
                        <select class="form-select" name="priority">
                            @foreach(['low', 'medium', 'high', 'urgent'] as $p)
                            <option value="{{ $p }}" {{ $ticket->priority->value === $p ? 'selected' : '' }}>{{ __($p) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i data-lucide="save" style="width:14px;height:14px;"></i> {{ __('Mettre à jour') }}
                    </button>
                </form>

                <hr class="my-3">

                <div class="d-grid gap-2">
                    <form action="{{ route('admin.ai.tickets.resolve', $ticket) }}" method="POST" class="d-grid">
                        @csrf
                        <button type="submit" class="btn btn-success">
                            <i data-lucide="check-circle" style="width:14px;height:14px;"></i> {{ __('Marquer comme résolu') }}
                        </button>
                    </form>
                    <form action="{{ route('admin.ai.tickets.close', $ticket) }}" method="POST" class="d-grid">
                        @csrf
                        <button type="submit" class="btn btn-secondary">
                            <i data-lucide="x-circle" style="width:14px;height:14px;"></i> {{ __('Fermer le ticket') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @if($ticket->slaPolicy)
        <div class="card mb-3">
            <div class="card-header"><strong>{{ __('SLA') }} : {{ $ticket->slaPolicy->name }}</strong></div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>{{ __('Première réponse') }}</dt>
                    <dd>{{ $ticket->slaPolicy->first_response_hours }}h</dd>
                    <dt>{{ __('Résolution') }}</dt>
                    <dd>{{ $ticket->slaPolicy->resolution_hours }}h</dd>
                    @if($ticket->due_at)
                    <dt>{{ __('Échéance') }}</dt>
                    <dd>{{ $ticket->due_at->format('d/m/Y H:i') }}</dd>
                    @endif
                    @if($ticket->first_response_at)
                    <dt>{{ __('Première réponse le') }}</dt>
                    <dd>{{ $ticket->first_response_at->format('d/m/Y H:i') }}</dd>
                    @endif
                    @if($ticket->resolved_at)
                    <dt>{{ __('Résolu le') }}</dt>
                    <dd>{{ $ticket->resolved_at->format('d/m/Y H:i') }}</dd>
                    @endif
                </dl>
            </div>
        </div>
        @endif

        @if($ticket->category)
        <div class="card mb-3">
            <div class="card-header"><strong>{{ __('Catégorie') }}</strong></div>
            <div class="card-body">{{ $ticket->category }}</div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var container = document.getElementById('repliesContainer');
    if (container) container.scrollTop = container.scrollHeight;

    var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var textarea = document.querySelector('textarea[name="content"]');
    var sentimentColors = {positive:'success', neutral:'warning', negative:'danger', urgent:'danger'};

    function aiPost(url, body, btn, cb) {
        var orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        fetch(url, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'}, body:JSON.stringify(body)})
            .then(function(r){return r.json()})
            .then(cb)
            .catch(function(e){console.error(e)})
            .finally(function(){btn.innerHTML=orig;btn.disabled=false;if(typeof lucide!=='undefined')lucide.createIcons()});
    }

    document.getElementById('btnSuggest')?.addEventListener('click', function(){
        aiPost('{{ route("admin.ai.ai-assist.suggest", $ticket) }}', {}, this, function(data){
            var box = document.getElementById('aiSuggestions');
            box.innerHTML = '';
            (data.suggestions||[]).forEach(function(s){
                var b = document.createElement('button');
                b.type='button'; b.className='btn btn-sm btn-outline-primary';
                b.textContent = s.length > 80 ? s.substring(0,80)+'…' : s;
                b.title = s;
                b.addEventListener('click', function(){textarea.value=s});
                box.appendChild(b);
            });
        });
    });

    document.getElementById('btnSentiment')?.addEventListener('click', function(){
        if(!textarea.value.trim()) return;
        aiPost('{{ route("admin.ai.ai-assist.sentiment") }}', {text:textarea.value}, this, function(data){
            document.getElementById('aiSentiment').innerHTML =
                '<span class="badge bg-'+(sentimentColors[data.sentiment]||'secondary')+'">'+data.sentiment+'</span>' +
                (data.summary ? ' <small class="text-muted">'+data.summary+'</small>' : '');
        });
    });

    document.querySelectorAll('.ai-rewrite').forEach(function(el){
        el.addEventListener('click', function(e){
            e.preventDefault();
            if(!textarea.value.trim()) return;
            var toggle = this.closest('.dropdown').querySelector('.dropdown-toggle');
            aiPost('{{ route("admin.ai.ai-assist.rewrite") }}', {content:textarea.value, style:this.dataset.style}, toggle, function(data){
                if(data.content) textarea.value = data.content;
            });
        });
    });
});
</script>
@endpush
@endsection
