@if($article->faqs->isNotEmpty())
@php
    $publishedFaqs = $article->faqs->where('is_published', true)->values();
@endphp
@if($publishedFaqs->isNotEmpty())
<section aria-labelledby="faq-heading" style="margin:48px 0;padding:32px;background:#ffffff;border-radius:12px;">
    <h2 id="faq-heading" style="color:#374151;font-size:1.5rem;font-weight:700;margin:0 0 24px 0;">Questions fréquentes</h2>
    <div x-data="{ openIndex: null }">
        @foreach($publishedFaqs as $index => $faq)
        <div style="margin-bottom:8px;">
            <button
                type="button"
                :aria-expanded="openIndex === {{ $index }}"
                :aria-controls="'faq-answer-{{ $index }}'"
                @click="openIndex = openIndex === {{ $index }} ? null : {{ $index }}"
                id="faq-question-{{ $index }}"
                style="background:#ffffff;color:#374151;padding:16px;border:1px solid #e5e7eb;border-radius:8px;width:100%;text-align:left;cursor:pointer;font-weight:600;font-size:1rem;min-height:44px;display:flex;align-items:center;justify-content:space-between;gap:12px;line-height:1.4;transition:border-radius 0.15s ease;"
                :style="openIndex === {{ $index }} ? 'border-radius:8px 8px 0 0' : 'border-radius:8px'"
                class="faq-accordion-btn"
            >
                <span>{{ $faq->question }}</span>
                <svg aria-hidden="true" width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;transition:transform 0.2s ease;" :style="openIndex === {{ $index }} ? 'transform:rotate(45deg)' : 'transform:rotate(0deg)'">
                    <line x1="10" y1="4" x2="10" y2="16"></line>
                    <line x1="4" y1="10" x2="16" y2="10"></line>
                </svg>
            </button>
            <div
                x-show="openIndex === {{ $index }}"
                x-collapse
                role="region"
                :aria-labelledby="'faq-question-{{ $index }}'"
                id="faq-answer-{{ $index }}"
                style="padding:16px;color:#374151;background:#f9fafb;border-radius:0 0 8px 8px;border:1px solid #e5e7eb;border-top:0;line-height:1.6;"
            >
                {!! nl2br(e($faq->answer)) !!}
            </div>
        </div>
        @endforeach
    </div>
</section>
<style>
    .faq-accordion-btn:focus-visible {
        outline: 2px solid var(--c-primary, #2563eb);
        outline-offset: 2px;
    }
</style>
@endif
@endif
