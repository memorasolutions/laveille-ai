<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div style="--chatbot-color: {{ $primaryColor }}; --chatbot-color-rgb: {{ implode(',', array_map('hexdec', str_split(ltrim($primaryColor, '#'), 2))) }};">
@if($enabled)
{{-- Floating chat bubble --}}
@if(!$isOpen)
<button
    wire:click="toggleOpen"
    type="button"
    class="ai-chatbot-bubble {{ $position === 'bottom-left' ? 'ai-chatbot-left' : '' }}"
    aria-label="{{ __('Ouvrir le chatbot') }}"
    aria-expanded="false"
    aria-controls="ai-chatbot-panel"
>
    @if($botAvatar)
        <img src="{{ $botAvatar }}" alt="{{ $botName }}" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
    @else
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H5.17L4 17.17V4H20V16Z" fill="white"/>
            <path d="M7 9H9V11H7V9ZM11 9H13V11H11V9ZM15 9H17V11H15V9Z" fill="white"/>
        </svg>
    @endif
</button>
@endif

{{-- Chat panel --}}
@if($isOpen)
<div
    id="ai-chatbot-panel"
    class="ai-chatbot-panel {{ $position === 'bottom-left' ? 'ai-chatbot-left' : '' }}"
    role="dialog"
    aria-label="{{ $botName }}"
    x-data
    @keydown.escape.window="$wire.toggleOpen()"
