@extends('auth::layouts.app')

@section('title', __('Mes articles'))

@section('content')

<div class="d-flex align-items-center justify-content-between mb-20 flex-wrap gap-12">
    <div>
        <h1 class="fw-semibold mb-4">{{ __('Mes articles') }}</h1>
        <p class="text-secondary-light mb-0">{{ __('Gérez vos articles de blog.') }}</p>
    </div>
    <a href="{{ route('user.articles.create') }}" class="btn btn-primary-600 radius-8">
        <iconify-icon icon="solar:add-circle-outline"></iconify-icon>
        {{ __('Nouvel article') }}
    </a>
</div>

@if(session('success'))
<div class="alert alert-success d-flex align-items-center gap-2 mb-20">
    <iconify-icon icon="solar:check-circle-outline"></iconify-icon>
    {{ session('success') }}
</div>
@endif

<div class="card">
    <div class="card-body p-0">
        @if($articles->isEmpty())
            <div class="py-48 text-center text-secondary-light">
                <iconify-icon icon="solar:document-text-outline" class="text-5xl mb-12 d-block"></iconify-icon>
                <p class="fw-medium mb-4">{{ __('Aucun article pour le moment.') }}</p>
                <p class="text-sm mb-16">{{ __('Créez votre premier article dès maintenant.') }}</p>
                <a href="{{ route('user.articles.create') }}" class="btn btn-primary-600 radius-8">
                    {{ __('Créer un article') }}
                </a>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Titre') }}</th>
                            <th>{{ __('Statut') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th class="text-end">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($articles as $article)
                        <tr>
                            <td>
                                <p class="fw-semibold mb-0">{{ $article->title }}</p>
                                @if($article->excerpt)
                                    <p class="text-secondary-light text-xs mb-0">{{ Str::limit($article->excerpt, 60) }}</p>
                                @endif
                            </td>
                            <td>
                                @if($article->status === 'published')
                                    <span class="badge text-sm fw-semibold px-10 py-4 radius-4 bg-success-focus text-success-main">
                                        <span style="width:6px;height:6px;background:currentColor;border-radius:50%;display:inline-block;"></span>
                                        {{ __('Publié') }}
                                    </span>
                                @elseif($article->status === 'draft')
                                    <span class="badge text-sm fw-semibold px-10 py-4 radius-4 bg-warning-focus text-warning-main">
                                        <span style="width:6px;height:6px;background:currentColor;border-radius:50%;display:inline-block;"></span>
                                        {{ __('Brouillon') }}
                                    </span>
                                @else
                                    <span class="badge text-sm fw-semibold px-10 py-4 radius-4 bg-neutral-focus text-neutral-main">
                                        <span style="width:6px;height:6px;background:currentColor;border-radius:50%;display:inline-block;"></span>
                                        {{ __('Archivé') }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-secondary-light">{{ $article->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-8">
                                    <a href="{{ route('user.articles.edit', $article) }}"
                                       class="btn btn-sm btn-outline-primary-600 radius-8">
                                        <iconify-icon icon="solar:pen-outline"></iconify-icon> {{ __('Modifier') }}
                                    </a>
                                    @if($article->status === 'published')
                                    <a href="{{ route('blog.show', $article->slug) }}" target="_blank"
                                       class="btn btn-sm btn-outline-secondary radius-8">
                                        <iconify-icon icon="solar:eye-outline"></iconify-icon>
                                    </a>
                                    @endif
                                    <form method="POST" action="{{ route('user.articles.destroy', $article) }}"
                                          onsubmit="return confirm('{{ __('Supprimer cet article ?') }}')" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger-600 radius-8">
                                            <iconify-icon icon="solar:trash-bin-minimalistic-outline"></iconify-icon>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($articles->hasPages())
            <div class="card-body border-top">
                {{ $articles->links() }}
            </div>
            @endif
        @endif
    </div>
</div>

@endsection
