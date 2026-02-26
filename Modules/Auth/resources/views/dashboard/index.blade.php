@extends('auth::layouts.app')

@section('title', __('Tableau de bord'))

@section('content')

<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-24">
    <div>
        <h1 class="fw-semibold mb-4">{{ __('Bonjour') }}, {{ $user->name }} !</h1>
        <p class="text-secondary-light mb-0">{{ __('Bienvenue dans votre espace personnel.') }}</p>
    </div>
    <span class="badge text-sm fw-semibold px-16 py-9 radius-4
        {{ $planName === 'Free' ? 'bg-neutral-focus text-neutral-main' : 'bg-primary-100 text-primary-600' }}">
        <iconify-icon icon="{{ $planName === 'Free' ? 'solar:box-outline' : 'solar:crown-outline' }}" style="font-size:14px;"></iconify-icon>
        Plan {{ $planName }}
    </span>
</div>

{{-- Bannière impersonnification --}}
@if(session('impersonating_original_id'))
<div class="alert alert-warning d-flex align-items-center gap-2 mb-20" role="alert">
    <iconify-icon icon="solar:user-speak-outline" class="text-lg flex-shrink-0"></iconify-icon>
    <span><strong>{{ __('Impersonnification en cours') }}</strong> — {{ __('Vous agissez en tant que') }} <strong>{{ auth()->user()->name }}</strong>.</span>
    <form method="POST" action="{{ route('admin.impersonate.stop') }}" class="ms-auto">
        @csrf
        <button type="submit" class="btn btn-sm btn-warning radius-8">{{ __('Retour admin') }}</button>
    </form>
</div>
@endif

{{-- Bannière vérification e-mail --}}
@if(!auth()->user()->hasVerifiedEmail())
<div class="alert alert-warning d-flex align-items-center gap-2 mb-20" role="alert">
    <iconify-icon icon="solar:letter-outline" class="text-lg flex-shrink-0"></iconify-icon>
    <span>{{ __('Vérifiez votre adresse courriel pour accéder à toutes les fonctionnalités.') }}</span>
    <a href="{{ route('verification.notice') }}" class="ms-auto btn btn-sm btn-warning radius-8">{{ __('Vérifier maintenant') }}</a>
</div>
@endif

{{-- Alerte notifications --}}
@if($unreadNotifications > 0)
<div class="alert alert-primary d-flex align-items-center gap-2 mb-20" role="alert">
    <iconify-icon icon="solar:bell-outline" class="text-lg flex-shrink-0"></iconify-icon>
    {{ __('Vous avez') }} <strong class="mx-4">{{ $unreadNotifications }}</strong> {{ __('notification(s) non lue(s).') }}
</div>
@endif

