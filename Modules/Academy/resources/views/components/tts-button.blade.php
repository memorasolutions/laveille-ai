{{--
    Composant : academy::components.tts-button
    Auteur    : MEMORA solutions <info@memora.ca>
    Narration TTS 100% navigateur (Web Speech API) — ZÉRO coût, ZÉRO service tiers.
    Paramètres :
    - $text : texte à lire (déjà strip_tags côté serveur par l'appelant)
--}}
@props(['text' => ''])

@if (trim($text) === '')
    {{-- TTS button skipped: empty text --}}
@else
    <div class="academy-tts"
         x-data="{
             state: 'idle',
             unsupported: typeof window.speechSynthesis === 'undefined',
             utterance: null,
             init() {
                 if (this.unsupported) return;

                 const text = {{ json_encode($text) }};
                 this.utterance = new SpeechSynthesisUtterance(text);
                 this.utterance.lang = 'fr-CA';

                 const setFrenchVoice = () => {
                     const voices = speechSynthesis.getVoices();
                     const frenchVoice = voices.find(voice =>
                         voice.lang && voice.lang.toLowerCase().startsWith('fr')
                     );
                     if (frenchVoice) {
                         this.utterance.voice = frenchVoice;
                     }
                 };

                 // Essai immédiat, puis repli sur l'évènement voiceschanged
                 // (certains navigateurs chargent la liste des voix de façon asynchrone).
                 setFrenchVoice();

                 if (speechSynthesis.getVoices().length === 0) {
                     const onVoicesChanged = () => {
                         setFrenchVoice();
                         speechSynthesis.removeEventListener('voiceschanged', onVoicesChanged);
                     };
                     speechSynthesis.addEventListener('voiceschanged', onVoicesChanged);
                 }

                 this.utterance.onend = () => {
                     this.state = 'idle';
                 };

                 // Échec silencieux : jamais d'alert()/confirm() natif.
                 this.utterance.onerror = () => {
                     this.state = 'idle';
                 };
             },
             start() {
                 if (this.unsupported || !this.utterance) return;
                 speechSynthesis.speak(this.utterance);
                 this.state = 'playing';
             },
             pause() {
                 if (this.unsupported) return;
                 speechSynthesis.pause();
                 this.state = 'paused';
             },
             resume() {
                 if (this.unsupported) return;
                 speechSynthesis.resume();
                 this.state = 'playing';
             },
             stop() {
                 if (this.unsupported) return;
                 speechSynthesis.cancel();
                 this.state = 'idle';
             }
         }"
         x-init="init()"
         x-show="!unsupported"
         style="display: none;"
    >
        <template x-if="state === 'idle'">
            <button
                type="button"
                @click="start()"
                aria-label="Écouter cette leçon en lecture audio"
                class="btn btn-sm academy-tts-btn academy-tts-btn-idle"
                style="min-height: 44px; background-color: var(--sys-action-primary, #064E5A); color: #fff; border: none; padding: 8px 14px; cursor: pointer;"
            >
                🔊 Écouter cette leçon
            </button>
        </template>

        <template x-if="state === 'playing'">
            <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                <button
                    type="button"
                    @click="pause()"
                    aria-label="Mettre en pause la lecture audio"
                    class="btn btn-sm academy-tts-btn"
                    style="min-height: 44px; background-color: var(--sys-action-primary, #064E5A); color: #fff; border: none; padding: 8px 14px; cursor: pointer;"
                >
                    ⏸ Pause
                </button>
                <button
                    type="button"
                    @click="stop()"
                    aria-label="Arrêter la lecture audio"
                    class="btn btn-sm academy-tts-btn"
                    style="min-height: 44px; background-color: #6B7280; color: #fff; border: none; padding: 8px 14px; cursor: pointer;"
                >
                    ⏹ Arrêter
                </button>
                <span class="visually-hidden" aria-live="polite">Lecture audio en cours</span>
            </div>
        </template>

        <template x-if="state === 'paused'">
            <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                <button
                    type="button"
                    @click="resume()"
                    aria-label="Reprendre la lecture audio"
                    class="btn btn-sm academy-tts-btn"
                    style="min-height: 44px; background-color: var(--sys-action-primary, #064E5A); color: #fff; border: none; padding: 8px 14px; cursor: pointer;"
                >
                    ▶ Reprendre
                </button>
                <button
                    type="button"
                    @click="stop()"
                    aria-label="Arrêter la lecture audio"
                    class="btn btn-sm academy-tts-btn"
                    style="min-height: 44px; background-color: #6B7280; color: #fff; border: none; padding: 8px 14px; cursor: pointer;"
                >
                    ⏹ Arrêter
                </button>
                <span class="visually-hidden" aria-live="polite">Lecture en pause</span>
            </div>
        </template>

        <style>
            .academy-tts-btn { font-size: 0.875rem; border-radius: 6px; }

            /* Micro-animation UNIQUEMENT si l'utilisateur n'a pas demandé de réduire les animations. */
            @media (prefers-reduced-motion: no-preference) {
                .academy-tts-btn-idle { animation: academy-tts-pulse 2.4s ease-in-out infinite; }

                @keyframes academy-tts-pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.03); }
                    100% { transform: scale(1); }
                }
            }
        </style>
    </div>
@endif
