<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.app')

@section('title', __('Tableau de bord'))

@section('content')

<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <h1 class="fw-semibold mb-1">{{ __('Bonjour') }}, {{ $user->name }} !</h1>
        <p class="text-muted mb-0">{{ __('Bienvenue dans votre espace personnel.') }}</p>
    </div>
    <span class="badge small fw-semibold px-3 py-2 rounded-1
        {{ $planName === 'Free' ? 'bg-secondary bg-opacity-10 text-secondary' : 'bg-primary bg-opacity-10 text-primary' }}">
        <i data-lucide="{{ $planName === 'Free' ? 'box' : 'crown' }}"></i>
        Plan {{ $planName }}
    </span>
</div>

{{-- Bannière impersonnification --}}
@if(session('impersonating_original_id'))
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
    <i data-lucide="megaphone" class="flex-shrink-0"></i>
    <span><strong>{{ __('Impersonnification en cours') }}</strong> – {{ __('Vous agissez en tant que') }} <strong>{{ auth()->user()->name }}</strong>.</span>
    <form method="POST" action="{{ route('admin.impersonate.stop') }}" class="ms-auto">
        @csrf
        <button type="submit" class="btn btn-sm btn-warning rounded-2">{{ __('Retour admin') }}</button>
    </form>
</div>
@endif

{{-- Bannière vérification e-mail --}}
@if(!auth()->user()->hasVerifiedEmail())
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3" role="alert">
    <i data-lucide="mail" class="flex-shrink-0"></i>
    <span>{{ __('Vérifiez votre adresse courriel pour accéder à toutes les fonctionnalités.') }}</span>
    <a href="{{ route('verification.notice') }}" class="ms-auto btn btn-sm btn-warning rounded-2">{{ __('Vérifier maintenant') }}</a>
</div>
@endif

{{-- Alerte notifications --}}
@if($unreadNotifications > 0)
<div class="alert alert-primary d-flex align-items-center gap-2 mb-3" role="alert">
    <i data-lucide="bell" class="flex-shrink-0"></i>
    {{ __('Vous avez') }} <strong class="mx-1">{{ $unreadNotifications }}</strong> {{ __('notification(s) non lue(s).') }}
</div>
@endif

{{-- Cartes statistiques --}}
<div class="row gy-4 mb-4">
    <div class="col-xxl-3 col-sm-6">
        <div class="card shadow-none border bg-gradient-start-1 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                    <i data-lucide="file-text" class="text-primary"></i>
                </div>
                <div>
                    <p class="text-muted mb-1">{{ __('Articles au total') }}</p>
                    <h4 class="fw-semibold text-primary mb-0">{{ $stats['articles_count'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="card shadow-none border bg-gradient-start-2 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                    <i data-lucide="check-circle" class="text-success"></i>
                </div>
                <div>
                    <p class="text-muted mb-1">{{ __('Publiés') }}</p>
                    <h4 class="fw-semibold text-success mb-0">{{ $stats['published_count'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="card shadow-none border bg-gradient-start-3 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                    <i data-lucide="pen-line" class="text-warning"></i>
                </div>
                <div>
                    <p class="text-muted mb-1">{{ __('Brouillons') }}</p>
                    <h4 class="fw-semibold text-warning mb-0">{{ $stats['draft_count'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="card shadow-none border bg-gradient-start-4 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;background:rgba(124,58,237,0.1);">
                    <i data-lucide="message-circle" style="color:#7c3aed;"></i>
                </div>
                <div>
                    <p class="text-muted mb-1">{{ __('Commentaires reçus') }}</p>
                    <h4 class="fw-semibold mb-0" style="color:#7c3aed;">{{ $stats['comments_count'] }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Actions rapides --}}
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="{{ route('user.articles.create') }}" class="btn btn-primary rounded-2">
        <i data-lucide="plus-circle"></i>
        {{ __('Nouvel article') }}
    </a>
    <a href="{{ route('blog.index') }}" class="btn btn-outline-primary rounded-2">
        <i data-lucide="globe"></i>
        {{ __('Voir le blog') }}
    </a>
    <a href="{{ route('user.profile') }}" class="btn btn-outline-secondary rounded-2">
        <i data-lucide="user-circle"></i>
        {{ __('Mon profil') }}
    </a>
    @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary rounded-2">
        <i data-lucide="shield"></i>
        {{ __('Administration') }}
    </a>
    @endif
</div>

{{-- Articles récents --}}
<div class="card h-100 p-0 rounded-3">
    <div class="card-header border-bottom bg-body py-3 px-4 d-flex align-items-center flex-wrap gap-3 justify-content-between">
        <h5 class="card-title fw-semibold mb-0">{{ __('Mes articles récents') }}</h5>
        <a href="{{ route('user.articles.create') }}" class="btn btn-primary btn-sm px-2 py-2 rounded-2 d-flex align-items-center gap-2">
            <i data-lucide="plus-circle" class="lh-1"></i> {{ __('Nouvel article') }}
        </a>
    </div>
    <div class="card-body p-0">
        @if($recentArticles->isEmpty())
            <div class="py-5 text-center text-muted">
                <i data-lucide="file-text" class="mb-2 d-block"></i>
                <p class="mb-2">{{ __('Aucun article pour le moment.') }}</p>
                <a href="{{ route('user.articles.create') }}" class="btn btn-primary rounded-2">
                    {{ __('Rédiger mon premier article') }}
                </a>
            </div>
        @else
            <div class="table-responsive scroll-sm">
                <table class="table bordered-table sm-table mb-0">
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
                            <td class="fw-medium">{{ $article->title }}</td>
                            <td>
                                @if($article->status === 'published')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success fw-medium">{{ __('Publié') }}</span>
                                @elseif($article->status === 'archived')
                                    <span class="badge bg-secondary bg-opacity-25 text-secondary border border-secondary fw-medium">{{ __('Archivé') }}</span>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning fw-medium">{{ __('Brouillon') }}</span>
                                @endif
                            </td>
                            <td class="text-muted">{{ $article->created_at->format('d M Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('user.articles.edit', $article) }}"
                                   class="bg-success bg-opacity-10 text-success fw-medium d-flex justify-content-center align-items-center rounded-circle" title="Modifier" style="width:40px;height:40px;">
                                    <i data-lucide="edit"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
