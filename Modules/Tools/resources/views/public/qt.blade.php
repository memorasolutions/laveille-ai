@extends(fronttheme_layout())
@section('title','QT — Quotient Techno - '.config('app.name'))
@section('meta_description','Teste ta culture techno et obtiens ton Quotient Techno (QT). Quiz IA, numérique et cybersécurité.')
@section('breadcrumb') @include('fronttheme::partials.breadcrumb',['breadcrumbTitle'=>'QT — Quotient Techno']) @endsection
@section('content')
@php $payload = base64_encode(json_encode($round)); @endphp
<style>
.qt-choice{display:block;width:100%;text-align:left;margin:8px 0;padding:12px 16px;border:2px solid #e5e7eb;border-radius:8px;background:#fff;color:var(--c-dark);font-size:1rem;cursor:pointer;transition:border-color .15s,background .15s;}
.qt-choice:hover:not(:disabled){border-color:var(--c-primary);}
.qt-choice:disabled{cursor:default;}
.qt-correct{background:#2E7D32 !important;color:#fff !important;border-color:#2E7D32 !important;}
.qt-wrong{background:#B91C1C !important;color:#fff !important;border-color:#B91C1C !important;}
.qt-dim{opacity:.55;}
[x-cloak]{display:none !important;}
</style>
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col col-lg-10 offset-lg-1">
                <div class="wpo-blog-content">
                    <div class="post" style="max-width:640px;margin:0 auto;" x-data="qtApp('{{ $payload }}')">
                        <h1 style="text-align:center;color:var(--c-dark);">QT — Quotient Techno</h1>
                        <p style="text-align:center;color:var(--c-text-secondary);">Quel est ton Quotient Techno ? 10 questions, score façon QI.</p>

                        {{-- Écran Quiz --}}
                        <div x-show="!gameOver">
                            <p style="text-align:center;color:var(--c-text-muted);">Question <span x-text="index+1"></span> / 10</p>
                            <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:20px;">
                                <p style="font-weight:600;font-size:1.1rem;color:var(--c-dark);" x-text="q().question"></p>
                                <template x-for="(c,i) in q().choices" :key="i">
                                    <button type="button" class="qt-choice" :class="choiceClass(i)" @click="choose(i)" :disabled="answered" x-text="c"></button>
                                </template>
                                <div x-show="answered" style="margin-top:12px;">
                                    <p x-text="selected===q().correct ? '✅ Bonne réponse !' : '❌ Pas tout à fait.'" :style="selected===q().correct?'color:#2E7D32;font-weight:600;':'color:#B91C1C;font-weight:600;'"></p>
                                    <p style="color:var(--c-text-secondary);" x-text="q().explanation"></p>
                                    <p x-show="q().fiche">
                                        <a :href="q().fiche" target="_blank" rel="noopener noreferrer" style="color:var(--c-primary);font-weight:600;">📖 Lire la fiche →</a>
                                    </p>
                                    <button type="button" class="ct-btn ct-btn-primary" @click="next()" x-text="index<9?'Question suivante →':'Voir mon QT →'" style="margin-top:8px;border-radius:8px;height:44px;padding:0 20px;"></button>
                                </div>
                            </div>
                        </div>

                        {{-- Écran Résultat --}}
                        <div x-show="gameOver" x-cloak style="text-align:center;">
                            <p style="color:var(--c-text-muted);">Ton Quotient Techno</p>
                            <p style="font-size:3.5rem;font-weight:800;color:var(--c-primary);line-height:1;" x-text="qt"></p>
                            <p style="font-size:1.4rem;font-weight:700;color:var(--c-dark);">
                                <span x-text="rank.emoji"></span> <span x-text="rank.label"></span>
                            </p>
                            <p style="color:var(--c-text-secondary);" x-text="correctCount+' / 10 bonnes réponses'"></p>
                            <p style="color:var(--c-text-secondary);" x-text="rank.message"></p>
                            <button class="ct-btn ct-btn-primary" @click="share()" style="margin:10px 6px;border-radius:8px;height:44px;padding:0 20px;">Partager mon QT</button>
                            <button class="ct-btn" @click="replay()" style="margin:10px 6px;border-radius:8px;height:44px;padding:0 20px;border:1px solid var(--c-primary);color:var(--c-primary);background:#fff;">Rejouer (nouvelles questions)</button>
                            <p style="font-size:12px;color:var(--c-text-muted);margin-top:8px;">Score ludique de culture techno — pas un vrai test de QI 😉</p>
                        </div>

                        {{-- Révision --}}
                        <div x-show="gameOver" x-cloak>
                            <h3 style="color:var(--c-dark);margin-top:24px;">Révision</h3>
                            <template x-for="(item,i) in questions" :key="i">
                                <div style="border-top:1px solid #e5e7eb;padding:12px 0;">
                                    <p style="font-weight:600;color:var(--c-dark);" x-text="(i+1)+'. '+item.question"></p>
                                    <p :style="answers[i]===item.correct?'color:#2E7D32;':'color:#B91C1C;'" x-text="answers[i]===item.correct ? '✅ Ta réponse : '+item.choices[answers[i]] : '❌ Ta réponse : '+(answers[i]!=null?item.choices[answers[i]]:'—')"></p>
                                    <p x-show="answers[i]!==item.correct" style="color:#2E7D32;" x-text="'Bonne réponse : '+item.choices[item.correct]"></p>
                                    <p style="color:var(--c-text-secondary);font-size:0.95rem;" x-text="item.explanation"></p>
                                    <p x-show="item.fiche">
                                        <a :href="item.fiche" target="_blank" rel="noopener noreferrer" style="color:var(--c-primary);">📖 Lire la fiche →</a>
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function qtApp(payload) {
    return {
        questions: [], index: 0, answered: false, selected: null,
        answers: [], gameOver: false, qt: 0, correctCount: 0, rank: {},

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
            if (this.index < 9) {
                this.index++;
                this.answered = false;
                this.selected = null;
            } else {
                this.finish();
            }
        },

        finish() {
            let earned = 0, total = 0, correct = 0;
            this.questions.forEach((q, i) => {
                total += q.points;
                if (this.answers[i] === q.correct) { earned += q.points; correct++; }
            });
            this.correctCount = correct;
            const frac = total > 0 ? earned / total : 0;
            this.qt = Math.round(55 + frac * 90);
            if (this.qt < 55) this.qt = 55;
            if (this.qt > 145) this.qt = 145;
            this.rank = this.computeRank(this.qt);
            this.gameOver = true;
            try {
                const best = parseInt(localStorage.getItem('qt-best') || '0');
                if (this.qt > best) { localStorage.setItem('qt-best', this.qt); }
            } catch (e) {}
        },

        computeRank(qt) {
            if (qt < 80) return { emoji: '🐣', label: 'Curieux du numérique', message: "Tu débutes — et c'est parfait ! Rejoue et lis les fiches, ton QT va grimper." };
            if (qt <= 89) return { emoji: '🔌', label: 'Utilisateur futé', message: "Bonne base ! Encore quelques fiches et tu montes d'un cran." };
            if (qt <= 109) return { emoji: '💡', label: 'Techno futé', message: "Tu es dans la bonne moyenne techno. Continue !" };
            if (qt <= 119) return { emoji: '🤓', label: 'Connaisseur techno', message: "Solide ! Tu maîtrises bien le sujet." };
            if (qt <= 129) return { emoji: '🦾', label: 'Geek confirmé', message: "Impressionnant — tu fais partie des initiés." };
            return { emoji: '🏆', label: 'Super Geek', message: "Élite techno ! Peu de gens atteignent ce niveau." };
        },

        share() {
            const url = window.location.origin + '/outils/qt';
            const text = '🧠 Mon Quotient Techno : ' + this.qt + ' — ' + this.rank.label + ' ' + this.rank.emoji + '\nJusqu\'où ira le tien ? ' + url;
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