>
    {{-- Header --}}
    <div class="ai-chatbot-header">
        @if($botAvatar)
            <img src="{{ $botAvatar }}" alt="{{ $botName }}" style="width:28px;height:28px;border-radius:50%;object-fit:cover;margin-right:8px;">
        @endif
        <span class="ai-chatbot-title">{{ $botName }}</span>
        <div class="ai-chatbot-header-actions">
            <button
                wire:click="clearConversation"
                type="button"
                class="ai-chatbot-btn"
                aria-label="{{ __('Effacer la conversation') }}"
                title="{{ __('Effacer la conversation') }}"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 19C6 20.1 6.9 21 8 21H16C17.1 21 18 20.1 18 19V7H6V19ZM19 4H15.5L14.5 3H9.5L8.5 4H5V6H19V4Z" fill="white"/>
                </svg>
            </button>
            <button
                wire:click="toggleOpen"
                type="button"
                class="ai-chatbot-btn"
                aria-label="{{ __('Fermer le chatbot') }}"
            >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="white"/>
                </svg>
            </button>
        </div>
    </div>

    {{-- Handoff status banner --}}
    @if($conversationStatus === 'waiting_human')
    <div class="ai-chatbot-status-banner ai-chatbot-status-waiting">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67V7z" fill="currentColor"/></svg>
        {{ __('En attente d\'un agent...') }}
    </div>
    @elseif($conversationStatus === 'human_active')
    <div class="ai-chatbot-status-banner ai-chatbot-status-active">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="currentColor"/></svg>
        {{ __('Un agent est connecté') }}
    </div>
    @endif

    {{-- Messages area --}}
    <div
        class="ai-chatbot-messages"
        role="log"
        aria-live="polite"
        aria-label="{{ __('Messages du chatbot') }}"
        @if(in_array($conversationStatus, ['waiting_human', 'human_active']))
            wire:poll.3s="pollAgentMessages"
        @endif
        x-init="
            const el = $el;
            const scroll = () => el.scrollTop = el.scrollHeight;
            scroll();
            new MutationObserver(scroll).observe(el, { childList: true, subtree: true, characterData: true });
        "
    >
        @if(count($messages) === 0 && $streamedResponse === '')
            <div class="ai-chatbot-empty">
                <p>{{ $welcomeMessage }}</p>
            </div>
            @if(count($suggestions) > 0)
            <div class="ai-chatbot-suggestions">
                @foreach($suggestions as $suggestion)
                    <button
                        wire:click="sendSuggestion('{{ addslashes($suggestion) }}')"
                        type="button"
                        class="ai-chatbot-suggestion-pill"
                    >{{ $suggestion }}</button>
                @endforeach
            </div>
            @endif
        @endif

        @foreach($messages as $msg)
            <div class="ai-chatbot-msg ai-chatbot-msg-{{ $msg['role'] }}">
                <div class="ai-chatbot-msg-content">
                    {!! nl2br(e($msg['content'])) !!}
                </div>
            </div>
        @endforeach

        {{-- Streaming response --}}
        @if($streamedResponse)
            <div class="ai-chatbot-msg ai-chatbot-msg-assistant">
                <div class="ai-chatbot-msg-content" wire:stream="chatbot-response">
                    {!! nl2br(e($streamedResponse)) !!}
                </div>
            </div>
        @endif

        {{-- Loading indicator --}}
        @if($isLoading && !$streamedResponse)
            <div class="ai-chatbot-msg ai-chatbot-msg-assistant">
                <div class="ai-chatbot-typing">
                    <span></span><span></span><span></span>
                </div>
            </div>
        @endif

        {{-- Error --}}
        @if($error)
            <div class="ai-chatbot-error" role="alert">
                {{ $error }}
            </div>
        @endif
    </div>

    {{-- Action buttons --}}
    <div class="ai-chatbot-actions-wrap">
        @if($leadMode)
            <button wire:click="cancelLeadCapture" type="button" class="ai-chatbot-action-btn ai-chatbot-cancel-btn" @if($isLoading) disabled @endif>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M19 6.41L17.59 5L12 10.59L6.41 5L5 6.41L10.59 12L5 17.59L6.41 19L12 13.41L17.59 19L19 17.59L13.41 12L19 6.41Z" fill="currentColor"/></svg>
                {{ __('Annuler') }}
            </button>
        @else
            <button wire:click="startLeadCapture" type="button" class="ai-chatbot-action-btn ai-chatbot-contact-btn" @if($isLoading) disabled @endif>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" fill="currentColor"/></svg>
                {{ __('Être contacté') }}
            </button>
            @auth
            <button wire:click="requestHuman" type="button" class="ai-chatbot-action-btn ai-chatbot-human-btn" @if($isLoading) disabled @endif>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="currentColor"/></svg>
                {{ __('Parler à un humain') }}
            </button>
            @endauth
        @endif
    </div>

    {{-- Input area --}}
    <form wire:submit="sendMessage" class="ai-chatbot-input-area">
        <label for="ai-chatbot-input" class="visually-hidden">{{ __('Votre message') }}</label>
        <textarea
            id="ai-chatbot-input"
            wire:model.live.debounce.150ms="message"
            class="ai-chatbot-input"
            placeholder="{{ $leadMode ? match($leadStep) { 1 => __('Votre nom complet...'), 2 => __('votre@courriel.com'), 3 => __('514-555-1234 ou « passer »'), 4 => __('Décrivez votre besoin...'), default => __('Écrivez votre message...') } : __('Écrivez votre message...') }}"
            autocomplete="off"
            maxlength="1000"
            rows="1"
            @if($isLoading) disabled @endif
            x-data
            x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 96) + 'px'"
            x-on:keydown.enter="event.preventDefault(); if (!event.shiftKey) { $wire.sendMessage(); } else { const p = $el.selectionStart; $el.value = $el.value.substring(0, p) + '\n' + $el.value.substring(p); $el.selectionStart = $el.selectionEnd = p + 1; $el.dispatchEvent(new Event('input')); }"
        ></textarea>
        <button
            type="submit"
            class="ai-chatbot-send"
            aria-label="{{ __('Envoyer') }}"
            @if($isLoading) disabled @endif
        >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.01 21L23 12L2.01 3L2 10L17 12L2 14L2.01 21Z" fill="currentColor"/>
            </svg>
        </button>
    </form>
</div>
@endif

