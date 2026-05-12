@props([
    'prompt' => '',
    'label' => 'Ouvrir dans une IA',
    'compact' => false,
    'showLabel' => true,
])
{{-- Composant DRY Memora : copy-to-clipboard + redirect vers l'IA choisie. Zéro coût serveur, zéro donnée laveille. --}}
{{-- URL pré-remplissage 2026 : ChatGPT/Claude/Perplexity/Mistral/Copilot supportent ?q=, Gemini = fallback copy + ouvre. --}}
<div x-data="openInAI(@js((string) $prompt))" class="open-in-ai">
    @if($showLabel)
    <p class="open-in-ai__label">{{ $label }} :</p>
    @endif
    <div class="open-in-ai__row">
        <button type="button" class="open-in-ai__btn" @click="goTo('chatgpt')" :disabled="!hasPrompt" aria-label="Ouvrir dans ChatGPT">
            <span aria-hidden="true">💬</span><span>ChatGPT</span>
        </button>
        <button type="button" class="open-in-ai__btn" @click="goTo('claude')" :disabled="!hasPrompt" aria-label="Ouvrir dans Claude">
            <span aria-hidden="true">🤖</span><span>Claude</span>
        </button>
        <button type="button" class="open-in-ai__btn" @click="goTo('gemini')" :disabled="!hasPrompt" aria-label="Ouvrir dans Gemini">
            <span aria-hidden="true">✨</span><span>Gemini</span>
        </button>
        <button type="button" class="open-in-ai__btn" @click="goTo('perplexity')" :disabled="!hasPrompt" aria-label="Ouvrir dans Perplexity">
            <span aria-hidden="true">🔎</span><span>Perplexity</span>
        </button>
        <button type="button" class="open-in-ai__btn" @click="goTo('mistral')" :disabled="!hasPrompt" aria-label="Ouvrir dans Mistral Le Chat">
            <span aria-hidden="true">🌬️</span><span>Mistral</span>
        </button>
        <button type="button" class="open-in-ai__btn" @click="goTo('copilot')" :disabled="!hasPrompt" aria-label="Ouvrir dans Microsoft Copilot">
            <span aria-hidden="true">🟦</span><span>Copilot</span>
        </button>
    </div>
    <p class="open-in-ai__privacy">🔒 Aucune donnée n'est envoyée à laveille.ai. Le prompt va directement dans ton IA préférée.</p>
</div>

@once
<style>
.open-in-ai { margin: 0.75rem 0; }
.open-in-ai__label { font-size: 0.875rem; color: var(--c-text-muted, #6b7280); margin: 0 0 0.5rem; font-weight: 600; }
.open-in-ai__row { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.open-in-ai__btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.5rem 0.85rem; min-height: 38px; border-radius: 8px; border: 1.5px solid var(--c-primary, #0B7285); background: #fff; color: var(--c-primary, #0B7285); font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.15s ease; font-family: inherit; }
.open-in-ai__btn:hover { background: var(--c-primary, #0B7285); color: #fff; transform: translateY(-1px); }
.open-in-ai__btn:focus-visible { outline: 3px solid var(--c-accent, #9A2A06); outline-offset: 2px; }
.open-in-ai__btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }
.open-in-ai__privacy { font-size: 0.75rem; color: var(--c-text-muted, #6b7280); margin: 0.5rem 0 0; font-style: italic; }
</style>
<script>
function openInAI(initialPrompt) {
    return {
        prompt: initialPrompt || '',
        get hasPrompt() { return this.prompt && this.prompt.trim().length > 0; },
        goTo(provider) {
            if (!this.hasPrompt) return;
            var p = this.prompt;
            if (navigator.clipboard) { navigator.clipboard.writeText(p).catch(function(){}); }
            var maxLen = 2000;
            var encoded = encodeURIComponent(p.length > maxLen ? p.substring(0, maxLen) : p);
            var urls = {
                chatgpt: 'https://chat.openai.com/?q=' + encoded,
                claude: 'https://claude.ai/new?q=' + encoded,
                perplexity: 'https://www.perplexity.ai/?q=' + encoded,
                mistral: 'https://chat.mistral.ai/chat?q=' + encoded,
                copilot: 'https://copilot.microsoft.com/?q=' + encoded,
                gemini: 'https://gemini.google.com/app'
            };
            window.open(urls[provider] || urls.chatgpt, '_blank', 'noopener,noreferrer');
        }
    };
}
</script>
@endonce