{{-- Cartes statistiques --}}
<div class="row gy-4 mb-24">
    <div class="col-xxl-3 col-sm-6">
        <div class="card shadow-none border bg-gradient-start-1 h-100">
            <div class="card-body d-flex align-items-center gap-16">
                <div class="w-48-px h-48-px bg-primary-100 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                    <iconify-icon icon="solar:document-text-outline" class="text-primary-600 text-2xl"></iconify-icon>
                </div>
                <div>
                    <p class="text-secondary-light mb-4">{{ __('Articles au total') }}</p>
                    <h4 class="fw-semibold text-primary-600 mb-0">{{ $stats['articles_count'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="card shadow-none border bg-gradient-start-2 h-100">
            <div class="card-body d-flex align-items-center gap-16">
                <div class="w-48-px h-48-px bg-success-100 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                    <iconify-icon icon="solar:check-circle-outline" class="text-success-600 text-2xl"></iconify-icon>
                </div>
                <div>
                    <p class="text-secondary-light mb-4">{{ __('Publiés') }}</p>
                    <h4 class="fw-semibold text-success-600 mb-0">{{ $stats['published_count'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="card shadow-none border bg-gradient-start-3 h-100">
            <div class="card-body d-flex align-items-center gap-16">
                <div class="w-48-px h-48-px bg-warning-100 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                    <iconify-icon icon="solar:pen-new-round-outline" class="text-warning-600 text-2xl"></iconify-icon>
                </div>
                <div>
                    <p class="text-secondary-light mb-4">{{ __('Brouillons') }}</p>
                    <h4 class="fw-semibold text-warning-600 mb-0">{{ $stats['draft_count'] }}</h4>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <div class="card shadow-none border bg-gradient-start-4 h-100">
            <div class="card-body d-flex align-items-center gap-16">
                <div class="w-48-px h-48-px bg-purple-100 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                    <iconify-icon icon="solar:chat-line-outline" class="text-purple text-2xl"></iconify-icon>
                </div>
                <div>
                    <p class="text-secondary-light mb-4">{{ __('Commentaires reçus') }}</p>
                    <h4 class="fw-semibold mb-0" style="color:#7c3aed;">{{ $stats['comments_count'] }}</h4>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Actions rapides --}}
<div class="d-flex flex-wrap gap-12 mb-24">
    <a href="{{ route('user.articles.create') }}" class="btn btn-primary-600 radius-8">
        <iconify-icon icon="solar:add-circle-outline" style="font-size:18px;"></iconify-icon>
        {{ __('Nouvel article') }}
    </a>
    <a href="{{ route('blog.index') }}" class="btn btn-outline-primary-600 radius-8">
        <iconify-icon icon="solar:earth-outline" style="font-size:18px;"></iconify-icon>
        {{ __('Voir le blog') }}
    </a>
    <a href="{{ route('user.profile') }}" class="btn btn-outline-secondary radius-8">
        <iconify-icon icon="solar:user-circle-outline" style="font-size:18px;"></iconify-icon>
        {{ __('Mon profil') }}
    </a>
    @if(auth()->user()->hasAnyRole(['admin', 'super_admin']))
    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary-600 radius-8">
        <iconify-icon icon="solar:shield-outline" style="font-size:18px;"></iconify-icon>
        {{ __('Administration') }}
    </a>
    @endif
</div>

{{-- Articles récents --}}
<div class="card h-100 p-0 radius-12">
    <div class="card-header border-bottom bg-base py-16 px-24 d-flex align-items-center flex-wrap gap-3 justify-content-between">
        <h5 class="card-title fw-semibold text-lg mb-0">{{ __('Mes articles récents') }}</h5>
        <a href="{{ route('user.articles.create') }}" class="btn btn-primary text-sm btn-sm px-12 py-12 radius-8 d-flex align-items-center gap-2">
            <iconify-icon icon="solar:add-circle-outline" class="icon text-xl line-height-1"></iconify-icon> {{ __('Nouvel article') }}
        </a>
    </div>
    <div class="card-body p-0">
        @if($recentArticles->isEmpty())
            <div class="py-48 text-center text-secondary-light">
                <iconify-icon icon="solar:document-text-outline" class="text-5xl mb-12 d-block"></iconify-icon>
                <p class="mb-12">{{ __('Aucun article pour le moment.') }}</p>
                <a href="{{ route('user.articles.create') }}" class="btn btn-primary-600 radius-8">
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
                                    <span class="bg-success-focus text-success-600 border border-success-main px-24 py-4 radius-4 fw-medium text-sm">{{ __('Publié') }}</span>
                                @elseif($article->status === 'archived')
                                    <span class="bg-neutral-200 text-neutral-600 border border-neutral-400 px-24 py-4 radius-4 fw-medium text-sm">{{ __('Archivé') }}</span>
                                @else
                                    <span class="bg-warning-focus text-warning-600 border border-warning-main px-24 py-4 radius-4 fw-medium text-sm">{{ __('Brouillon') }}</span>
                                @endif
                            </td>
                            <td class="text-secondary-light">{{ $article->created_at->format('d M Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('user.articles.edit', $article) }}"
                                   class="bg-success-focus text-success-600 bg-hover-success-200 fw-medium w-40-px h-40-px d-flex justify-content-center align-items-center rounded-circle" title="Modifier">
                                    <iconify-icon icon="lucide:edit" class="icon text-xl"></iconify-icon>
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
