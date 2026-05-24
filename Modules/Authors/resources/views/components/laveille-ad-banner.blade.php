@props(['variant' => null])

@php
    $v = $variant ?? random_int(1, 4);
    $cta = match((int) $v) {
        1 => ['text' => "📬 Reçois la veille IA chaque dimanche → S'abonner à laveille.ai", 'url' => 'https://laveille.ai'],
        2 => ['text' => '🔧 Découvre nos outils IA gratuits → laveille.ai/outils', 'url' => 'https://laveille.ai/outils'],
        3 => ['text' => "📚 Livre « L'IA sans se faire poursuivre » par Stéphane → laveille.ai/livre", 'url' => 'https://laveille.ai/livre'],
        default => ['text' => '🏢 Services MEMORA solutions → memora.solutions', 'url' => 'https://memora.solutions'],
    };
@endphp

<div {{ $attributes->merge(['class' => 'mt-8 pt-6 border-t border-gray-200 dark:border-gray-700']) }}>
    <div class="max-w-3xl mx-auto px-4 py-3 text-center text-sm text-gray-600 dark:text-gray-400">
        <a href="{{ $cta['url'] }}" target="_blank" rel="noopener noreferrer" class="underline hover:no-underline transition-all">
            {!! $cta['text'] !!}
        </a>
    </div>
</div>