{{-- localStorage persistence for guest conversations --}}
@if(isset($isGuest) && $isGuest)
<script>
document.addEventListener('livewire:init', () => {
    const key = 'ai_chatbot_messages';
    const comp = Livewire.getByName('ai-chatbot')[0];
    if (!comp) return;

    // Restore from localStorage on mount
    try {
        const stored = JSON.parse(localStorage.getItem(key) || '[]');
        if (stored.length > 0 && comp.$wire.messages.length === 0) {
            comp.$wire.restoreMessages(stored);
        }
    } catch (e) {}

    // Save to localStorage on every Livewire update
    Livewire.hook('morph.updated', () => {
        try {
            const msgs = comp.$wire.messages;
            if (msgs && msgs.length > 0) {
                localStorage.setItem(key, JSON.stringify(msgs));
            }
        } catch (e) {}
    });

    // Clear localStorage when conversation is cleared
    comp.$wire.on('conversation-cleared', () => {
        localStorage.removeItem(key);
    });
});
</script>
@endif

<style>
.ai-chatbot-bubble {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--chatbot-color);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(var(--chatbot-color-rgb), 0.4);
    z-index: 9999;
    transition: transform 0.2s, box-shadow 0.2s;
}
.ai-chatbot-bubble:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 16px rgba(var(--chatbot-color-rgb), 0.5);
}
.ai-chatbot-bubble:focus-visible {
    outline: 3px solid var(--chatbot-color);
    outline-offset: 3px;
}

.ai-chatbot-left { right: auto; left: 24px; }
.ai-chatbot-panel {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 350px;
    height: 500px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: column;
    z-index: 9999;
    overflow: hidden;
}

.ai-chatbot-header {
    background: var(--chatbot-color);
    color: #fff;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.ai-chatbot-title {
    font-weight: 600;
    font-size: 15px;
}
.ai-chatbot-header-actions {
    display: flex;
    gap: 8px;
}
.ai-chatbot-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    transition: background 0.15s;
}
.ai-chatbot-btn:hover {
    background: rgba(255,255,255,0.2);
}
.ai-chatbot-btn:focus-visible {
    outline: 2px solid #fff;
    outline-offset: 2px;
}

.ai-chatbot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ai-chatbot-empty {
    text-align: center;
    color: #6c757d;
    padding: 40px 16px;
    font-size: 14px;
}
.ai-chatbot-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 0 12px 8px;
    justify-content: center;
}
.ai-chatbot-suggestion-pill {
    background: #e8eefb;
    color: var(--chatbot-color);
    border: 1px solid #b0c4ff;
    border-radius: 16px;
    padding: 6px 14px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}
.ai-chatbot-suggestion-pill:hover {
    background: var(--chatbot-color);
    color: #fff;
    border-color: var(--chatbot-color);
}
[data-theme="dark"] .ai-chatbot-suggestion-pill {
    background: #2a2a3e;
    color: #7ea8ff;
    border-color: #3a4a6e;
}
[data-theme="dark"] .ai-chatbot-suggestion-pill:hover {
    background: var(--chatbot-color);
    color: #fff;
}

.ai-chatbot-msg {
    display: flex;
    max-width: 85%;
}
.ai-chatbot-msg-user {
    align-self: flex-end;
}
.ai-chatbot-msg-assistant {
    align-self: flex-start;
}
.ai-chatbot-msg-content {
    padding: 8px 12px;
    border-radius: 12px;
    font-size: 13px;
    line-height: 1.5;
    word-break: break-word;
}
.ai-chatbot-msg-user .ai-chatbot-msg-content {
    background: var(--chatbot-color);
    color: #fff;
    border-bottom-right-radius: 4px;
}
.ai-chatbot-msg-assistant .ai-chatbot-msg-content {
    background: #f0f2f5;
    color: #1a1a2e;
    border-bottom-left-radius: 4px;
}

.ai-chatbot-typing {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 10px 14px;
    background: #f0f2f5;
    border-radius: 12px;
    border-bottom-left-radius: 4px;
}
.ai-chatbot-typing span {
    width: 6px;
    height: 6px;
    background: #999;
    border-radius: 50%;
    animation: ai-chatbot-bounce 1.2s infinite;
}
.ai-chatbot-typing span:nth-child(2) { animation-delay: 0.2s; }
.ai-chatbot-typing span:nth-child(3) { animation-delay: 0.4s; }
@keyframes ai-chatbot-bounce {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-6px); }
}

