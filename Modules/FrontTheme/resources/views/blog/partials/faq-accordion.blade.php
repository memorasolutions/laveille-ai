@if($article->faqs->isNotEmpty())
@php
    $publishedFaqs = $article->faqs->where('is_published', true)->values();
@endphp
@if($publishedFaqs->isNotEmpty())
<section aria-labelledby="faq-heading" class="article-faq-section">
    <h2 id="faq-heading" class="wp-block-heading">{{ __('Questions fréquentes') }}</h2>
    @foreach($publishedFaqs as $faq)
        <details class="article-faq-item">
            <summary>{{ $faq->question }}</summary>
            <div>{!! nl2br(e($faq->answer)) !!}</div>
        </details>
    @endforeach
</section>
@endif
@endif
