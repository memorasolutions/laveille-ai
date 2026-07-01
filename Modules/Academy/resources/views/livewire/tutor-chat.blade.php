{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{--
    Tuteur IA ancré au cours — panneau flottant bas-droite, Alpine.js toggle.
    WCAG AAA : contraste 7:1, cibles ≥ 24px, aria-live, role=dialog.
    Loi 25 : aucun stockage PII côté client (historique en RAM Livewire seulement).
    Livewire v4 : un seul élément racine — <style> intégré dans le <div> racine via wire:ignore.
--}}
<div
    x-data="{ open: false }"
    style="position: fixed; bottom: 20px; right: 20px; z-index: 9000; font-family: inherit;"
>
    {{-- Animation CSS — dans le div racine (Livewire v4 : un seul élément racine) --}}
    <style wire:ignore>
        @keyframes tutorBounce {
            0%, 80%, 100% { transform: translateY(0); }
            40%            { transform: translateY(-6px); }
        }
    </style>
    {{-- ── Bouton toggle (fermé) ── --}}
    <button
        x-show="! open"
        x-on:click="open = true"
        type="button"
        aria-label="Ouvrir le tuteur IA"
        style="
            width: 56px; height: 56px; border-radius: 50%;
            background: var(--c-primary, #064E5A); color: #fff;
            border: none; cursor: pointer; font-size: 1.4rem;
            box-shadow: 0 4px 16px rgba(6,78,90,0.35);
            display: flex; align-items: center; justify-content: center;
            transition: background 0.15s;
        "
        x-on:mouseover="$el.style.background='#085f6b'"
        x-on:mouseout="$el.style.background='var(--c-primary,#064E5A)'"
    >
        💬
    </button>

    {{-- ── Panneau de chat ── --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity:0; transform:translateY(12px);"
        x-transition:enter-end="opacity:1; transform:translateY(0);"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity:1; transform:translateY(0);"
        x-transition:leave-end="opacity:0; transform:translateY(12px);"
        role="dialog"
        aria-label="Tuteur IA"
        aria-modal="false"
        style="
            width: 360px; height: 520px;
            background: #fff; border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            border: 1px solid #E5E7EB;
            display: flex; flex-direction: column; overflow: hidden;
        "
    >
        {{-- En-tête --}}
        <div style="
            background: var(--c-primary, #064E5A); color: #fff;
            padding: 14px 16px;
            display: flex; align-items: center; justify-content: space-between;
        ">
            <span style="font-weight: 700; font-size: 0.95rem;">🤖 Tuteur IA</span>
            <button
                type="button"
                x-on:click="open = false"
                aria-label="Fermer le tuteur IA"
                style="
                    background: none; border: none; color: #fff; cursor: pointer;
                    font-size: 1.1rem; padding: 4px 6px; border-radius: 6px;
                    min-width: 28px; min-height: 28px;
                "
            >✕</button>
        </div>

        {{-- Zone de messages --}}
        <div
            id="tutor-chat-messages"
            x-ref="messages"
            aria-live="polite"
            aria-atomic="false"
            style="flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 10px;"
        >
            @forelse($messages as $msg)
                @if($msg['role'] === 'user')
                    {{-- Bulle utilisateur : droite, fond primaire --}}
                    <div style="display: flex; justify-content: flex-end;">
                        <div style="
                            background: var(--c-primary, #064E5A); color: #fff;
                            padding: 10px 14px; border-radius: 16px 16px 4px 16px;
                            max-width: 80%; font-size: 0.88rem; line-height: 1.5;
                            word-break: break-word;
                        ">{{ $msg['content'] }}</div>
                    </div>
                @else
                    {{-- Bulle assistant : gauche, fond gris ou rouge si erreur --}}
                    <div style="display: flex; justify-content: flex-start;">
                        <div style="
                            background: {{ ($msg['error'] ?? false) ? '#FEF2F2' : '#F3F4F6' }};
                            color: {{ ($msg['error'] ?? false) ? '#DC2626' : '#374151' }};
                            padding: 10px 14px; border-radius: 16px 16px 16px 4px;
                            max-width: 85%; font-size: 0.88rem; line-height: 1.5;
                            word-break: break-word;
                        ">{{ $msg['content'] }}</div>
                    </div>
                @endif
            @empty
                <div style="
                    text-align: center; color: var(--sys-text-muted, #6B7280);
                    font-size: 0.85rem; margin: auto; padding: 24px 16px;
                ">
                    <div style="font-size: 2rem; margin-bottom: 8px;">🎓</div>
                    Posez votre première question sur la leçon.
                </div>
            @endforelse

            {{-- Indicateur de chargement --}}
            @if($isLoading)
                <div style="display: flex; justify-content: flex-start;">
                    <div style="
                        background: #F3F4F6; padding: 12px 16px;
                        border-radius: 16px 16px 16px 4px;
                        display: flex; gap: 5px; align-items: center;
                    ">
                        @foreach([0, 200, 400] as $delay)
                            <span style="
                                width: 8px; height: 8px; background: #9CA3AF;
                                border-radius: 50%; display: inline-block;
                                animation: tutorBounce 1s {{ $delay }}ms infinite;
                            "></span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Formulaire de saisie --}}
        <form
            wire:submit.prevent="sendQuestion"
            style="border-top: 1px solid #E5E7EB; padding: 12px; background: #fff;"
        >
            <div style="display: flex; gap: 8px; align-items: flex-end;">
                <textarea
                    wire:model.live.blur="question"
                    @disabled($isLoading || $accessBlocked)
                    placeholder="{{ $accessBlocked ? 'Tuteur IA indisponible pour ce cours' : 'Posez votre question sur la leçon...' }}"
                    rows="2"
                    aria-label="Votre question au tuteur"
                    x-ref="tutorInput"
                    x-on:keydown.enter="
                        if (! $event.shiftKey) {
                            $event.preventDefault();
                            $wire.sendQuestion();
                        }
                    "
                    style="
                        flex: 1; padding: 8px 12px; font-size: 0.88rem;
                        border: 1px solid #D1D5DB; border-radius: 10px;
                        resize: none; font-family: inherit; line-height: 1.4;
                        outline: none;
                    "
                    x-on:focus="$el.style.borderColor='var(--c-primary,#064E5A)'"
                    x-on:blur="$el.style.borderColor='#D1D5DB'"
                ></textarea>
                <button
                    type="submit"
                    aria-label="Envoyer la question"
                    @disabled($isLoading || $accessBlocked || strlen(trim($question)) < 3)
                    style="
                        background: var(--c-primary, #064E5A); color: #fff;
                        border: none; border-radius: 10px; padding: 8px 12px;
                        cursor: pointer; font-size: 1rem; min-width: 40px; min-height: 40px;
                        opacity: {{ ($isLoading || $accessBlocked || strlen(trim($question)) < 3) ? '0.5' : '1' }};
                    "
                >→</button>
            </div>

            {{-- Effacer l'historique --}}
            @if(count($messages) > 0)
                <div style="text-align: center; margin-top: 8px;">
                    <button
                        type="button"
                        wire:click="clearHistory"
                        style="
                            background: none; border: none; cursor: pointer;
                            font-size: 0.78rem; color: var(--sys-text-muted, #6B7280);
                            text-decoration: underline; padding: 2px 4px;
                            min-height: 24px;
                        "
                    >Effacer l'historique</button>
                </div>
            @endif

            {{-- Avertissement Loi 25 / portée --}}
            <p style="
                font-size: 0.72rem; color: var(--sys-text-muted, #9CA3AF);
                text-align: center; margin: 6px 0 0; line-height: 1.3;
            ">
                Réponses basées sur le contenu de la leçon uniquement.
            </p>
        </form>
    </div>
</div>

