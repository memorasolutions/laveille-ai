@props([
    'icon' => '📭',
    'title' => __('Aucun résultat trouvé'),
    'description' => null,
    'variant' => 'empty',
])

<section
    {{ $attributes->class(['empty-state', 'empty-state--' . $variant]) }}
    role="status"
    aria-live="polite"
>
    <span class="empty-state__icon" aria-hidden="true">{{ $icon }}</span>

    <h3 class="empty-state__title">{{ $title }}</h3>

    @if($description)
        <p class="empty-state__description">{{ $description }}</p>
    @endif

    @isset($cta)
        <div class="empty-state__cta">
            {{ $cta }}
        </div>
    @endisset
</section>

@once
@push('styles')
<style>
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        background-color: #f9fafb;
        border-radius: var(--r-base, 0.5rem);
        padding: 60px 24px;
        width: 100%;
        box-sizing: border-box;
    }
    .empty-state__icon { font-size: 48px; line-height: 1; margin-bottom: 16px; display: block; }
    .empty-state__title {
        color: var(--c-dark, #111827);
        font-family: var(--f-heading, system-ui, -apple-system, sans-serif);
        font-weight: 700;
        font-size: 1.2rem;
        line-height: 1.4;
        margin: 0 0 8px;
    }
    .empty-state__description {
        color: #374151;
        font-size: 0.95rem;
        line-height: 1.6;
        margin: 0 0 4px;
        max-width: 480px;
    }
    .empty-state__cta { margin-top: 20px; }

    .empty-state--search { border: 1px dashed #d1d5db; }
    .empty-state--education { border: 1px solid #d1fae5; background-color: #f0fdf4; }
    .empty-state--error { border: 1px solid #fecaca; background-color: #fef2f2; }
    .empty-state--filter { border: 1px dashed #c7d2fe; background-color: #eef2ff; }
    .empty-state--empty { border: 1px solid #e5e7eb; }

    @media (max-width: 640px) {
        .empty-state { padding: 40px 16px; }
        .empty-state__icon { font-size: 40px; }
        .empty-state__title { font-size: 1.1rem; }
        .empty-state__description { font-size: 0.875rem; }
    }
</style>
@endpush
@endonce
