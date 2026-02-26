@extends('blog::public.layout')

@section('title', $user->name . ' — Auteur')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-12">

    {{-- Breadcrumb --}}
    <div class="mb-6">
        <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Blog
        </a>
    </div>

    {{-- Header auteur --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-8 mb-10">
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
            {{-- Avatar --}}
            @if($user->avatar)
                <img src="{{ asset('storage/' . $user->avatar) }}"
                     alt="{{ $user->name }}"
                     class="w-20 h-20 rounded-full object-cover flex-shrink-0">
            @else
                <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center text-2xl font-bold text-blue-600 flex-shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif

            {{-- Infos --}}
            <div class="text-center sm:text-left">
                <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
                @if($user->bio)
                    <p class="text-gray-500 mt-2 text-sm leading-relaxed max-w-xl">{{ $user->bio }}</p>
                @endif
                <div class="flex flex-wrap justify-center sm:justify-start gap-4 mt-4 text-sm text-gray-400">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        {{ $totalArticles }} article(s) publié(s)
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Membre depuis {{ $user->created_at->format('M Y') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Articles de l'auteur --}}
    <h2 class="text-xl font-bold text-gray-900 mb-6">Articles de {{ $user->name }}</h2>

    @if($articles->isEmpty())
        <div class="py-20 text-center text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-lg font-medium text-gray-500">Aucun article publié par cet auteur.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($articles as $article)
            <article class="bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition overflow-hidden group">
                @if($article->featured_image)
                    <a href="{{ route('blog.show', $article->slug) }}">
                        <img src="{{ asset('storage/' . $article->featured_image) }}"
                             alt="{{ $article->title }}"
                             class="w-full h-44 object-cover group-hover:scale-105 transition duration-300">
                    </a>
                @else
                    <a href="{{ route('blog.show', $article->slug) }}"
                       class="block h-44 bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center">
                        <svg class="w-12 h-12 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </a>
                @endif

                <div class="p-5">
                    <h3 class="text-base font-bold text-gray-900 mb-2 leading-snug group-hover:text-blue-600 transition">
                        <a href="{{ route('blog.show', $article->slug) }}">{{ $article->title }}</a>
                    </h3>

                    @if($article->excerpt)
                        <p class="text-sm text-gray-500 line-clamp-2 mb-4">{{ $article->excerpt }}</p>
                    @endif

                    <div class="flex items-center justify-between text-xs text-gray-400 pt-3 border-t border-gray-50">
                        <span>{{ $article->published_at?->format('d M Y') }}</span>
                        @if($article->tags && count($article->tags) > 0)
                            <span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">{{ $article->tags[0] }}</span>
                        @endif
                    </div>
                </div>
            </article>
            @endforeach
        </div>

        @if($articles->hasPages())
        <div class="mt-10">
            {{ $articles->links() }}
        </div>
        @endif
    @endif

</div>
@endsection