.ai-chatbot-msg-agent {
    align-self: flex-start;
}
.ai-chatbot-msg-agent .ai-chatbot-msg-content {
    background: #d4edda;
    color: #155724;
    border-bottom-left-radius: 4px;
}
.ai-chatbot-status-banner {
    padding: 6px 12px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
    justify-content: center;
    flex-shrink: 0;
}
.ai-chatbot-status-waiting {
    background: #fff3cd;
    color: #856404;
}
.ai-chatbot-status-active {
    background: #d4edda;
    color: #155724;
}
.ai-chatbot-actions-wrap {
    padding: 4px 12px;
    text-align: center;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: center;
    gap: 8px;
}
.ai-chatbot-action-btn {
    background: none;
    border: 1px solid #6c757d;
    border-radius: 16px;
    padding: 4px 12px;
    font-size: 12px;
    color: #6c757d;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.15s;
}
.ai-chatbot-action-btn:hover {
    background: #6c757d;
    color: #fff;
}
.ai-chatbot-contact-btn {
    border-color: var(--chatbot-color);
    color: var(--chatbot-color);
}
.ai-chatbot-contact-btn:hover {
    background: var(--chatbot-color);
    color: #fff;
}
.ai-chatbot-cancel-btn {
    border-color: #dc3545;
    color: #dc3545;
}
.ai-chatbot-cancel-btn:hover {
    background: #dc3545;
    color: #fff;
}

.ai-chatbot-error {
    background: #fde8e8;
    color: #c62828;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 13px;
    text-align: center;
}

.ai-chatbot-input-area {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    padding: 10px 12px;
    border-top: 1px solid #e5e7eb;
    flex-shrink: 0;
}
.ai-chatbot-input {
    flex: 1;
    border: 1px solid #d1d5db;
    border-radius: 20px;
    padding: 8px 14px;
    font-size: 13px;
    outline: none;
    transition: border-color 0.15s;
    resize: none;
    overflow-y: auto;
    max-height: 96px;
    min-height: 36px;
    line-height: 1.4;
    font-family: inherit;
}
.ai-chatbot-input:focus {
    border-color: var(--chatbot-color);
    box-shadow: 0 0 0 2px rgba(var(--chatbot-color-rgb), 0.2);
}
.ai-chatbot-input:disabled {
    background: #f9fafb;
}
.ai-chatbot-send {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--chatbot-color);
    border: none;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: background 0.15s;
}
.ai-chatbot-send:hover {
    background: #3a6fd8;
}
.ai-chatbot-send:disabled {
    background: #b0c4ff;
    cursor: not-allowed;
}
.ai-chatbot-send:focus-visible {
    outline: 3px solid var(--chatbot-color);
    outline-offset: 3px;
}

/* Mobile responsive */
@media (max-width: 576px) {
    .ai-chatbot-bubble {
        bottom: 90px;
    }
    .ai-chatbot-panel {
        bottom: 0;
        right: 0;
        width: 100vw;
        height: 100vh;
        border-radius: 0;
    }
}

/* Dark mode support */
[data-theme="dark"] .ai-chatbot-panel {
    background: #1e1e2e;
}
[data-theme="dark"] .ai-chatbot-msg-assistant .ai-chatbot-msg-content {
    background: #2a2a3e;
    color: #e0e0e0;
}
[data-theme="dark"] .ai-chatbot-input {
    background: #2a2a3e;
    border-color: #3a3a4e;
    color: #e0e0e0;
}
[data-theme="dark"] .ai-chatbot-input-area {
    border-top-color: #3a3a4e;
}
[data-theme="dark"] .ai-chatbot-empty {
    color: #9ca3af;
}
[data-theme="dark"] .ai-chatbot-typing {
    background: #2a2a3e;
}
[data-theme="dark"] .ai-chatbot-typing span {
    background: #6b7280;
}
[data-theme="dark"] .ai-chatbot-msg-agent .ai-chatbot-msg-content {
    background: #1a3a2a;
    color: #a8d8b8;
}
[data-theme="dark"] .ai-chatbot-status-waiting {
    background: #3a3020;
    color: #d4a843;
}
[data-theme="dark"] .ai-chatbot-status-active {
    background: #1a3a2a;
    color: #a8d8b8;
}
</style>
@endif
</div>
