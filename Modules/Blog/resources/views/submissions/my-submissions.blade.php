<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Mes articles') . ' - ' . config('app.name'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Mes articles')])
@endsection

@section('content')
<div class="container" style="padding: 20px 0 60px; min-height: 50vh;">
    <div style="max-width: 800px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h1 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); margin: 0;">{{ __('Mes articles soumis') }}</h1>
            <a href="{{ route('blog.submissions.create') }}" style="background: var(--c-primary); color: #fff; padding: 8px 20px; border-radius: var(--r-btn); font-weight: 600; text-decoration: none;">+ {{ __('Proposer') }}</a>
        </div>

        @forelse($submissions as $article)
        <div style="background: #fff; border: 1px solid #E5E7EB; border-radius: var(--r-base); padding: 20px; margin-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); margin: 0 0 6px; font-size: 1.1rem;">{{ $article->title }}</h3>
                    <p style="color: #6B7280; font-size: 13px; margin: 0;">{{ $article->created_at?->format('d/m/Y') }} · {{ $article->blogCategory?->name ?? '-' }}</p>
                </div>
                <div>
                    @if($article->submission_status === 'pending')
                        <span style="background: #FEF3C7; color: #92400E; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">{{ __('En attente') }}</span>
                    @elseif($article->submission_status === 'approved')
                        <span style="background: #D1FAE5; color: #065F46; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">{{ __('Publié') }}</span>
                    @elseif($article->submission_status === 'rejected')
                        <span style="background: #FEE2E2; color: #991B1B; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: 600;">{{ __('Refusé') }}</span>
                    @endif
                </div>
            </div>
            @if($article->submission_status === 'approved' && $article->published_at)
                <a href="{{ route('blog.show', $article->slug) }}" style="color: var(--c-primary); font-weight: 600; font-size: 13px; margin-top: 8px; display: inline-block;">{{ __('Voir l\'article publié') }} →</a>
            @endif
        </div>
        @empty
        <div style="text-align: center; padding: 60px 20px; background: #F9FAFB; border-radius: var(--r-base); border: 1px dashed #D1D5DB;">
            <div style="font-size: 48px; margin-bottom: 12px;">✍️</div>
            <h3 style="font-family: var(--f-heading); color: var(--c-dark);">{{ __('Vous n\'avez pas encore soumis d\'article') }}</h3>
            <p style="color: #6B7280;">{{ __('Partagez vos connaissances avec la communauté !') }}</p>
            <a href="{{ route('blog.submissions.create') }}" style="background: var(--c-primary); color: #fff; padding: 10px 24px; border-radius: var(--r-btn); font-weight: 600; text-decoration: none; display: inline-block; margin-top: 12px;">{{ __('Proposer un article') }}</a>
        </div>
        @endforelse
    </div>
</div>
@endsection
