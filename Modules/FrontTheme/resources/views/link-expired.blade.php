@extends(fronttheme_layout())

@section('title', ($reason === 'expired' ? __('Lien expiré') : __('Lien introuvable')) . ' - ' . config('app.name'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $reason === 'expired' ? __('Lien expiré') : __('Lien introuvable')])
@endsection

@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center" style="padding: 40px 20px;">
                <div style="font-size: 4rem; margin-bottom: 16px;">
                    {{ $reason === 'expired' ? '⏰' : '🔗' }}
                </div>
                <h1 style="font-family: var(--f-heading); font-weight: 700; margin-bottom: 12px;">
                    {{ $reason === 'expired' ? __('Lien expiré') : __('Lien introuvable') }}
                </h1>
                <p style="color: #374151; font-size: 16px; line-height: 1.6; margin-bottom: 24px;">
                    @if($reason === 'expired')
                        {{ __('Ce lien court a expiré ou a été désactivé par son propriétaire. Il n\'est plus accessible.') }}
                    @else
                        {{ __('Ce lien court n\'existe pas ou a été supprimé. Vérifiez l\'URL et réessayez.') }}
                    @endif
                </p>
                <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
                    <a href="{{ route('home') }}" class="btn btn-primary" style="border-radius: 8px; padding: 12px 28px; font-weight: 600;">
                        {{ __('Aller à l\'accueil') }}
                    </a>
                    @if(Route::has('shorturl.create'))
                        <a href="{{ route('shorturl.create') }}" class="btn btn-outline-secondary" style="border-radius: 8px; padding: 12px 28px; font-weight: 600;">
                            {{ __('Raccourcir un lien') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
