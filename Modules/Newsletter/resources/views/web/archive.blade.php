<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Archives de l\'infolettre') . ' - ' . config('app.name'))
@section('meta_description', __('Retrouvez tous les numéros passés de l\'infolettre La veille, votre veille technologique hebdomadaire en IA.'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Infolettre')])
@endsection

@section('content')
    <h1 class="sr-only">{{ __('Archives de l\'infolettre') }}</h1>
    <section class="wpo-blog-single-section section-padding">
        <div class="container">
            @if($issues->isEmpty())
                <div class="alert alert-info text-center">{{ __('Aucun numéro publié pour le moment. Revenez bientôt !') }}</div>
            @else
            <div class="row">
                @foreach($issues as $issue)
                <div class="col-md-4 col-sm-6" style="margin-bottom:20px;">
                    <div class="panel panel-default" style="border-radius:6px;overflow:hidden;">
                        <div class="panel-body" style="padding:20px;">
                            <span style="display:inline-block;background-color:#0B7285;color:#fff;font-size:11px;font-weight:bold;padding:3px 8px;border-radius:3px;margin-bottom:10px;">Semaine {{ $issue->week_number }}</span>
                            <h3 style="font-size:16px;margin:0 0 8px;line-height:1.3;">
                                <a href="{{ $issue->web_url }}" style="color:#1a1a2e;">{{ $issue->subject }}</a>
                            </h3>
                            <p style="margin:0 0 12px;font-size:13px;color:#777;">{{ $issue->sent_at?->translatedFormat('j F Y') }}</p>
                            <a href="{{ $issue->web_url }}" style="color:#0B7285;font-weight:bold;font-size:14px;">{{ __('Lire ce numéro') }} &rarr;</a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="text-center">{{ $issues->links() }}</div>
            @endif
        </div>
    </section>
@endsection
