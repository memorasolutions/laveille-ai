<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', ($issue->subject ?? __('Infolettre')) . ' - ' . config('app.name'))
@section('meta_description', __('Infolettre La veille') . ' #' . ($weekNumber ?? '') . ' - ' . __('veille technologique IA'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', [
        'breadcrumbTitle' => $issue->subject ?? __('Infolettre'),
        'breadcrumbItems' => [__('Infolettre'), $issue->subject ?? ''],
    ])
@endsection

@section('content')
    <section class="wpo-blog-single-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col col-lg-8 col-12">
                    <div class="wpo-blog-content">

                    <h1 style="font-size:1.8rem;margin:0 0 8px;">{{ $subject }}</h1>
                    <p style="color:#777;font-size:14px;margin-bottom:20px;">{{ $issue->sent_at?->translatedFormat('j F Y') }} - Semaine {{ $weekNumber }}</p>

                    {{-- Mini-éditorial --}}
                    @if($editorial ?? null)
                    <blockquote style="border-left:4px solid #0B7285;padding:12px 20px;margin:0 0 30px;background-color:#f8fafc;border-radius:0 6px 6px 0;">
                        <p style="margin:0;font-size:15px;color:#333;font-style:italic;line-height:1.6;">{{ $editorial }}</p>
                    </blockquote>
                    @endif

                    {{-- Fait marquant --}}
                    @if($highlight ?? null)
                    <div class="panel panel-default" style="border-left:4px solid #0B7285;border-radius:6px;">
                        <div class="panel-body">
                            <p style="font-size:11px;text-transform:uppercase;color:#0B7285;font-weight:bold;margin-bottom:10px;">Le fait marquant</p>
                            @if($highlight->image_url)
                            <img src="{{ asset($highlight->image_url) }}" alt="{{ $highlight->seo_title ?? $highlight->title }}" class="img-responsive" style="border-radius:6px;margin-bottom:12px;max-height:300px;width:100%;object-fit:cover;" loading="lazy">
                            @endif
                            <h2 style="font-size:20px;margin:0 0 8px;">{{ $highlight->seo_title ?? $highlight->title }}</h2>
                            <p style="color:#555;">{{ Str::limit($highlight->summary ?? strip_tags($highlight->content ?? ''), 200) }}</p>
                            <a href="{{ $highlight->url ?? route('news.show', $highlight->slug ?? '') }}" style="color:#0B7285;font-weight:bold;">Lire l'article &rarr;</a>
                        </div>
                    </div>
                    @endif

                    {{-- Défi de la quinzaine --}}
                    @if(($weeklyPrompt ?? null) && (($weekNumber ?? 0) % 2 === 0))
                    <div style="background-color:#0c1427;border-radius:6px;padding:24px;margin-bottom:20px;">
                        <p style="font-size:11px;text-transform:uppercase;color:#3dc9d8;font-weight:bold;margin-bottom:12px;">Défi de la quinzaine</p>
                        <p style="color:#e2e8f0;font-size:16px;margin-bottom:12px;">Essayez ce prompt cette semaine :</p>
                        <div style="background-color:#1e293b;border:1px solid #3dc9d8;border-radius:6px;padding:15px;margin-bottom:12px;">
                            <p style="color:#e2e8f0;font-style:italic;font-size:15px;margin:0;line-height:1.5;">{{ is_array($weeklyPrompt) ? ($weeklyPrompt['prompt'] ?? '') : $weeklyPrompt }}</p>
                        </div>
                        @if(is_array($weeklyPrompt) && ($weeklyPrompt['technique'] ?? null))
                        <div style="border-left:3px solid #3dc9d8;padding-left:12px;margin-bottom:12px;">
                            <p style="color:#94a3b8;font-size:13px;margin:0;"><strong style="color:#3dc9d8;">Technique utilisée :</strong> {{ $weeklyPrompt['technique'] }}</p>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Actualités --}}
                    @if(($topNews ?? null) && $topNews->count())
                    <h2 style="font-size:18px;color:#0B7285;margin-bottom:16px;">Actualités de la semaine</h2>
                    @foreach($topNews as $news)
                    <div class="media" style="margin-bottom:16px;padding-bottom:16px;{{ !$loop->last ? 'border-bottom:1px solid #f0f0f0;' : '' }}">
                        @if($news->image_url)
                        <div class="media-left">
                            <img src="{{ asset($news->image_url) }}" alt="{{ $news->seo_title ?? $news->title }}" style="width:80px;height:80px;border-radius:6px;object-fit:cover;" loading="lazy">
                        </div>
                        @endif
                        <div class="media-body">
                            <h4 style="margin:0 0 4px;font-size:15px;"><a href="{{ $news->url ?? route('news.show', $news->slug ?? '') }}" style="color:#1a1a2e;">{{ $news->seo_title ?? $news->title }}</a></h4>
                            @if($news->summary)<p style="color:#777;font-size:13px;margin:0;">{{ Str::limit(strip_tags($news->summary), 100) }}</p>@endif
                        </div>
                    </div>
                    @endforeach
                    @endif

                    {{-- Outil de la semaine --}}
                    @if($toolOfWeek ?? null)
                    <h2 style="font-size:18px;color:#0B7285;margin-bottom:16px;">Outil de la semaine</h2>
                    <div class="panel panel-default" style="border-radius:6px;background-color:#f0fdfa;">
                        <div class="panel-body">
                            <div class="media">
                                @if($toolOfWeek->screenshot)
                                <div class="media-left">
                                    <img src="{{ str_starts_with($toolOfWeek->screenshot, 'http') ? $toolOfWeek->screenshot : asset($toolOfWeek->screenshot) }}" alt="{{ $toolOfWeek->name }}" style="width:200px;border-radius:8px;" loading="lazy">
                                </div>
                                @endif
                                <div class="media-body">
                                    <h3 style="margin:0 0 6px;font-size:20px;">{{ $toolOfWeek->name }}</h3>
                                    <p style="color:#555;">{{ Str::limit(strip_tags($toolOfWeek->short_description ?? $toolOfWeek->description ?? ''), 150) }}</p>
                                    <a href="{{ route('directory.show', $toolOfWeek->slug) }}" style="color:#0B7285;font-weight:bold;">Découvrir &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Article à lire --}}
                    @if($featuredArticle ?? null)
                    <h2 style="font-size:18px;color:#0B7285;margin-bottom:16px;">À lire cette semaine</h2>
                    <div class="panel panel-default" style="border-radius:6px;">
                        <div class="panel-body">
                            <h3 style="margin:0 0 8px;font-size:18px;"><a href="{{ route('blog.show', $featuredArticle->slug) }}" style="color:#1a1a2e;">{{ $featuredArticle->title }}</a></h3>
                            <p style="color:#555;">{{ Str::limit(strip_tags($featuredArticle->excerpt ?? $featuredArticle->content ?? ''), 150) }}</p>
                            <a href="{{ route('blog.show', $featuredArticle->slug) }}" style="color:#0B7285;font-weight:bold;">Lire l'article &rarr;</a>
                        </div>
                    </div>
                    @endif

                    {{-- Outil gratuit --}}
                    @if($interactiveTool ?? null)
                    <h2 style="font-size:18px;color:#d97706;margin-bottom:16px;">Outil gratuit à essayer</h2>
                    <div class="panel panel-default" style="border-radius:6px;background-color:#fffbeb;">
                        <div class="panel-body">
                            <h3 style="margin:0 0 8px;font-size:18px;">{{ $interactiveTool->icon ?? '' }} {{ $interactiveTool->name }}</h3>
                            <p style="color:#555;">{{ Str::limit(strip_tags($interactiveTool->description ?? ''), 150) }}</p>
                            <a href="{{ route('tools.show', $interactiveTool->slug) }}" class="btn" style="background-color:#d97706;color:#fff;border:none;border-radius:4px;">Essayer gratuitement &rarr;</a>
                        </div>
                    </div>
                    @endif

                    {{-- Terme IA --}}
                    @if($aiTerm ?? null)
                    <h2 style="font-size:18px;color:#0B7285;margin-bottom:16px;">Terme IA de la semaine</h2>
                    <div class="panel panel-default" style="border-radius:6px;background-color:#f8fafc;">
                        <div class="panel-body">
                            <div class="media">
                                @if($aiTerm->hero_image)
                                <div class="media-left">
                                    <img src="{{ asset($aiTerm->hero_image) }}" alt="{{ $aiTerm->name }}" style="width:150px;height:150px;border-radius:8px;object-fit:cover;" loading="lazy">
                                </div>
                                @endif
                                <div class="media-body">
                                    <h3 style="margin:0 0 8px;font-size:20px;">{{ $aiTerm->name }}</h3>
                                    <p style="color:#555;">{{ Str::limit(strip_tags($aiTerm->definition ?? ''), 200) }}</p>
                                </div>
                            </div>
                            @if($aiTerm->analogy)
                            <div style="border-left:4px solid #0B7285;padding-left:15px;margin-top:14px;">
                                <p style="color:#0B7285;font-size:13px;font-style:italic;margin-bottom:4px;">En d'autres mots...</p>
                                <p style="color:#555;">{{ strip_tags($aiTerm->analogy) }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- CTA abonnement --}}
                    <div class="text-center" style="margin:30px 0;">
                        <a href="{{ route('home') }}#newsletter" class="btn btn-lg" style="background-color:#0B7285;color:#fff;border:none;border-radius:4px;padding:12px 30px;font-weight:bold;">S'abonner à l'infolettre</a>
                        @if(Route::has('newsletter.archive'))
                        <br/><a href="{{ route('newsletter.archive') }}" style="color:#0B7285;font-size:14px;margin-top:10px;display:inline-block;">Voir tous les numéros &rarr;</a>
                        @endif
                    </div>

                    </div>
                </div>
                <div class="col col-lg-4 col-12">
                    @include('fronttheme::partials.sidebar')
                </div>
            </div>
        </div>
    </section>
@endsection
