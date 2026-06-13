@extends(fronttheme_layout())
@section('title','Motdle — le mot tech du jour - '.config('app.name'))
@section('meta_description','Devinez le mot tech/IA du jour en 6 essais. Jeu de mots quotidien gratuit en français.')
@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb',['breadcrumbTitle'=>'Motdle','breadcrumbItems'=>[__('Outils'),'Motdle']])
@endsection
@section('content')
@php $payload = base64_encode(json_encode($word)); @endphp
<style>
.motdle-cell{width:48px;height:48px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;text-transform:uppercase;border:2px solid #d1d5db;border-radius:6px;color:var(--c-dark);background:#fff;}
.motdle-cell.filled{border-color:#9ca3af;}
.motdle-correct{background:#2E7D32 !important;color:#fff !important;border-color:#2E7D32 !important;}
.motdle-present{background:#B7791F !important;color:#fff !important;border-color:#B7791F !important;}
.motdle-absent{background:#6B7280 !important;color:#fff !important;border-color:#6B7280 !important;}
@keyframes motdle-reveal{0%{transform:scale(.82);}60%{transform:scale(1.06);}100%{transform:scale(1);}}
.motdle-correct,.motdle-present,.motdle-absent{animation:motdle-reveal .3s ease;}
.motdle-key{min-width:34px;height:48px;margin:2px;border:none;border-radius:6px;background:#e5e7eb;font-weight:600;cursor:pointer;color:var(--c-dark);}
.motdle-key-correct{background:#2E7D32;color:#fff;}
.motdle-key-present{background:#B7791F;color:#fff;}
.motdle-key-absent{background:#9ca3af;color:#fff;}
[x-cloak]{display:none !important;}
@media (max-width:520px){.motdle-cell{width:40px;height:40px;font-size:1.2rem;}.motdle-key{min-width:28px;height:44px;}}
@media (max-width:380px){.motdle-cell{width:32px;height:32px;font-size:1rem;}.motdle-key{min-width:24px;font-size:0.85rem;}}
</style>

<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col col-lg-10 offset-lg-1">
                <div class="wpo-blog-content">
                    <div class="post" style="max-width:520px;margin:0 auto;" x-data="motdleApp('{{ $payload }}')">
                        <h1 style="color:var(--c-dark);font-size:1.6rem;text-align:center;">Motdle</h1>
                        <p style="text-align:center;color:var(--c-text-secondary);">
                            Le mot tech du jour en <span x-text="length"></span> lettres, en 6 essais.
                            Indice : commence par <strong x-text="first"></strong>.
                            <button type="button" class="ct-help-btn" data-help-key="motdle-rules" aria-label="Aide : comment jouer">?</button>
                        </p>

                        <p role="status" aria-live="polite" x-text="message" style="text-align:center;min-height:1.5em;color:var(--c-primary);font-weight:600;"></p>

                        <div role="grid" aria-label="Grille Motdle">
                            <template x-for="r in 6" :key="r">
                                <div role="row" style="display:flex;gap:6px;justify-content:center;margin-bottom:6px;">
                                    <template x-for="c in length" :key="c">
                                        <div
                                            role="gridcell"
                                            class="motdle-cell"
                                            :class="cellClass(r-1,c-1)"
                                            :aria-label="cellAria(r-1,c-1)"
                                            x-text="cellLetter(r-1,c-1)"
                                        ></div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <p style="text-align:center;font-size:12px;color:var(--c-text-muted,#6E7687);margin-top:8px;margin-bottom:0;">
                            🟩 bien placée · 🟨 mal placée · ⬜ absente
                        </p>

                        <div style="margin-top:24px;">
                            <div style="display:flex;justify-content:center;flex-wrap:nowrap;margin-bottom:6px;">
                                <template x-for="k in ['A','Z','E','R','T','Y','U','I','O','P']" :key="k">
                                    <button type="button" @click="press(k)" :class="keyClass(k)" class="motdle-key" x-text="k"></button>
                                </template>
                            </div>
                            <div style="display:flex;justify-content:center;flex-wrap:nowrap;margin-bottom:6px;">
                                <template x-for="k in ['Q','S','D','F','G','H','J','K','L','M']" :key="k">
                                    <button type="button" @click="press(k)" :class="keyClass(k)" class="motdle-key" x-text="k"></button>
                                </template>
                            </div>
                            <div style="display:flex;justify-content:center;flex-wrap:nowrap;">
                                <button type="button" @click="press('ENTER')" class="motdle-key" style="min-width:60px;" aria-label="Valider">Entrée</button>
                                <template x-for="k in ['W','X','C','V','B','N']" :key="k">
                                    <button type="button" @click="press(k)" :class="keyClass(k)" class="motdle-key" x-text="k"></button>
                                </template>
                                <button type="button" @click="press('BACK')" class="motdle-key" aria-label="Effacer">⌫</button>
                            </div>
                        </div>

                        <div x-show="gameOver" x-cloak x-transition style="margin-top:24px;text-align:center;">
                            <p x-text="won ? 'Bravo !' : 'Perdu — le mot était ' + display" style="font-weight:600;color:var(--c-primary);font-size:1.1rem;"></p>
                            <p><strong x-text="display" style="font-size:1.3rem;color:var(--c-dark);"></strong></p>
                            <p x-show="glossaryDef" x-text="glossaryDef" style="color:var(--c-text-secondary);"></p>
                            <p x-show="glossarySlug">
                                <a :href="glossaryUrl" style="color:var(--c-primary);font-weight:600;">En savoir plus dans le glossaire →</a>
                            </p>
                            <button @click="share()" class="ct-btn ct-btn-primary" style="margin-top:12px;">Partager mon résultat</button>
                            <p style="margin-top:16px;color:var(--c-text-secondary);">
                                Série : <strong x-text="streak"></strong> · record <strong x-text="record"></strong>
                            </p>
                            <p style="color:var(--c-text-secondary);">Revenez demain pour un nouveau mot.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function motdleApp(payload) {
    return {
        answer: '', display: '', length: 0, first: '', day: 0,
        glossarySlug: null, glossaryDef: null, glossaryUrl: null,
        rows: [], current: 0, guess: '', gameOver: false, won: false,
        keyStates: {}, message: '', streak: 0, record: 0,

        init() {
            const w = JSON.parse(atob(payload));
            this.answer = w.answer;
            this.display = w.display;
            this.length = w.length;
            this.first = w.first;
            this.day = w.day;
            this.glossarySlug = w.glossary_slug;
            this.glossaryDef = w.glossary_def;
            this.glossaryUrl = this.glossarySlug ? '/glossaire/' + this.glossarySlug : null;

            this.rows = Array.from({ length: 6 }, () =>
                Array.from({ length: this.length }, () => ({ letter: '', state: '' }))
            );

            const savedDay = localStorage.getItem('motdle-' + this.day);
            if (savedDay) {
                try {
                    const state = JSON.parse(savedDay);
                    this.rows = state.rows;
                    this.current = state.current;
                    this.gameOver = state.gameOver;
                    this.won = state.won;
                    this.keyStates = state.keyStates || {};
                } catch (e) { /* état corrompu : ignore */ }
            }

            const streakData = JSON.parse(localStorage.getItem('motdle-streak') || '{}');
            this.streak = streakData.current || 0;
            this.record = streakData.record || 0;

            window.addEventListener('keydown', (e) => this.onKey(e));

            // Aide : auto-ouverte à la 1re visite (puis mémorisée)
            if (!localStorage.getItem('motdle-help-seen')) {
                localStorage.setItem('motdle-help-seen', '1');
                setTimeout(() => {
                    const help = (window.HELP_CONTENT || {})['motdle-rules'];
                    if (help) { window.dispatchEvent(new CustomEvent('open-help-modal', { detail: help })); }
                }, 500);
            }
        },

        onKey(e) {
            if (this.gameOver) return;
            if (e.key === 'Enter') { this.press('ENTER'); }
            else if (e.key === 'Backspace') { this.press('BACK'); }
            else if (/^[a-zA-Z]$/.test(e.key)) { this.press(e.key.toUpperCase()); }
        },

        press(k) {
            if (this.gameOver) return;
            if (k === 'ENTER') { this.submit(); }
            else if (k === 'BACK') { this.guess = this.guess.slice(0, -1); }
            else if (this.guess.length < this.length) { this.guess += k; }
        },

        cellLetter(r, c) {
            if (r < this.current) { return this.rows[r][c].letter; }
            if (r === this.current) { return this.guess[c] || ''; }
            return '';
        },

        cellClass(r, c) {
            let base = '';
            if (r < this.current && this.rows[r][c].state) {
                base += ' motdle-' + this.rows[r][c].state;
            }
            if (this.cellLetter(r, c)) { base += ' filled'; }
            return base.trim();
        },

        cellAria(r, c) {
            const letter = this.cellLetter(r, c);
            if (!letter) return 'Case vide';
            if (r >= this.current) return 'Lettre ' + letter;
            const state = this.rows[r][c].state;
            if (state === 'correct') return 'Lettre ' + letter + ', bien placée';
            if (state === 'present') return 'Lettre ' + letter + ', mal placée';
            return 'Lettre ' + letter + ', absente';
        },

        keyClass(k) {
            const state = this.keyStates[k];
            return state ? 'motdle-key-' + state : '';
        },

        submit() {
            if (this.guess.length < this.length) { this.message = 'Mot incomplet'; return; }

            const guess = this.guess;
            const answer = this.answer;
            const length = this.length;

            const letterCount = {};
            for (let i = 0; i < length; i++) {
                letterCount[answer[i]] = (letterCount[answer[i]] || 0) + 1;
            }

            const result = Array.from({ length: length }, () => ({ letter: '', state: '' }));

            // Passe 1 : bien placées
            for (let i = 0; i < length; i++) {
                if (guess[i] === answer[i]) {
                    result[i] = { letter: guess[i], state: 'correct' };
                    letterCount[guess[i]]--;
                } else {
                    result[i] = { letter: guess[i], state: '' };
                }
            }
            // Passe 2 : présentes mal placées
            for (let i = 0; i < length; i++) {
                if (result[i].state === '') {
                    const ch = guess[i];
                    if (letterCount[ch] > 0) { result[i].state = 'present'; letterCount[ch]--; }
                    else { result[i].state = 'absent'; }
                }
            }

            this.rows[this.current] = result;

            // États des touches (priorité correct > present > absent)
            for (let i = 0; i < length; i++) {
                const ch = guess[i];
                const ns = result[i].state;
                const os = this.keyStates[ch];
                if (!os || os === 'absent') { this.keyStates[ch] = ns; }
                else if (os === 'present' && ns === 'correct') { this.keyStates[ch] = 'correct'; }
            }

            const isWin = result.every(cell => cell.state === 'correct');
            this.current++; // avance dans tous les cas (la ligne soumise devient r < current → s'affiche)
            this.guess = '';

            if (isWin) {
                this.won = true;
                this.gameOver = true;
                this.message = 'Bravo !';
            } else if (this.current >= 6) {
                this.gameOver = true;
                this.message = 'Perdu — le mot était ' + this.display;
            } else {
                this.message = '';
            }

            this.saveDay();
            if (this.gameOver) { this.updateStreak(); }
        },

        saveDay() {
            localStorage.setItem('motdle-' + this.day, JSON.stringify({
                rows: this.rows, current: this.current, gameOver: this.gameOver,
                won: this.won, keyStates: this.keyStates
            }));
        },

        updateStreak() {
            const d = JSON.parse(localStorage.getItem('motdle-streak') || '{}');
            if (d.lastDay === this.day) { return; }
            let newStreak = 0;
            if (this.won) {
                newStreak = (d.lastDay === this.day - 1) ? (d.current || 0) + 1 : 1;
            }
            const newRecord = Math.max(d.record || 0, newStreak);
            localStorage.setItem('motdle-streak', JSON.stringify({ current: newStreak, record: newRecord, lastDay: this.day }));
            this.streak = newStreak;
            this.record = newRecord;
        },

        share() {
            const emojis = { correct: '🟩', present: '🟨', absent: '⬜' };
            let grid = '';
            for (let r = 0; r < this.current; r++) {
                let line = '';
                for (let c = 0; c < this.length; c++) {
                    line += emojis[this.rows[r][c].state || 'absent'];
                }
                grid += line + '\n';
            }
            const text = 'Motdle #' + this.day + ' ' + (this.won ? this.current : 'X') + '/6\n' + grid + window.location.href;
            const toast = (msg, variant) => window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: msg, variant: variant, duration: 3000 } }));
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => { this.message = 'Résultat copié !'; toast('Résultat copié !', 'success'); })
                    .catch(() => { this.message = 'Copie impossible'; toast('Copie impossible', 'error'); });
            } else {
                this.message = 'Copie non supportée';
                toast('Copie non supportée', 'warning');
            }
        }
    };
}
</script>
<script>
window.HELP_CONTENT = Object.assign(window.HELP_CONTENT || {}, {
  "motdle-rules": {
    "title": "Comment jouer à Motdle",
    "body": "<p>Devinez le <strong>mot tech du jour</strong> en 6 essais. Chaque proposition doit avoir le bon nombre de lettres.</p>"
      + "<p>Après chaque essai, la couleur de chaque case vous guide :</p>"
      + "<ul style='line-height:1.9;list-style:none;padding-left:0;'>"
      + "<li><span style='display:inline-block;width:16px;height:16px;background:#2E7D32;border-radius:3px;vertical-align:middle;margin-right:8px;'></span><strong>Vert</strong> : bonne lettre, à la bonne place.</li>"
      + "<li><span style='display:inline-block;width:16px;height:16px;background:#B7791F;border-radius:3px;vertical-align:middle;margin-right:8px;'></span><strong>Jaune</strong> : la lettre est dans le mot, mais ailleurs.</li>"
      + "<li><span style='display:inline-block;width:16px;height:16px;background:#6B7280;border-radius:3px;vertical-align:middle;margin-right:8px;'></span><strong>Gris</strong> : la lettre n'est pas dans le mot.</li>"
      + "</ul>"
      + "<p><strong>Exemple</strong> — si le mot à trouver est CHAT et que vous proposez CAFE : le C devient vert (bien placé), le A devient jaune (présent mais ailleurs), et F et E deviennent gris (absents).</p>"
      + "<p>Un nouveau mot tech chaque jour. La première lettre est donnée en indice, et les accents ne comptent pas.</p>"
  }
});
</script>
@endsection
