@extends(fronttheme_layout())

@section('title', 'QT — Quotient Techno - '.config('app.name'))

@section('meta_description', 'Testez votre culture techno avec ce quiz ludique sur l’IA, le web et la cybersécurité. Découvrez votre Quotient Techno (QT) en 2 minutes !')

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'QT — Quotient Techno'])
@endsection

@section('content')
@php $payload = base64_encode(json_encode($round)); @endphp
<style>
    .qt-choice {
        display: block; width: 100%; text-align: left; margin: 8px 0; padding: 12px 16px;
        border: 2px solid #e5e7eb; border-radius: 8px; background: #fff; color: var(--c-dark);
        font-size: 1rem; cursor: pointer; transition: border-color 0.2s ease;
    }
    .qt-choice:hover:not(:disabled) { border-color: var(--c-primary); }
    .qt-choice:disabled { cursor: default; }
    .qt-correct { background: #2E7D32 !important; color: #fff !important; border-color: #2E7D32 !important; }
    .qt-wrong { background: #B91C1C !important; color: #fff !important; border-color: #B91C1C !important; animation: qt-shake 0.35s; }
    .qt-dim { opacity: 0.5; }
    .ct-btn-outline { background: #fff; border: 1px solid var(--c-primary); color: var(--c-primary); }
    .ct-btn-outline:hover { background: var(--c-primary); color: #fff; }
    @keyframes qt-shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-6px); } 75% { transform: translateX(6px); } }
    @keyframes qt-fall { to { transform: translateY(105vh) rotate(540deg); opacity: 0.2; } }
    .qt-medal { width: 150px; height: 150px; border-radius: 50%; margin: 1rem auto 0; display: flex; align-items: center; justify-content: center; color: #fff; border: 6px solid #E9B949; box-shadow: 0 0 0 3px rgba(255,255,255,.35) inset, 0 10px 28px rgba(0,0,0,.22); position: relative; overflow: hidden; animation: qt-medal-in .6s cubic-bezier(.18,.89,.32,1.28); }
    .qt-medal::before { content: ''; position: absolute; top: -40%; left: -45%; width: 70%; height: 180%; background: linear-gradient(115deg, rgba(255,255,255,.42), rgba(255,255,255,0)); transform: rotate(18deg); pointer-events: none; }
    @keyframes qt-medal-in { 0% { transform: scale(.4) rotate(-12deg); opacity: 0; } 100% { transform: scale(1) rotate(0); opacity: 1; } }
    [x-cloak] { display: none !important; }
</style>
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col col-lg-10 offset-lg-1">
                <div class="wpo-blog-content">
                    <div class="post" style="max-width:640px;margin:0 auto;" x-data="qtApp('{{ $payload }}')">
                        {{-- Écran Intro --}}
                        <div x-show="phase==='intro'" style="padding:2rem 1rem;text-align:center;">
                            <h1 style="font-size:2.25rem;font-weight:800;color:var(--c-primary);margin-bottom:1rem;">Quel est ton Quotient Techno ?</h1>
                            <p style="color:var(--c-text-secondary);font-size:1.125rem;margin-bottom:1.5rem;">
                                10 questions sur l'IA, le web et la cybersécurité.<br>
                                Découvre ton rang, de 🐣 Curieux à 🏆 Super Geek.
                            </p>
                            <div style="display:flex;justify-content:center;flex-wrap:wrap;gap:8px;margin-bottom:1.5rem;">
                                <span style="background:var(--c-primary-light,#F0FAFB);color:var(--c-primary);padding:0.5rem 1rem;font-weight:600;border-radius:99px;">⏱️ ~2 min</span>
                                <span style="background:var(--c-primary-light,#F0FAFB);color:var(--c-primary);padding:0.5rem 1rem;font-weight:600;border-radius:99px;">❓ 10 questions</span>
                                <span style="background:var(--c-primary-light,#F0FAFB);color:var(--c-primary);padding:0.5rem 1rem;font-weight:600;border-radius:99px;">⚡ résultat instantané</span>
                            </div>
                            <div style="font-size:1.6rem;letter-spacing:6px;margin-bottom:1.75rem;">🐣 🔌 💡 🤓 🦾 🏆</div>
                            <button class="ct-btn ct-btn-primary" style="height:52px;font-size:1.1rem;padding:0 28px;border-radius:8px;" @click="phase='quiz'">
                                Découvre ton QT 🚀
                            </button>
                            <p style="color:var(--c-text-muted);font-size:0.875rem;margin-top:1.5rem;">
                                Score ludique de culture techno — pas un vrai test de QI 😉
                            </p>
                        </div>

                        {{-- Écran Quiz --}}
                        <div x-show="phase==='quiz'" x-transition style="padding:1.5rem 0;">
                            <div style="margin-bottom:1.5rem;">
                                <div style="height:8px;background:#e5e7eb;border-radius:99px;overflow:hidden;">
                                    <div :style="'width:'+((index + (answered ? 1 : 0))/10*100)+'%;height:8px;background:var(--c-primary);border-radius:99px;transition:width .4s ease;'"></div>
                                </div>
                                <p style="color:var(--c-text-secondary);font-size:0.875rem;text-align:center;margin-top:8px;">
                                    Question <span x-text="index+1"></span> / 10
                                </p>
                            </div>

                            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:20px;margin-bottom:1rem;">
                                <p x-text="q().question" style="font-size:1.2rem;font-weight:600;color:var(--c-dark);margin-bottom:1.25rem;"></p>
                                <template x-for="(c, i) in q().choices" :key="i">
                                    <button type="button" class="qt-choice" :class="choiceClass(i)" @click="choose(i)" :disabled="answered" x-text="c"></button>
                                </template>
                            </div>

                            <div x-show="answered" x-transition style="padding:1.25rem;background:#f3f4f6;border-radius:12px;">
                                <p style="font-size:1.1rem;font-weight:600;margin-bottom:0.5rem;" :style="selected===q().correct?'color:#2E7D32;':'color:#B91C1C;'" x-text="selected===q().correct?'✅ Bonne réponse !':'❌ Pas tout à fait.'"></p>
                                <p x-text="q().explanation" style="color:var(--c-text-secondary);margin-bottom:0.75rem;"></p>
                                <p x-show="q().fiche" style="margin-bottom:0.75rem;">
                                    <a :href="q().fiche" target="_blank" rel="noopener noreferrer" style="color:var(--c-primary);font-weight:600;">📖 Lire la fiche →</a>
                                </p>
                                <button type="button" class="ct-btn ct-btn-primary" @click="next()" x-text="index < 9 ? 'Question suivante →' : 'Voir mon QT →'" style="border-radius:8px;height:44px;padding:0 20px;"></button>
                            </div>
                        </div>

                        {{-- Écran Résultat --}}
                        <div x-show="phase==='result'" x-cloak style="padding:1.5rem 0;text-align:center;">
                            <p style="font-size:1.125rem;color:var(--c-text-secondary);margin-bottom:0.25rem;">Ton Quotient Techno</p>
                            <p style="font-size:3.5rem;font-weight:800;color:var(--c-primary);line-height:1;" x-text="displayQt"></p>

                            <div class="qt-medal" :style="'background:'+rank.bg+';'">
                                <span x-text="rank.emoji" style="font-size:3rem;line-height:1;"></span>
                            </div>
                            <div x-text="rank.label" style="font-weight:800;font-size:1.4rem;color:var(--c-dark);margin-top:0.75rem;"></div>

                            <p style="font-size:1.05rem;color:var(--c-text-secondary);margin-top:0.75rem;">
                                <span x-text="correctCount"></span> / 10 bonnes réponses
                            </p>
                            <p style="color:var(--c-text-muted);margin-top:0.25rem;" x-text="rank.message"></p>

                            {{-- Question IA humoristique --}}
                            <div x-show="!aiAnswered" style="margin-top:16px;background:var(--c-primary-light,#F0FAFB);border-radius:12px;padding:14px;">
                                <p style="font-weight:600;margin-bottom:0.75rem;color:var(--c-dark);">🤖 Avoue… un petit coup de main d'une IA ?</p>
                                <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;">
                                    <button type="button" class="ct-btn ct-btn-outline" style="border-radius:8px;" @click="setAi('brain')">🧠 100 % cerveau !</button>
                                    <button type="button" class="ct-btn ct-btn-outline" style="border-radius:8px;" @click="setAi('hint')">🤝 un petit coup de pouce</button>
                                    <button type="button" class="ct-btn ct-btn-outline" style="border-radius:8px;" @click="setAi('ai')">🤖 à fond l'IA 😅</button>
                                </div>
                            </div>
                            <p x-show="aiAnswered" style="font-weight:600;color:var(--c-primary);margin-top:0.75rem;" x-text="aiBadge"></p>

                            <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin-top:1.25rem;">
                                <button type="button" class="ct-btn ct-btn-primary" style="border-radius:8px;height:44px;padding:0 20px;" @click="share()">Partager mon QT</button>
                                <button type="button" class="ct-btn ct-btn-outline" style="border-radius:8px;height:44px;padding:0 20px;" @click="replay()">Rejouer</button>
                            </div>

                            {{-- Révision --}}
                            <div x-show="questions.length > 0" style="margin-top:2rem;">
                                <h3 style="text-align:left;margin-bottom:1.25rem;font-weight:700;color:var(--c-dark);">Révision</h3>
                                <template x-for="(item, i) in questions" :key="i">
                                    <div style="background:#f9fafb;border-radius:12px;padding:16px;margin-bottom:1rem;text-align:left;">
                                        <p style="font-weight:600;color:var(--c-dark);margin-bottom:0.5rem;" x-text="(i+1)+'. '+item.question"></p>
                                        <p style="margin-bottom:0.5rem;" :style="answers[i]===item.correct?'color:#2E7D32;':'color:#B91C1C;'" x-text="answers[i]!==null ? ((answers[i]===item.correct?'✅ ':'❌ ')+'Ta réponse : '+item.choices[answers[i]]) : '—'"></p>
                                        <p x-show="answers[i]!==item.correct" style="color:#2E7D32;margin-bottom:0.5rem;" x-text="'Bonne réponse : '+item.choices[item.correct]"></p>
                                        <p x-text="item.explanation" style="color:var(--c-text-secondary);font-size:0.95rem;margin-bottom:0.5rem;"></p>
                                        <p x-show="item.fiche">
                                            <a :href="item.fiche" target="_blank" rel="noopener noreferrer" style="color:var(--c-primary);font-weight:600;">📖 Lire la fiche →</a>
                                        </p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="qt-confetti" style="position:fixed;inset:0;pointer-events:none;z-index:60;overflow:hidden;"></div>
</section>

<script>
function qtApp(payload) {
    return {
        phase: 'intro', questions: [], index: 0, answered: false, selected: null,
        answers: [], qt: 0, displayQt: 0, correctCount: 0, rank: {},
        aiAnswered: false, aiBadge: '', aiUsage: '',

        init() {
            this.questions = JSON.parse(atob(payload));
            this.answers = Array(this.questions.length).fill(null);
        },

        q() { return this.questions[this.index] || { choices: [] }; },

        choose(i) {
            if (this.answered) return;
            this.selected = i;
            this.answered = true;
            this.answers[this.index] = i;
        },

        choiceClass(i) {
            if (!this.answered) return '';
            if (i === this.q().correct) return 'qt-correct';
            if (i === this.selected) return 'qt-wrong';
            return 'qt-dim';
        },

        next() {
            if (this.index < 9) { this.index++; this.answered = false; this.selected = null; }
            else { this.finish(); }
        },

        finish() {
            let earned = 0, total = 0, correct = 0;
            this.questions.forEach((q, i) => {
                total += q.points;
                if (this.answers[i] === q.correct) { earned += q.points; correct++; }
            });
            const frac = total > 0 ? earned / total : 0;
            this.qt = Math.min(145, Math.max(55, Math.round(55 + frac * 90)));
            this.correctCount = correct;
            this.rank = this.computeRank(this.qt);
            this.phase = 'result';
            this.animateQt();
            if (this.qt >= 120) { this.$nextTick(() => this.burstConfetti()); }
            try {
                const best = localStorage.getItem('qt-best');
                if (!best || this.qt > parseInt(best)) { localStorage.setItem('qt-best', this.qt.toString()); }
            } catch (e) {}
        },

        animateQt() {
            this.displayQt = 0;
            const step = Math.max(1, Math.round(this.qt / 40));
            const t = setInterval(() => {
                this.displayQt = Math.min(this.qt, this.displayQt + step);
                if (this.displayQt >= this.qt) { clearInterval(t); }
            }, 30);
        },

        computeRank(qt) {
            if (qt < 80) return { emoji: '🐣', label: 'Curieux du numérique', message: "Tu débutes — et c'est parfait ! Rejoue et lis les fiches, ton QT va grimper.", bg: 'linear-gradient(135deg,#6E7687,#52586a)' };
            if (qt < 90) return { emoji: '🔌', label: 'Utilisateur futé', message: "Bonne base ! Encore quelques fiches et tu montes d'un cran.", bg: 'linear-gradient(135deg,#0B7285,#0a6275)' };
            if (qt < 110) return { emoji: '💡', label: 'Techno futé', message: "Tu es dans la bonne moyenne techno. Continue !", bg: 'linear-gradient(135deg,#0B7285,#064E5A)' };
            if (qt < 120) return { emoji: '🤓', label: 'Connaisseur techno', message: "Solide ! Tu maîtrises bien le sujet.", bg: 'linear-gradient(135deg,#064E5A,#053a43)' };
            if (qt < 130) return { emoji: '🦾', label: 'Geek confirmé', message: "Impressionnant — tu fais partie des initiés.", bg: 'linear-gradient(135deg,#9A2A06,#7a2105)' };
            return { emoji: '🏆', label: 'Super Geek', message: "Élite techno ! Peu de gens atteignent ce niveau.", bg: 'linear-gradient(135deg,#E9B949,#c8961f)' };
        },

        setAi(v) {
            this.aiAnswered = true;
            this.aiUsage = v;
            this.aiBadge = v === 'brain' ? 'Badge : 100 % cerveau 🧠' : (v === 'hint' ? 'Badge : cerveau + copilote IA 🤝' : "Badge : avec l'aide d'une IA 🤖");
        },

        burstConfetti() {
            const c = document.getElementById('qt-confetti');
            if (!c) return;
            const cols = ['#064E5A', '#0B7285', '#E9B949', '#2E7D32', '#ffffff'];
            for (let i = 0; i < 40; i++) {
                const d = document.createElement('div');
                d.style.cssText = 'position:absolute;top:-10px;width:9px;height:9px;border-radius:2px;left:' + (Math.random() * 100) + '%;background:' + cols[i % cols.length] + ';animation:qt-fall ' + (2 + Math.random() * 1.2) + 's linear forwards;transform:rotate(' + (Math.random() * 360) + 'deg);';
                c.appendChild(d);
            }
            setTimeout(() => { c.innerHTML = ''; }, 3500);
        },

        share() {
            const url = window.location.origin + '/outils/qt';
            let tag = '';
            if (this.aiUsage === 'brain') tag = ' (100 % cerveau 🧠)';
            else if (this.aiUsage === 'ai') tag = " (avec l'aide d'une IA 🤖)";
            else if (this.aiUsage === 'hint') tag = ' (un peu aidé 🤝)';
            const text = '🧠 Mon Quotient Techno : ' + this.qt + ' — ' + this.rank.label + ' ' + this.rank.emoji + tag + '\nJusqu\'où ira le tien ? ' + url;
            const toast = (m, v) => window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: m, variant: v, duration: 3000 } }));
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => toast('Résultat copié !', 'success')).catch(() => toast('Copie impossible', 'error'));
            } else {
                toast('Copie non supportée', 'warning');
            }
        },

        replay() { window.location.reload(); }
    };
}
</script>
@endsection
