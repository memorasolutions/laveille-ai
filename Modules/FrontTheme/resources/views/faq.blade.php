<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())

@section('title', __('Foire aux questions') . ' - ' . config('app.name'))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => __('Foire aux questions')])
@endsection

@section('content')
    <section class="wpo-blog-single-section section-padding">
        <div class="container">
            <div class="row">
                <div class="col col-lg-10 offset-lg-1">
                    @forelse($faqs as $category => $items)
                        @if($category)
                            <h3 class="mb-3 mt-4">{{ $category }}</h3>
                        @endif
                        <div class="accordion" id="faq-{{ Str::slug($category ?: 'general') }}">
                            @foreach($items as $faq)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-{{ $faq->id }}">
                                        <button class="accordion-button{{ $loop->first && $loop->parent->first ? '' : ' collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $faq->id }}" aria-expanded="{{ $loop->first && $loop->parent->first ? 'true' : 'false' }}" aria-controls="collapse-{{ $faq->id }}">
                                            {{ $faq->question }}
                                        </button>
                                    </h2>
                                    <div id="collapse-{{ $faq->id }}" class="accordion-collapse collapse{{ $loop->first && $loop->parent->first ? ' show' : '' }}" aria-labelledby="heading-{{ $faq->id }}" data-bs-parent="#faq-{{ Str::slug($category ?: 'general') }}">
                                        <div class="accordion-body">
                                            {!! $faq->safeAnswer() !!}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="alert alert-info">{{ __('Aucune question pour le moment.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

@push('scripts')
@php
    $faqItems = [];
    foreach ($faqs as $category => $items) {
        foreach ($items as $faq) {
            $faqItems[] = [
                chr(64).'type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    chr(64).'type' => 'Answer',
                    'text' => strip_tags($faq->answer),
                ],
            ];
        }
    }
@endphp
<script type="application/ld+json">
{!! json_encode([chr(64).'context' => 'https://schema.org', chr(64).'type' => 'FAQPage', 'mainEntity' => $faqItems], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>
@endpush
@endsection
