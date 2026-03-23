<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Outils gratuits') . ' - ' . config('app.name'))
@section('meta_description', __('Des outils gratuits pour votre quotidien numérique : calculatrice de taxes, générateur de mots de passe, code QR, simulateur fiscal, roue de tirage et plus.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Outils gratuits')])
@endsection

@section('content')
    <section class="wpo-blog-pg-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="wpo-section-title" style="margin-bottom: 2rem;">
                        <h2>{{ __('Outils gratuits') }}</h2>
                        <p>{{ __('Des outils pratiques pour votre quotidien numérique.') }}</p>
                    </div>

                    {{-- Ad: tools top --}}
                    @if(class_exists(\Modules\Ads\Services\AdsRenderer::class))
                        {!! app(\Modules\Ads\Services\AdsRenderer::class)->render('tools-top') !!}
                    @endif

                    <div class="row">
                        @forelse($tools as $tool)
                            <div class="col-lg-4 col-md-6 col-12 mb-4">
                                <div class="card h-100 shadow-sm" style="border-radius: var(--r-base); overflow: hidden; transition: transform 0.2s;">
                                    @if($tool->featured_image && file_exists(public_path($tool->featured_image)))
                                        <img src="{{ asset($tool->featured_image) }}" class="card-img-top" alt="{{ $tool->name }}" style="height: 180px; object-fit: cover;">
                                    @else
                                        <div style="height: 180px; background: linear-gradient(135deg, var(--c-primary), var(--c-primary-hover)); display: flex; align-items: center; justify-content: center;">
                                            <span style="font-size: 48px; color: rgba(255,255,255,0.3);">{{ $tool->icon ?? '🔧' }}</span>
                                        </div>
                                    @endif
                                    <div class="card-body d-flex flex-column">
                                        <h3 class="card-title" style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark); font-size: 1.15rem;">
                                            {{ $tool->name }}
                                        </h3>
                                        <p class="card-text text-muted flex-grow-1">{{ $tool->description }}</p>
                                        <a href="{{ route('tools.show', $tool->slug) }}" class="btn mt-2" style="background: var(--c-accent); color: #fff; border-radius: var(--r-btn); font-family: var(--f-heading); font-weight: 700;">
                                            {{ __('Utiliser') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-info">{{ __('Aucun outil disponible pour le moment.') }}</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
