<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.app')

@section('title', __('Tableau de bord'))

@section('content')

<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <h1 class="fw-semibold fs-5 text-body mb-1">{{ __('Bonjour') }}, {{ $user->name }} !</h1>
        <p class="text-muted mb-0">{{ __('Bienvenue dans votre espace personnel.') }}</p>
    </div>
    <span class="d-inline-flex align-items-center gap-1 px-3 py-2 rounded fw-semibold small flex-shrink-0
        {{ $planName === 'Free' ? 'bg-secondary bg-opacity-10 text-muted' : 'bg-primary bg-opacity-10 text-primary' }}">
        <i data-lucide="{{ $planName === 'Free' ? 'package' : 'crown' }}" class="icon-sm"></i>
        Plan {{ $planName }}
    </span>
</div>

@if(session('impersonating_original_id'))
<div class="card mb-4 border-start border-4 border-warning" role="alert">
    <div class="p-4 d-flex align-items-center gap-2">
        <i data-lucide="user-x" class="icon-sm text-warning flex-shrink-0"></i>
        <span class="small"><strong>{{ __('Impersonnification en cours') }}</strong> — {{ __('Vous agissez en tant que') }} <strong>{{ auth()->user()->name }}</strong>.</span>
        <form method="POST" action="{{ route('admin.impersonate.stop') }}" class="ms-auto">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm">{{ __('Retour admin') }}</button>
        </form>
    </div>
</div>
@endif

@if(!auth()->user()->hasVerifiedEmail())
<div class="card mb-4 border-start border-4 border-warning" role="alert">
    <div class="p-4 d-flex align-items-center gap-2">
        <i data-lucide="mail" class="icon-sm text-warning flex-shrink-0"></i>
        <span class="small">{{ __('Vérifiez votre adresse courriel pour accéder à toutes les fonctionnalités.') }}</span>
        <a href="{{ route('verification.notice') }}" class="ms-auto btn btn-warning btn-sm">{{ __('Vérifier maintenant') }}</a>
    </div>
</div>
@endif

@if($unreadNotifications > 0)
<div class="card mb-4 border-start border-4 border-primary" role="alert">
    <div class="p-4 d-flex align-items-center gap-2">
        <i data-lucide="bell" class="icon-sm text-primary flex-shrink-0"></i>
        <span class="small">{{ __('Vous avez') }} <strong>{{ $unreadNotifications }}</strong> {{ __('notification(s) non lue(s).') }}</span>
    </div>
</div>
@endif

{{-- Cartes statistiques --}}
<div class="row mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="p-4">
                <div class="d-flex align-items-center gap-4">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width:3rem;height:3rem;">
                        <i data-lucide="file-text" class="icon-sm text-white"></i>
                    </div>
                    <div>
                        <span class="small text-muted d-block mb-1">{{ __('Articles au total') }}</span>
                        <h4 class="fw-bold fs-4 mb-0">{{ $stats['articles_count'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="p-4">
                <div class="d-flex align-items-center gap-4">
                    <div class="rounded-circle bg-success d-flex align-items-center justify-content-center flex-shrink-0" style="width:3rem;height:3rem;">
                        <i data-lucide="check-circle" class="icon-sm text-white"></i>
                    </div>
                    <div>
                        <span class="small text-muted d-block mb-1">{{ __('Publiés') }}</span>
                        <h4 class="fw-bold fs-4 mb-0">{{ $stats['published_count'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="p-4">
                <div class="d-flex align-items-center gap-4">
                    <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width:3rem;height:3rem;">
                        <i data-lucide="pencil" class="icon-sm text-white"></i>
                    </div>
                    <div>
                        <span class="small text-muted d-block mb-1">{{ __('Brouillons') }}</span>
                        <h4 class="fw-bold fs-4 mb-0">{{ $stats['draft_count'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="p-4">
                <div class="d-flex align-items-center gap-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:3rem;height:3rem;background-color:#7c3aed;">
                        <i data-lucide="message-circle" class="icon-sm text-white"></i>
                    </div>
                    <div>
                        <span class="small text-muted d-block mb-1">{{ __('Commentaires reçus') }}</span>
                        <h4 class="fw-bold fs-4 mb-0">{{ $stats['comments_count'] }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Actions rapides --}}
<div class="d-flex flex-wrap gap-3 mb-4">
    <a href="{{ route('user.articles.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
        <i data-lucide="plus-circle" class="icon-sm"></i>
        {{ __('Nouvel article') }}
    </a>
    <a href="{{ route('blog.index') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-2">
        <i data-lucide="globe" class="icon-sm"></i>
        {{ __('Voir le blog') }}
    </a>
    <a href="{{ route('user.profile') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2">
        <i data-lucide="user" class="icon-sm"></i>
        {{ __('Mon profil') }}
    </a>
    @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
    <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-2">
        <i data-lucide="shield" class="icon-sm"></i>
        {{ __('Administration') }}
    </a>
    @endif
</div>

{{-- Articles récents --}}
<div class="card">
    <div class="p-4 border-bottom d-flex align-items-center flex-wrap gap-3 justify-content-between">
        <h5 class="fw-semibold mb-0">{{ __('Mes articles récents') }}</h5>
        <a href="{{ route('user.articles.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
            <i data-lucide="plus-circle" class="icon-sm"></i> {{ __('Nouvel article') }}
        </a>
    </div>
    <div class="p-0 overflow-x-auto">
        @if($recentArticles->isEmpty())
            <div class="py-5 text-center text-muted">
                <i data-lucide="file-text" class="icon-sm mb-3 d-block" style="font-size:2rem;"></i>
                <p class="mb-3">{{ __('Aucun article pour le moment.') }}</p>
                <a href="{{ route('user.articles.create') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-2">
                    {{ __('Rédiger mon premier article') }}
                </a>
            </div>
        @else
            <table class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>{{ __('Titre') }}</th>
                        <th>{{ __('Statut') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th class="text-end">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentArticles as $article)
                    <tr>
                        <td class="small fw-semibold text-body">{{ $article->title }}</td>
                        <td>
                            @if($article->status === 'published')
                                <span class="badge bg-success">{{ __('Publié') }}</span>
                            @elseif($article->status === 'archived')
                                <span class="badge bg-secondary">{{ __('Archivé') }}</span>
                            @else
                                <span class="badge bg-warning text-dark">{{ __('Brouillon') }}</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $article->created_at->format('d M Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('user.articles.edit', $article) }}"
                               class="btn btn-sm btn-outline-success rounded-circle" title="{{ __('Modifier') }}">
                                <i data-lucide="pencil" class="icon-sm"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@endsection
