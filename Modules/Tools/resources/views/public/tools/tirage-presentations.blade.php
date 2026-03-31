<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends(fronttheme_layout())
@section('title', $tool->name . ' - ' . config('app.name'))
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => $tool->name, 'breadcrumbItems' => [__('Outils'), $tool->name]])
@endsection
@push('styles')
<style>
.timer-bar { height: 8px; border-radius: 4px; background: #e9ecef; overflow: hidden; margin-bottom: 1rem; }
.timer-bar-fill { height: 100%; border-radius: 4px; transition: width 1s linear; }
.timer-display { font-family: 'Courier New', monospace; font-size: 2.5rem; font-weight: 800; letter-spacing: 2px; }
.draw-card-enter { animation: cardFlip 0.6s ease-out; }
@keyframes cardFlip {
    0% { opacity: 0; transform: rotateY(90deg) scale(0.8); }
    60% { transform: rotateY(-10deg) scale(1.05); }
    100% { opacity: 1; transform: rotateY(0) scale(1); }
}
.fullscreen-overlay {
    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    z-index: 9999; display: flex; flex-direction: column; align-items: center; justify-content: center;
    color: #fff; padding: 2rem;
}
.fullscreen-overlay .fs-student { font-size: 4rem; font-weight: 800; margin-bottom: 1rem; }
.fullscreen-overlay .fs-question { font-size: 2rem; font-style: italic; opacity: 0.85; }
.fullscreen-overlay .fs-timer { font-size: 5rem; font-weight: 800; font-family: 'Courier New', monospace; }
.fullscreen-overlay .fs-bar { width: 60%; height: 12px; border-radius: 6px; background: rgba(255,255,255,0.2); margin-top: 1rem; }
.fullscreen-overlay .fs-bar-fill { height: 100%; border-radius: 6px; transition: width 1s linear; }
.confetti-piece {
    position: fixed; width: 10px; height: 10px; border-radius: 2px;
    pointer-events: none; z-index: 10000;
    animation: confetti-fall 1.5s ease-out forwards;
}
@keyframes confetti-fall {
    0% { opacity: 1; transform: translateY(0) rotate(0deg) scale(1); }
    100% { opacity: 0; transform: translateY(300px) rotate(720deg) scale(0.3); }
}
</style>
@endpush
@section('content')
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-12">
                <div class="card shadow-sm" style="border-radius: var(--r-base);">
                    <div class="card-body p-4 p-md-5" x-data="presOrder()" x-init="initEditMode()">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h1 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-dark); margin: 0;">{{ $tool->name }}</h1>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm" :class="soundEnabled ? 'btn-outline-secondary' : 'btn-outline-danger'" @click="toggleSound()" :title="soundEnabled ? '{{ __('Son actif') }}' : '{{ __('Son muet') }}'" style="border-radius: var(--r-btn);">
                                    <span x-text="soundEnabled ? '🔊' : '🔇'"></span>
                                </button>
                            </div>
                        </div>
                        <p class="text-muted mb-4">{{ __('Entrez vos apprenants et questions, puis tirez au sort.') }}</p>

                        {{-- Barre sauvegarde (connectés) --}}
                        <div x-show="isAuthenticated" x-cloak style="background: rgba(11,114,133,0.04); border: 1px solid rgba(11,114,133,0.12); border-radius: 10px; padding: 12px; margin-bottom: 16px;">
                            <div class="d-flex gap-2 align-items-center">
                                <input type="text" class="form-control form-control-sm flex-fill" x-model="saveName" placeholder="{{ __('Nommer cette configuration...') }}" aria-label="{{ __('Nom de la configuration') }}" style="border-radius: 8px;">
                                <button class="btn btn-sm" @click="saveToAccount()" :disabled="studentList.length < 1 || saving" style="background: var(--c-primary); color: #fff; border-radius: 8px; font-weight: 600; white-space: nowrap; padding: 6px 16px;"
                                        x-text="saving ? '{{ __('Sauvegarde...') }}' : (_editingId ? '{{ __('Mettre à jour') }}' : '{{ __('Sauvegarder') }}')"></button>
                            </div>
                            <div class="small mt-2" style="font-size: 0.8rem; color: var(--c-text-muted);">
                                {{ __('Retrouvez vos configurations dans') }} <a href="{{ route('user.saved') }}?tab=draw-configs" style="color: var(--c-primary); text-decoration: underline;">{{ __('vos sauvegardes') }}</a>.
                            </div>
                            <template x-if="saveError">
                                <div class="alert alert-danger small p-1 mt-2 mb-0" style="font-size: 0.8rem; border-radius: 6px;" x-text="saveError"></div>
                            </template>
                        </div>
                        {{-- Bandeau visiteurs --}}
                        <div x-show="!isAuthenticated" x-cloak style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 0.85rem; color: #0369a1;">
                            {{ __('Connectez-vous pour sauvegarder vos configurations dans votre compte.') }}
                        </div>

                        {{-- Listes cote a cote --}}
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">{{ __('Apprenants') }}</label>
                                <textarea class="form-control" rows="6" x-model="names" @input="saveLists()" aria-label="Liste des apprenants" placeholder="{{ __("Marie Dubois\nJean Martin\nSophie Tremblay") }}"></textarea>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-medium">{{ __('Questions / sujets') }}</label>
                                <textarea class="form-control" rows="6" x-model="questions" @input="saveLists()" aria-label="Liste des questions" placeholder="{{ __("Presentez votre parcours\nQuelles sont vos attentes ?\nPartagez un projet") }}"></textarea>
                            </div>
                        </div>

                        {{-- Compteurs --}}
                        <div class="d-flex justify-content-center gap-4 mb-3" style="font-size: 0.9rem;">
                            <span style="color: var(--c-primary); font-weight: 600;" x-text="availableStudents.length + ' {{ __('apprenant(s) restant(s)') }}'"></span>
                            <span>&bull;</span>
                            <span style="color: #D97706; font-weight: 600;" x-text="availableQuestions.length + ' {{ __('question(s) restante(s)') }}'"></span>
                        </div>

                        {{-- Minuteur config --}}
                        <div class="mb-3 p-3 rounded" style="background: #f8f9fa;" x-show="timerEnabled">
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <label class="form-label mb-0 fw-medium" style="white-space: nowrap;">{{ __('Minuteur') }} :</label>
                                <input type="range" class="form-range" x-model.number="timerMinutes" aria-label="Durée du minuteur" min="1" max="15" step="1" style="max-width: 150px;" :disabled="timerRunning">
                                <span class="badge" style="background: var(--c-dark); color: #fff; font-size: 0.9rem;" x-text="timerMinutes + ' min'"></span>
                                <template x-if="timerRunning">
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-secondary" @click="pauseTimer()" x-text="timerPaused ? '{{ __('Reprendre') }}' : '{{ __('Pause') }}'" style="border-radius: var(--r-btn);"></button>
                                        <button class="btn btn-sm btn-outline-danger" @click="stopTimer()" style="border-radius: var(--r-btn);">{{ __('Arrêter') }}</button>
                                    </div>
                                </template>
                            </div>
                            <template x-if="timerRunning">
                                <div class="mt-2">
                                    <div class="timer-bar">
                                        <div class="timer-bar-fill" :style="'width:' + timerProgress + '%; background:' + (timerProgress > 20 ? '#059669' : timerProgress > 5 ? '#F59E0B' : '#DC2626')"></div>
                                    </div>
                                    <div class="text-center">
                                        <span class="timer-display" :style="'color:' + (timerProgress > 20 ? 'var(--c-dark)' : timerProgress > 5 ? '#D97706' : '#DC2626')" x-text="timerDisplay"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Gros bouton tirer --}}
                        <button class="btn w-100 mb-4" @click="drawOne()" :disabled="!canDraw"
                                style="background: var(--c-accent); color: #fff; border-radius: var(--r-btn); font-family: var(--f-heading); font-weight: 700; font-size: 1.2rem; padding: 14px;">
                            {{ __('Tirer au sort') }}
                        </button>

                        {{-- Resultat du tirage --}}
                        <template x-if="currentDraw">
                            <div class="text-center p-4 rounded mb-4 draw-card-enter" style="background: var(--c-accent-light, #FDF5ED); border: 2px solid var(--c-accent);">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span class="badge mb-2" style="background: var(--c-dark); color: #fff;" x-text="'#' + drawCount"></span>
                                    <button class="btn btn-sm btn-outline-secondary" @click="enterFullscreen()" title="{{ __('Plein écran') }}" style="border-radius: var(--r-btn);">
                                        <span x-text="isFullscreen ? '✕' : '⛶'"></span> {{ __('Plein écran') }}
                                    </button>
                                </div>
                                <h2 style="font-family: var(--f-heading); font-weight: 800; color: var(--c-accent); margin: 0.5rem 0;" x-text="currentDraw.student"></h2>
                                <div style="font-size: 1.5rem; color: #999;">&rarr;</div>
                                <div class="p-2 rounded" style="background: var(--c-primary-light); color: var(--c-primary); font-style: italic; font-size: 1.1rem;" x-text="currentDraw.question"></div>
                            </div>
                        </template>

                        {{-- Fullscreen overlay --}}
                        <template x-if="isFullscreen && currentDraw">
                            <div class="fullscreen-overlay" @click.self="exitFullscreen()" @keydown.escape.window="exitFullscreen()">
                                <button class="btn btn-sm" @click="exitFullscreen()" style="position: absolute; top: 1rem; right: 1rem; background: rgba(255,255,255,0.2); color: #fff; border-radius: var(--r-btn); font-size: 1.2rem;">✕ {{ __('Fermer') }}</button>
                                <span class="badge mb-3" style="background: rgba(255,255,255,0.2); color: #fff; font-size: 1rem;" x-text="'#' + drawCount"></span>
                                <div class="fs-student" x-text="currentDraw.student" style="font-family: var(--f-heading);"></div>
                                <div style="font-size: 3rem; opacity: 0.5; margin: 0.5rem 0;">&rarr;</div>
                                <div class="fs-question" x-text="currentDraw.question"></div>
                                <template x-if="timerRunning">
                                    <div class="mt-4 text-center" style="width: 100%;">
                                        <div class="fs-timer" :style="'color:' + (timerProgress > 20 ? '#10b981' : timerProgress > 5 ? '#f59e0b' : '#ef4444')" x-text="timerDisplay"></div>
                                        <div class="fs-bar" style="margin: 0.5rem auto;">
                                            <div class="fs-bar-fill" :style="'width:' + timerProgress + '%; background:' + (timerProgress > 20 ? '#10b981' : timerProgress > 5 ? '#f59e0b' : '#ef4444')"></div>
                                        </div>
                                        <div class="d-flex justify-content-center gap-2 mt-3">
                                            <button class="btn" @click="pauseTimer()" style="background: rgba(255,255,255,0.2); color: #fff; border-radius: var(--r-btn);" x-text="timerPaused ? '{{ __('Reprendre') }}' : '{{ __('Pause') }}'"></button>
                                            <button class="btn" @click="stopTimer()" style="background: rgba(255,255,255,0.2); color: #fff; border-radius: var(--r-btn);">{{ __('Arrêter') }}</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Message quand plus rien a tirer --}}
                        <div class="text-center p-3 rounded mb-3" x-show="!canDraw && drawCount > 0" style="background: #D1FAE5; border: 2px solid #059669;">
                            <strong style="color: #059669;">{{ __('Tous les tirages sont complétés !') }}</strong>
                        </div>

                        {{-- Historique --}}
                        <template x-if="history.length > 0">
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 style="font-family: var(--f-heading); font-weight: 700; margin: 0;">{{ __('Historique') }} (<span x-text="history.length"></span>)</h6>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-secondary" @click="exportHistory()" style="font-size: 0.7rem;">{{ __('Exporter .txt') }}</button>
                                        <button class="btn btn-sm btn-outline-secondary" @click="printHistory()" style="font-size: 0.7rem;">{{ __('Imprimer') }}</button>
                                        <button class="btn btn-sm btn-outline-danger" @click="clearAll()" style="font-size: 0.7rem;">{{ __('Effacer') }}</button>
                                    </div>
                                </div>
                                <template x-for="(d, i) in history" :key="i">
                                    <div class="d-flex justify-content-between p-2 mb-1 rounded" style="background: #f8f9fa; font-size: 0.85rem;">
                                        <span><strong x-text="'#' + (history.length - i)"></strong> <span x-text="d.student"></span> &rarr; <em x-text="d.question"></em></span>
                                        <small class="text-muted" x-text="d.time"></small>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Options avancées --}}
                        <details class="mb-3">
                            <summary style="cursor: pointer; font-family: var(--f-heading); font-weight: 600; color: var(--c-dark);">{{ __('Options avancées') }}</summary>
                            <div class="mt-3 p-3 rounded" style="background: #f8f9fa;">
                                <label class="mb-2 d-flex align-items-center gap-2" style="cursor: pointer;">
                                    <input type="checkbox" x-model="removeStudent" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                    <span style="font-size: 0.9rem;">{{ __('Retirer l\'apprenant après tirage') }}</span>
                                </label>
                                <label class="mb-2 d-flex align-items-center gap-2" style="cursor: pointer;">
                                    <input type="checkbox" x-model="removeQuestion" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                    <span style="font-size: 0.9rem;">{{ __('Retirer la question après tirage') }}</span>
                                </label>
                                <label class="mb-3 d-flex align-items-center gap-2" style="cursor: pointer;">
                                    <input type="checkbox" x-model="timerEnabled" @change="localStorage.setItem('tp_timer', timerEnabled)" style="display:inline-block !important; width:18px; height:18px; accent-color: var(--c-primary); margin: 0; flex-shrink: 0;">
                                    <span style="font-size: 0.9rem;">{{ __('Activer le minuteur par présentation') }}</span>
                                </label>
                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <button class="btn btn-sm btn-outline-secondary" @click="resetDrawn()" style="border-radius: var(--r-btn);">{{ __('Réinitialiser les listes') }}</button>
                                    <label style="cursor: pointer;">
                                        <input type="file" accept=".txt" @change="importFile($event, 'names')" style="display:none" x-ref="impNames">
                                        <span class="btn btn-sm btn-outline-secondary" @click="$refs.impNames.click()" style="border-radius: var(--r-btn);">{{ __('Importer apprenants') }}</span>
                                    </label>
                                    <label style="cursor: pointer;">
                                        <input type="file" accept=".txt" @change="importFile($event, 'questions')" style="display:none" x-ref="impQuestions">
                                        <span class="btn btn-sm btn-outline-secondary" @click="$refs.impQuestions.click()" style="border-radius: var(--r-btn);">{{ __('Importer questions') }}</span>
                                    </label>
                                </div>
                                <button class="btn btn-sm w-100" @click="generateFullOrder()" :disabled="studentList.length < 2"
                                        style="background: var(--c-primary); color: #fff; border-radius: var(--r-btn);">
                                    {{ __('Générer l\'ordre complet des présentations') }}
                                </button>
                            </div>
                        </details>

                        {{-- Ordre complet (si genere) --}}
                        <template x-if="fullOrder.length > 0">
                            <div class="p-3 rounded mb-3" style="background: var(--c-primary-light);">
                                <h6 style="font-family: var(--f-heading); font-weight: 700;">{{ __('Ordre complet') }}</h6>
                                <ol style="font-size: 0.9rem; padding-left: 1.5rem; margin-bottom: 0.5rem;">
                                    <template x-for="(item, i) in fullOrder" :key="i">
                                        <li class="mb-1"><span x-text="item.student"></span> &rarr; <em x-text="item.question"></em></li>
                                    </template>
                                </ol>
                                <button class="btn btn-sm" @click="copyFullOrder()" style="background: var(--c-dark); color: #fff; border-radius: var(--r-btn);" x-text="copied ? '{{ __('Copié !') }}' : '{{ __('Copier') }}'"></button>
                            </div>
                        </template>

                        {{-- Loi 25 --}}
                        <details style="font-size: 0.75rem; color: #999;">
                            <summary style="cursor: pointer;">{{ __('Protection de vos données (Loi 25)') }}</summary>
                            <ul class="mt-2 mb-0" style="padding-left: 1.2rem;">
                                <li x-show="isAuthenticated">{{ __('Données sauvegardées dans votre compte, protégées et supprimables à tout moment.') }}</li>
                                <li x-show="!isAuthenticated">{{ __('Stockage local uniquement (navigateur) — jamais envoyé à un serveur.') }}</li>
                                <li>{{ __('Aucune transmission à des tiers.') }}</li>
                                <li>{{ __('Suppression facile via le bouton « Effacer » ou votre espace personnel.') }}</li>
                            </ul>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    Alpine.data('presOrder', function() {
        return {
            names: localStorage.getItem('tp_names') || '',
            questions: localStorage.getItem('tp_questions') || '',
            removeStudent: true,
            removeQuestion: true,
            drawnStudents: [],
            drawnQuestions: [],
            history: [],
            currentDraw: null,
            drawCount: 0,
            fullOrder: [],
            copied: false,

            // Timer
            timerEnabled: localStorage.getItem('tp_timer') === 'true',
            timerMinutes: 5,
            timerSeconds: 0,
            timerRunning: false,
            timerPaused: false,
            timerInterval: null,

            // Fullscreen
            isFullscreen: false,

            // Sauvegarde compte
            isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
            saveName: '',
            saving: false,
            saveError: '',
            _editingId: null,

            // Sound
            soundEnabled: localStorage.getItem('tp_sound') !== 'false',

            // Confetti colors
            confettiColors: ['#0B7285', '#E67E22', '#6366f1', '#10b981', '#ef4444', '#8b5cf6', '#f59e0b', '#06b6d4'],

            get studentList() { return this.names.split('\n').map(function(n) { return n.trim(); }).filter(function(n) { return n; }); },
            get questionsList() { return this.questions.split('\n').map(function(q) { return q.trim(); }).filter(function(q) { return q; }); },

            get availableStudents() {
                var drawn = this.drawnStudents;
                var all = this.studentList;
                if (!this.removeStudent) return all;
                return all.filter(function(s) { return drawn.indexOf(s) === -1; });
            },
            get availableQuestions() {
                var drawn = this.drawnQuestions;
                var all = this.questionsList;
                if (!this.removeQuestion) return all;
                return all.filter(function(q) { return drawn.indexOf(q) === -1; });
            },
            get canDraw() { return this.availableStudents.length > 0 && this.availableQuestions.length > 0; },

            // Timer computed
            get timerDisplay() {
                var m = Math.floor(this.timerSeconds / 60);
                var s = this.timerSeconds % 60;
                return (m < 10 ? '0' + m : m) + ':' + (s < 10 ? '0' + s : s);
            },
            get timerProgress() {
                var total = this.timerMinutes * 60;
                if (total === 0) return 0;
                return (this.timerSeconds / total) * 100;
            },

            secureRandom: function(max) { var a = new Uint32Array(1); crypto.getRandomValues(a); return a[0] % max; },

            drawOne: function() {
                if (!this.canDraw) return;
                var si = this.secureRandom(this.availableStudents.length);
                var qi = this.secureRandom(this.availableQuestions.length);
                var student = this.availableStudents[si];
                var question = this.availableQuestions[qi];
                this.currentDraw = { student: student, question: question, time: new Date().toLocaleTimeString('fr-CA') };
                this.drawCount++;
                this.history.unshift({ student: student, question: question, time: this.currentDraw.time });
                this.drawnStudents.push(student);
                this.drawnQuestions.push(question);
                this.playDrawSound();
                this.confetti();
                if (this.timerEnabled) { this.startTimer(); }
            },

            // Timer methods
            startTimer: function() {
                var self = this;
                if (self.timerInterval) clearInterval(self.timerInterval);
                self.timerSeconds = self.timerMinutes * 60;
                self.timerRunning = true;
                self.timerPaused = false;
                self.timerInterval = setInterval(function() {
                    if (!self.timerPaused && self.timerSeconds > 0) {
                        self.timerSeconds--;
                        if (self.timerSeconds === 0) {
                            clearInterval(self.timerInterval);
                            self.timerRunning = false;
                            self.playTimerEndSound();
                        }
                    }
                }, 1000);
            },
            pauseTimer: function() {
                this.timerPaused = !this.timerPaused;
            },
            stopTimer: function() {
                if (this.timerInterval) clearInterval(this.timerInterval);
                this.timerRunning = false;
                this.timerPaused = false;
                this.timerSeconds = 0;
            },

            // Fullscreen
            enterFullscreen: function() { this.isFullscreen = true; },
            exitFullscreen: function() { this.isFullscreen = false; },

            // Sound
            toggleSound: function() {
                this.soundEnabled = !this.soundEnabled;
                localStorage.setItem('tp_sound', this.soundEnabled);
            },
            playBeep: function(frequency, duration) {
                if (!this.soundEnabled) return;
                try {
                    var ctx = new (window.AudioContext || window.webkitAudioContext)();
                    var osc = ctx.createOscillator();
                    var gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'sine';
                    osc.frequency.value = frequency;
                    gain.gain.value = 0.15;
                    osc.start();
                    osc.stop(ctx.currentTime + (duration / 1000));
                } catch(e) {}
            },
            playDrawSound: function() {
                var self = this;
                self.playBeep(660, 80);
                setTimeout(function() { self.playBeep(880, 80); }, 100);
                setTimeout(function() { self.playBeep(1100, 120); }, 200);
            },
            playTimerEndSound: function() {
                var self = this;
                self.playBeep(880, 150);
                setTimeout(function() { self.playBeep(880, 150); }, 300);
                setTimeout(function() { self.playBeep(880, 150); }, 600);
                setTimeout(function() { self.playBeep(1100, 300); }, 900);
            },

            // Confetti
            confetti: function() {
                var colors = this.confettiColors;
                for (var i = 0; i < 35; i++) {
                    var el = document.createElement('div');
                    el.className = 'confetti-piece';
                    el.style.backgroundColor = colors[i % colors.length];
                    el.style.left = (30 + Math.random() * 40) + 'vw';
                    el.style.top = (10 + Math.random() * 30) + 'vh';
                    el.style.animationDelay = (Math.random() * 0.4) + 's';
                    el.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
                    document.body.appendChild(el);
                    setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 2000);
                }
            },

            clearAll: function() { this.history = []; this.drawnStudents = []; this.drawnQuestions = []; this.currentDraw = null; this.drawCount = 0; this.fullOrder = []; this.stopTimer(); },
            resetDrawn: function() { this.drawnStudents = []; this.drawnQuestions = []; },
            saveLists: function() { localStorage.setItem('tp_names', this.names); localStorage.setItem('tp_questions', this.questions); },

            _headers: function() {
                return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' };
            },
            saveToAccount: function() {
                if (this.saving || this.studentList.length < 1) return;
                var self = this;
                var title = this.saveName.trim() || 'Configuration tirage';
                this.saving = true;
                this.saveError = '';
                var isEdit = !!this._editingId;
                var url = isEdit ? '/api/draw-presets/' + this._editingId : '/api/draw-presets';
                var method = isEdit ? 'PUT' : 'POST';
                fetch(url, {
                    method: method, headers: this._headers(),
                    body: JSON.stringify({ name: title, config_text: this.names, params: { questions: this.questions, removeStudent: this.removeStudent, removeQuestion: this.removeQuestion, timerEnabled: this.timerEnabled, timerMinutes: this.timerMinutes } })
                })
                .then(function(r) { if (!r.ok) throw new Error('Erreur ' + r.status); return r.json(); })
                .then(function(data) {
                    self._editingId = null;
                    self.saveName = '';
                    self.saving = false;
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: '{{ __("Configuration sauvegardée") }}' } }));
                })
                .catch(function(e) { self.saveError = e.message; self.saving = false; setTimeout(function() { self.saveError = ''; }, 4000); });
            },
            initEditMode: function() {
                if (!this.isAuthenticated) return;
                var self = this;
                var params = new URLSearchParams(window.location.search);
                var editId = params.get('edit');
                if (!editId) return;
                fetch('/api/draw-presets', { headers: this._headers() })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        var found = (data.data || []).find(function(p) { return p.public_id === editId; });
                        if (found) {
                            self.names = found.config_text || '';
                            var pr = found.params || {};
                            self.questions = pr.questions || '';
                            self.removeStudent = pr.removeStudent !== undefined ? pr.removeStudent : true;
                            self.removeQuestion = pr.removeQuestion !== undefined ? pr.removeQuestion : true;
                            self.timerEnabled = pr.timerEnabled || false;
                            self.timerMinutes = pr.timerMinutes || 5;
                            self.saveName = found.name;
                            self._editingId = found.public_id;
                        }
                    });
            },

            exportHistory: function() {
                var text = this.history.map(function(d, i) { return '#' + (i + 1) + ' - ' + d.time + ' - ' + d.student + ' \u2192 ' + d.question; }).join('\n');
                var blob = new Blob([text], { type: 'text/plain' });
                var a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = 'tirages.txt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            },
            printHistory: function() {
                var text = this.history.map(function(d, i) { return '#' + (i + 1) + ' - ' + d.time + ' - ' + d.student + ' \u2192 ' + d.question; }).join('\n');
                var w = window.open('', '_blank');
                w.document.write('<html><head><title>Historique des tirages</title><style>body{font-family:sans-serif;padding:2rem;}h1{font-size:1.5rem;}pre{font-size:0.9rem;line-height:1.8;}</style></head><body><h1>Historique des tirages</h1><pre>' + text + '</pre></body></html>');
                w.document.close();
                w.print();
            },
            importFile: function(event, target) {
                var self = this;
                var file = event.target.files[0];
                if (!file) return;
                var reader = new FileReader();
                reader.onload = function(e) {
                    if (target === 'names') self.names = e.target.result;
                    else self.questions = e.target.result;
                    self.saveLists();
                    self.resetDrawn();
                };
                reader.readAsText(file);
            },
            generateFullOrder: function() {
                var students = this.studentList.slice();
                var qs = this.questionsList.slice();
                for (var i = students.length - 1; i > 0; i--) { var j = this.secureRandom(i + 1); var t = students[i]; students[i] = students[j]; students[j] = t; }
                for (var k = qs.length - 1; k > 0; k--) { var m = this.secureRandom(k + 1); var q = qs[k]; qs[k] = qs[m]; qs[m] = q; }
                this.fullOrder = students.map(function(s, i) { return { student: s, question: qs[i % qs.length] }; });
            },
            copyFullOrder: function() {
                var self = this;
                var text = this.fullOrder.map(function(item, i) { return (i + 1) + '. ' + item.student + ' \u2192 ' + item.question; }).join('\n');
                navigator.clipboard.writeText(text);
                this.copied = true;
                setTimeout(function() { self.copied = false; }, 2000);
            }
        };
    });
});
</script>
@endpush
