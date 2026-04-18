@props([
    'title' => '',
    'summary' => '',
    'sections' => [],
    'directUrl' => '',
    'indexUrl' => '',
    'indexLabel' => 'Voir tous les termes',
    'utmSource' => 'share',
    'kind' => 'Terme',
    'buttonLabel' => 'Partager',
])

@if($title && $directUrl)
@php
    $directUrlUtm = $directUrl . '?utm_source=' . urlencode($utmSource) . '&utm_medium=clipboard';

    $plain = $kind . ' : ' . $title . "\n";
    if ($summary) {
        $plain .= $summary . "\n";
    }
    $plain .= "\n";
    foreach ($sections as $section) {
        $icon = $section['icon'] ?? '';
        $label = $section['label'] ?? '';
        $content = $section['content'] ?? '';
        if ($content) {
            $plain .= ($icon ? $icon . ' ' : '') . ($label ? $label . ' : ' : '') . $content . "\n";
        }
    }
    $plain .= "\n";
    $plain .= '🔗 ' . $directUrlUtm . "\n";
    if ($indexUrl) {
        $plain .= '📚 ' . $indexLabel . ' : ' . $indexUrl . "\n";
    }
    $plain .= "\n" . 'Via laveille.ai';

    $html = '<strong>' . e($kind) . ' : ' . e($title) . '</strong><br>';
    if ($summary) {
        $html .= e($summary) . '<br>';
    }
    $html .= '<br>';
    foreach ($sections as $section) {
        $icon = $section['icon'] ?? '';
        $label = $section['label'] ?? '';
        $content = $section['content'] ?? '';
        if ($content) {
            $html .= ($icon ? e($icon) . ' ' : '') . ($label ? '<strong>' . e($label) . '</strong> : ' : '') . e($content) . '<br>';
        }
    }
    $html .= '<br>';
    $html .= '🔗 <a href="' . e($directUrlUtm) . '">' . e($directUrlUtm) . '</a><br>';
    if ($indexUrl) {
        $html .= '📚 <a href="' . e($indexUrl) . '">' . e($indexLabel) . '</a><br>';
    }
    $html .= '<br>Via <a href="https://laveille.ai">laveille.ai</a>';

    $jsonLdData = [
        '@context' => 'https://schema.org',
        '@type' => 'DefinedTerm',
        'name' => $title,
        'description' => $summary ?: $title,
        'url' => $directUrl,
    ];
    $jsonLd = json_encode($jsonLdData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG);

    $jsFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG;
    $plainPayloadJs = json_encode($plain, $jsFlags);
    $htmlPayloadJs = json_encode($html, $jsFlags);
    $directUrlUtmJs = json_encode($directUrlUtm, $jsFlags);
    $titleJs = json_encode($title, $jsFlags);

    $feedbackId = 'share-feedback-' . uniqid();
@endphp
<div style="position:relative; display:inline-block;">
    <script type="application/ld+json">
        {!! $jsonLd !!}
    </script>
    <button
        type="button"
        class="aab-btn"
        style="min-height:44px; min-width:44px; display:inline-flex; align-items:center; cursor:pointer;"
        x-data="{
            feedback: null,
            busy: false,
            share() {
                if (this.busy) return;
                this.busy = true;
                this.feedback = null;
                const payload = {!! $plainPayloadJs !!};
                const payloadHtml = {!! $htmlPayloadJs !!};
                const url = {!! $directUrlUtmJs !!};
                const title = {!! $titleJs !!};

                if (navigator.share && typeof navigator.canShare === 'function') {
                    try {
                        if (navigator.canShare({ title, text: payload, url })) {
                            navigator.share({ title, text: payload, url })
                                .then(() => { this.feedback = 'shared'; })
                                .catch(err => {
                                    if (err && err.name === 'AbortError') { this.feedback = null; }
                                    else this.copyFallback(payload, payloadHtml);
                                })
                                .finally(() => { this.busy = false; this.reset(); });
                            return;
                        }
                    } catch(e) {}
                }
                this.copyFallback(payload, payloadHtml);
            },
            copyFallback(payload, payloadHtml) {
                try {
                    if (navigator.clipboard && typeof ClipboardItem !== 'undefined') {
                        const item = new ClipboardItem({
                            'text/plain': new Blob([payload], { type: 'text/plain' }),
                            'text/html': new Blob([payloadHtml], { type: 'text/html' })
                        });
                        navigator.clipboard.write([item])
                            .then(() => { this.feedback = 'copied'; })
                            .catch(() => this.textFallback(payload))
                            .finally(() => { this.busy = false; this.reset(); });
                        return;
                    }
                } catch(e) {}
                this.textFallback(payload);
            },
            textFallback(payload) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(payload)
                        .then(() => { this.feedback = 'copied'; })
                        .catch(() => { this.feedback = 'error'; })
                        .finally(() => { this.busy = false; this.reset(); });
                } else {
                    this.feedback = 'error';
                    this.busy = false;
                    this.reset();
                }
            },
            reset() { setTimeout(() => { this.feedback = null; }, 2500); }
        }"
        @click="share()"
        :aria-busy="busy"
        :disabled="busy"
        aria-label="{{ addslashes($buttonLabel) }} {{ addslashes($kind) }} : {{ addslashes($title) }}"
        aria-describedby="{{ $feedbackId }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" style="width:18px; height:18px; flex-shrink:0; vertical-align:middle;">
            <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
            <polyline points="16 6 12 2 8 6"/>
            <line x1="12" y1="2" x2="12" y2="15"/>
        </svg>
        <span class="aab-label" style="margin-left:6px;">{{ $buttonLabel }}</span>
    </button>
    <span
        id="{{ $feedbackId }}"
        x-show="feedback"
        x-cloak
        role="status"
        aria-live="polite"
        aria-atomic="true"
        :style="'position:absolute; bottom:calc(100% + 8px); left:50%; transform:translateX(-50%); padding:6px 12px; border-radius:6px; font-size:0.75rem; font-weight:500; white-space:nowrap; box-shadow:0 2px 8px rgba(0,0,0,0.15); z-index:10; pointer-events:none; background:' + (feedback === 'error' ? '#fef2f2' : (feedback === 'shared' ? '#ecfdf5' : '#eff6ff')) + '; color:' + (feedback === 'error' ? '#b91c1c' : (feedback === 'shared' ? '#065f46' : '#1e40af'))"
        x-text="feedback === 'shared' ? 'Partagé !' : (feedback === 'copied' ? 'Copié !' : 'Erreur de partage')"
    ></span>
</div>
@endif
