@extends(fronttheme_layout())

@section('title', 'QT — Quotient Techno - '.config('app.name'))

@section('meta_description', 'Testez votre culture techno avec ce quiz ludique sur l’IA, le web et la cybersécurité. Découvrez votre Quotient Techno (QT) en 2 minutes !')

@section('og_type', 'article')
@section('og_image', ($share['og_image'] ?? asset('images/tools/qt.jpg')))

@section('breadcrumb')
    @include('fronttheme::partials.breadcrumb', ['breadcrumbTitle' => 'QT — Quotient Techno', 'breadcrumbItems' => [__('Outils'), 'QT — Quotient Techno']])
@endsection

@section('content')
@php $payload = base64_encode(json_encode($round)); $dailyPayload = base64_encode(json_encode($daily['questions'])); $dailyNumber = (int) $daily['number']; @endphp
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
    /* Appariement — blocs déplaçables */
    .qt-mblock { display:block; background:#fff; border:2px solid #e5e7eb; border-radius:10px; padding:12px 14px; color:var(--c-dark); font-size:0.98rem; line-height:1.35; cursor:grab; min-height:44px; touch-action:none; user-select:none; -webkit-user-select:none; box-shadow:0 1px 3px rgba(0,0,0,.08); transition:border-color .15s ease, box-shadow .15s ease; }
    .qt-mblock:hover { border-color:var(--c-primary); }
    .qt-mblock:focus-visible { outline:none; border-color:var(--c-primary); box-shadow:0 0 0 .2rem rgba(6,78,90,.2); }
    .qt-mblock.is-picked { border-color:var(--c-primary); box-shadow:0 0 0 .2rem rgba(6,78,90,.18); }
    .qt-mblock.is-drag { opacity:.45; }
    .qt-mblock.qt-mdisabled { cursor:default; opacity:.7; }
    .qt-mslot { min-height:56px; border:2px dashed #cbd5e1; border-radius:10px; background:#f9fafb; display:flex; align-items:center; padding:4px; transition:border-color .15s ease, background .15s ease; }
    .qt-mslot.is-empty:hover, .qt-mslot:focus-visible { outline:none; border-color:var(--c-primary); background:#f0fafb; }
    .qt-mslot:not(.is-empty) { border-style:solid; border-color:#cbd5e1; background:#fff; }
    .qt-mslot-ph { color:var(--c-text-muted,#6E7687); font-size:0.9rem; padding:0 10px; }
    .qt-mslot .qt-mblock { width:100%; margin:0; }
    .qt-mghost { position:fixed; top:0; left:0; z-index:1080; pointer-events:none; opacity:.92; box-shadow:0 8px 20px rgba(0,0,0,.22); }
    [x-cloak] { display: none !important; }
</style>
<section class="wpo-blog-single-section section-padding">
    <div class="container">
        <div class="row">
            <div class="col col-lg-10 offset-lg-1">
                <div class="wpo-blog-content">
                    <div class="post" style="max-width:640px;margin:0 auto;" x-data="qtApp('{{ $payload }}', '{{ $dailyPayload }}', {{ $dailyNumber }})">
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
                                <span style="background:var(--c-primary-light,#F0FAFB);color:var(--c-primary);padding:0.5rem 1rem;font-weight:600;border-radius:99px;">🔄 renouvelé à chaque partie</span>
                            </div>
                            <div style="font-size:1.6rem;letter-spacing:6px;margin-bottom:1.75rem;">🐣 🔌 💡 🤓 🦾 🏆</div>
                            <div style="display:flex;flex-direction:column;align-items:center;gap:10px;">
                                <button class="ct-btn ct-btn-primary" style="height:52px;font-size:1.1rem;padding:0 28px;border-radius:8px;" @click="begin('defi')">
                                    🏆 Défi du jour <span x-text="'#' + defiNumber"></span> 🚀
                                </button>
                                <button class="ct-btn ct-btn-outline" style="height:44px;padding:0 22px;border-radius:8px;" @click="begin('libre')">
                                    🎲 Partie libre (10 questions au hasard)
                                </button>
                                <p x-show="defiDoneToday" style="font-size:0.85rem;color:var(--c-text-muted);margin:0.25rem 0 0;">✅ Tu as déjà fait le défi d'aujourd'hui (QT : <span x-text="bestToday"></span>) — tu peux le refaire ou jouer en partie libre.</p>
                            </div>
                            <p style="color:var(--c-primary);font-weight:600;font-size:0.95rem;margin-top:1.5rem;max-width:520px;margin-left:auto;margin-right:auto;">
                                🔄 Les 10 questions sont pigées au hasard dans une grande banque : <strong>elles changent à chaque partie</strong>. Refais le test autant de fois que tu veux pour découvrir de nouvelles notions… et faire grimper ton QT !
                            </p>
                            <p style="color:var(--c-text-muted);font-size:0.875rem;margin-top:0.75rem;">
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
                                <p style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--c-primary);margin-bottom:0.5rem;" x-text="typeLabel()"></p>
                                <p x-text="q().question" style="font-size:1.2rem;font-weight:600;color:var(--c-dark);margin-bottom:1.25rem;"></p>

                                {{-- QCM + Vrai/Faux : boutons de choix --}}
                                <div x-show="isChoiceType()">
                                    <template x-for="(c, i) in q().choices" :key="i">
                                        <button type="button" class="qt-choice" :class="choiceClass(i)" @click="choose(i)" :disabled="answered" x-text="c"></button>
                                    </template>
                                </div>

                                {{-- Réponse courte : champ texte --}}
                                <div x-show="q().type==='court'">
                                    <input type="text" class="form-control" x-model="courtInput" :disabled="answered" @keydown.enter.prevent="validateCourt()"
                                           placeholder="Écris ta réponse…" autocomplete="off" autocapitalize="off" spellcheck="false"
                                           aria-label="Ta réponse" style="font-size:1.1rem;height:48px;margin-bottom:0.75rem;">
                                    <button type="button" class="ct-btn ct-btn-primary" @click="validateCourt()" :disabled="answered || !courtInput.trim()"
                                            style="border-radius:8px;height:44px;padding:0 22px;">Valider</button>
                                </div>

                                {{-- Appariement : blocs déplaçables (glisser-déposer souris+tactile + tap/clavier — WCAG 2.5.7) --}}
                                <div x-show="q().type==='appariement'">
                                    <div class="d-flex flex-column gap-3">
                                        {{-- Réserve --}}
                                        <div>
                                            <div style="font-weight:700;color:var(--c-text-muted,#6E7687);font-size:0.8rem;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Réserve · glisse ou tape un bloc</div>
                                            <div class="d-grid gap-2">
                                                <template x-for="def in poolDefs()" :key="'pool-'+def.idx">
                                                    <div class="qt-mblock" :class="{'is-picked': pickedDef===def.idx, 'is-drag': dragActive && dragIdx===def.idx, 'qt-mdisabled': answered}"
                                                         role="button" :tabindex="answered ? -1 : 0" :aria-pressed="pickedDef===def.idx" :data-def-index="def.idx"
                                                         @pointerdown="onPointerDownDef($event, def.idx, null)" @click.prevent="pickDef(def.idx)"
                                                         @keydown.enter.prevent="onKeyDef(def.idx, null)" @keydown.space.prevent="onKeyDef(def.idx, null)" x-text="def.text"></div>
                                                </template>
                                                <template x-if="poolDefs().length===0"><div style="font-size:0.85rem;color:var(--c-text-muted,#6E7687);">Tous les blocs sont placés.</div></template>
                                            </div>
                                        </div>
                                        {{-- Termes + emplacements --}}
                                        <div class="d-grid gap-2">
                                            <template x-for="(term, k) in q().terms" :key="'term-'+k">
                                                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;">
                                                    <span style="font-weight:700;color:var(--c-dark);min-width:130px;flex:0 0 auto;" x-text="term"></span>
                                                    <div class="qt-mslot" :class="{'is-empty': matchSel[k]===''}" role="button" :tabindex="answered ? -1 : 0"
                                                         :data-slot-index="k" :aria-label="slotAria(k)" @click.prevent="placeOnSlot(k)"
                                                         @keydown.enter.prevent="onKeySlot(k)" @keydown.space.prevent="onKeySlot(k)" style="flex:1 1 220px;min-width:0;">
                                                        <template x-if="matchSel[k]===''"><span class="qt-mslot-ph">Dépose ici</span></template>
                                                        <template x-if="matchSel[k]!==''">
                                                            <div class="qt-mblock" :class="{'is-drag': dragActive && dragIdx===matchSel[k], 'qt-mdisabled': answered}"
                                                                 role="button" :tabindex="answered ? -1 : 0" aria-label="Définition placée, appuyez pour retirer." :data-def-index="matchSel[k]"
                                                                 @pointerdown="onPointerDownDef($event, matchSel[k], k)" @click.prevent="returnToPool(k)"
                                                                 @keydown.enter.prevent="returnToPool(k)" @keydown.space.prevent="returnToPool(k)" x-text="q().defs[matchSel[k]]"></div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                        <button type="button" class="ct-btn ct-btn-primary" @click="validateMatch()" :disabled="answered || !matchAllChosen()" style="border-radius:8px;height:44px;padding:0 22px;margin-top:0.25rem;align-self:flex-start;">Valider l'association</button>
                                    </div>
                                </div>
                            </div>

                            <div x-show="answered" x-transition style="padding:1.25rem;background:#f3f4f6;border-radius:12px;">
                                <p style="font-size:1.1rem;font-weight:600;margin-bottom:0.5rem;" :style="lastCorrect?'color:#2E7D32;':'color:#B91C1C;'" x-text="lastCorrect?'✅ Bonne réponse !':'❌ Pas tout à fait.'"></p>
                                <p x-show="q().type==='court' && !lastCorrect" style="margin-bottom:0.5rem;color:#2E7D32;" x-text="'Réponse attendue : '+(q().display||'')"></p>
                                <div x-show="q().type==='appariement'" style="margin-bottom:0.5rem;">
                                    <template x-for="(t, ti) in q().terms" :key="ti">
                                        <p style="margin-bottom:0.25rem;font-size:0.95rem;color:var(--c-dark);"><strong x-text="t"></strong><span x-text="' → '+q().defs[q().answer[ti]]"></span></p>
                                    </template>
                                </div>
                                <p x-text="q().explanation" style="color:var(--c-text-secondary);margin-bottom:0.75rem;"></p>
                                <p x-show="q().fiche" style="margin-bottom:0.75rem;">
                                    <a :href="q().fiche" target="_blank" rel="noopener noreferrer" style="color:var(--c-primary);font-weight:600;">📖 Lire la fiche →</a>
                                </p>
                                <button type="button" class="ct-btn ct-btn-primary" @click="next()" x-text="index < 9 ? 'Question suivante →' : 'Voir mon QT →'" style="border-radius:8px;height:44px;padding:0 20px;"></button>
                            </div>
                        </div>

                        {{-- Écran Résultat --}}
                        <div x-show="phase==='result'" x-cloak style="padding:1.5rem 0;text-align:center;">
                            <div x-show="isDefi" style="display:inline-block;background:var(--c-primary);color:#fff;font-weight:700;border-radius:999px;padding:4px 14px;margin-bottom:0.75rem;font-size:0.95rem;"><span x-text="'🏆 Défi du jour #' + defiNumber"></span></div>
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

                            {{-- Phase 2 : streak + preuve sociale (honnête : affichée seulement quand il y a des données) --}}
                            <div x-show="streak >= 2 || socialProof" style="margin-top:0.85rem;">
                                <div x-show="streak >= 2" style="display:inline-block;background:#FFF4E5;color:#9A3412;font-weight:700;border-radius:999px;padding:5px 14px;margin-bottom:0.4rem;">🔥 Série de <span x-text="streak"></span> jours&nbsp;!</div>
                                <template x-if="socialProof && socialProof.percentile !== null">
                                    <div>
                                        <p style="color:var(--c-primary);font-weight:700;font-size:1.05rem;margin:0;">🏆 Tu bats <span x-text="socialProof.percentile"></span>&nbsp;% des joueurs&nbsp;!</p>
                                        <p x-show="socialProof.today >= 5" style="color:var(--c-text-muted);font-size:0.85rem;margin:0.15rem 0 0;"><span x-text="socialProof.today"></span> parties jouées aujourd'hui</p>
                                    </div>
                                </template>
                            </div>

                            {{-- A : diagnostic par thème (forces / faiblesses + fiches ciblées) --}}
                            <div style="max-width:560px;margin:1.5rem auto 0;text-align:left;background:var(--c-primary-light,#F0FAFB);border-radius:12px;padding:16px 18px;">
                                <p style="font-weight:800;font-size:1.05rem;color:var(--c-dark);margin-bottom:1rem;text-align:center;">📊 Ton profil par thème</p>
                                <template x-for="t in themeStats()" :key="t.key">
                                    <div style="margin-bottom:0.85rem;">
                                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                            <span style="font-weight:600;color:var(--c-dark);"><span x-text="t.emoji"></span> <span x-text="t.label"></span></span>
                                            <span style="font-weight:700;color:var(--c-dark);" x-text="t.correct + '/' + t.total"></span>
                                        </div>
                                        <div style="height:8px;border-radius:999px;background:#E5E7EB;overflow:hidden;" role="progressbar" :aria-valuenow="t.pct" aria-valuemin="0" aria-valuemax="100">
                                            <div style="height:100%;border-radius:999px;transition:width .6s ease;" :style="'width:'+t.pct+'%;background:'+barColor(t.pct)+';'"></div>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="weakFiches().length > 0" style="margin-top:1.1rem;">
                                    <p style="font-weight:700;color:var(--c-dark);margin-bottom:0.6rem;">📚 Pour progresser, (re)lis :</p>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                        <template x-for="f in weakFiches()" :key="f.url">
                                            <a :href="f.url" target="_blank" rel="noopener noreferrer" style="display:inline-flex;align-items:center;gap:4px;padding:6px 14px;border-radius:999px;background:#fff;color:var(--c-primary);font-weight:600;font-size:0.9rem;text-decoration:none;border:1px solid var(--c-primary);">📖 <span x-text="f.label"></span></a>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- (Question IA humoristique retirée v1.65.204 — peu claire, allège l'écran. setAi/aiTag conservés dormants : réversible.) --}}

                            {{-- Résultat copiable « façon Wordle » (action principale, surtout sur ordinateur) --}}
                            <div style="max-width:420px;margin:1.5rem auto 0.75rem;background:var(--c-primary-light,#F0FAFB);border-radius:12px;padding:14px 16px;text-align:center;">
                                <div style="font-weight:700;color:var(--c-dark);margin-bottom:0.5rem;">🧠 Mon QT : <span x-text="qt"></span> <span x-text="rank.emoji"></span> <span x-text="rank.label"></span></div>
                                <div x-text="emojiGrid()" style="font-size:1.5rem;letter-spacing:2px;line-height:1.3;"></div>
                                <div x-text="correctCount + '/10'" style="font-size:0.85rem;color:var(--c-dark);opacity:0.7;margin-top:0.25rem;"></div>
                            </div>
                            <button type="button" class="ct-btn ct-btn-primary" style="border-radius:8px;height:46px;padding:0 24px;font-weight:700;" @click="copyResult()">📋 Copier mon résultat</button>
                            <p x-show="!shareOpen" style="margin:0.5rem auto 0;max-width:440px;color:var(--c-text-muted);font-size:0.9rem;">😏 Qui peut battre ton QT ? Copie-le et <strong>défie ta gang</strong> !<span x-show="isDefi"> ⏳ Défi #<span x-text="defiNumber"></span> jusqu'à ce soir.</span></p>

                            {{-- Panneau réseaux : s'ouvre juste ICI, sous le bouton « Copier » --}}
                            <div x-show="shareOpen" style="max-width:460px;margin:0.85rem auto 0;background:#fff;border:1px solid var(--c-primary);border-radius:12px;padding:14px;">
                                <p x-show="pasteHint" style="font-weight:700;margin-bottom:0.6rem;color:var(--c-primary);">✅ Copié ! Maintenant <strong>défie ta gang</strong> : colle-le et tague quelqu'un qui se croit plus techno 😏 👇</p>
                                <p x-show="!pasteHint" style="font-weight:600;margin-bottom:0.6rem;color:var(--c-dark);">Partage ton défi 👇</p>
                                <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;">
                                    <button type="button" class="ct-btn ct-btn-outline" style="border-radius:999px;padding:6px 16px;" @click="shareNetwork('x')" aria-label="Partager sur X">𝕏 X</button>
                                    <button type="button" class="ct-btn ct-btn-outline" style="border-radius:999px;padding:6px 16px;" @click="shareNetwork('whatsapp')">💬 WhatsApp</button>
                                    <button type="button" class="ct-btn ct-btn-outline" style="border-radius:999px;padding:6px 16px;" @click="shareNetwork('linkedin')">💼 LinkedIn</button>
                                    <button type="button" class="ct-btn ct-btn-outline" style="border-radius:999px;padding:6px 16px;" @click="shareNetwork('facebook')">📘 Facebook</button>
                                    <button type="button" class="ct-btn ct-btn-outline" style="border-radius:999px;padding:6px 16px;" @click="copyShareLink()">🔗 Copier le lien</button>
                                </div>
                            </div>

                            {{-- Actions secondaires compactes (partage natif / carte / rejouer) --}}
                            <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center;margin-top:1rem;">
                                <button type="button" class="ct-btn ct-btn-outline" style="border-radius:8px;height:40px;padding:0 16px;" @click="share()">📣 Partager</button>
                                <button type="button" class="ct-btn ct-btn-outline" style="border-radius:8px;height:40px;padding:0 16px;" @click="shareCard('9:16')">📸 Carte story</button>
                                <button type="button" class="ct-btn ct-btn-outline" style="border-radius:8px;height:40px;padding:0 16px;" @click="shareCard('1:1')">⬜ Carré</button>
                                <button type="button" class="ct-btn ct-btn-outline" style="border-radius:8px;height:40px;padding:0 16px;" @click="replay()">🔄 Rejouer</button>
                            </div>

                            {{-- Révision --}}
                            <div x-show="questions.length > 0" style="margin-top:2rem;">
                                <h3 style="text-align:left;margin-bottom:1.25rem;font-weight:700;color:var(--c-dark);">Révision</h3>
                                <template x-for="(item, i) in questions" :key="i">
                                    <div style="background:#f9fafb;border-radius:12px;padding:16px;margin-bottom:1rem;text-align:left;">
                                        <p style="font-weight:600;color:var(--c-dark);margin-bottom:0.5rem;" x-text="(i+1)+'. '+item.question"></p>
                                        <p style="margin-bottom:0.5rem;" :style="isCorrect(i)?'color:#2E7D32;':'color:#B91C1C;'" x-text="reviewMy(i)"></p>
                                        <p x-show="!isCorrect(i)" style="color:#2E7D32;margin-bottom:0.5rem;" x-text="reviewSol(i)"></p>
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
function qtApp(payload, dailyPayload, dailyNumber) {
    return {
        phase: 'intro', questions: [], index: 0, answered: false, selected: null,
        lastCorrect: false, courtInput: '', matchSel: [],
        answers: [], qt: 0, displayQt: 0, correctCount: 0, rank: {},
        aiAnswered: false, aiBadge: '', aiUsage: '',
        isDefi: false, defiNumber: dailyNumber || 0, defiDoneToday: false, bestToday: 0,
        socialProof: null, streak: 0,

        init() {
            this.questions = JSON.parse(atob(payload));
            this.answers = Array(this.questions.length).fill(null);
            try {
                const done = localStorage.getItem('qt-defi-' + (dailyNumber || 0));
                if (done) { this.defiDoneToday = true; this.bestToday = parseInt(done) || 0; }
            } catch (e) {}
        },

        begin(mode) {
            this.isDefi = (mode === 'defi');
            this.questions = JSON.parse(atob(this.isDefi ? dailyPayload : payload));
            this.answers = Array(this.questions.length).fill(null);
            this.index = 0; this.answered = false; this.selected = null;
            this.aiAnswered = false; this.aiBadge = ''; this.aiUsage = '';
            this.prepInput();
            this.phase = 'quiz';
        },

        q() { return this.questions[this.index] || { choices: [] }; },

        choose(i) {
            if (this.answered) return;
            this.selected = i;
            this.lastCorrect = (i === this.q().correct);
            this.answered = true;
            this.answers[this.index] = i;
        },

        choiceClass(i) {
            if (!this.answered) return '';
            if (i === this.q().correct) return 'qt-correct';
            if (i === this.selected) return 'qt-wrong';
            return 'qt-dim';
        },

        // --- Types de questions (Phase 1) ---
        isChoiceType() { const t = this.q().type; return t === 'qcm' || t === 'vraifaux' || t === undefined; },
        typeLabel() {
            const t = this.q().type;
            if (t === 'vraifaux') return 'Vrai ou Faux';
            if (t === 'court') return 'Réponse courte';
            if (t === 'appariement') return 'Appariement';
            return 'Choix multiple';
        },
        normCourt(s) {
            return (s || '').toString().toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9 ]/g, ' ').replace(/\s+/g, ' ').trim();
        },
        validateCourt() {
            if (this.answered) return;
            if (!this.courtInput.trim()) return;
            const ok = (this.q().accepted || []).includes(this.normCourt(this.courtInput));
            this.lastCorrect = ok;
            this.answers[this.index] = this.courtInput;
            this.answered = true;
        },
        matchAllChosen() {
            const q = this.q();
            if (q.type !== 'appariement') return false;
            const n = (q.terms || []).length;
            if (this.matchSel.length < n) return false;
            for (let k = 0; k < n; k++) { const v = this.matchSel[k]; if (v === '' || v === null || v === undefined) return false; }
            return true;
        },
        validateMatch() {
            if (this.answered || !this.matchAllChosen()) return;
            const ans = this.q().answer || [];
            this.lastCorrect = ans.every((a, k) => parseInt(this.matchSel[k]) === a);
            this.answers[this.index] = this.matchSel.slice();
            this.answered = true;
        },
        isCorrect(i) {
            const q = this.questions[i]; const a = this.answers[i];
            if (a === null || a === undefined) return false;
            if (q.type === 'court') return (q.accepted || []).includes(this.normCourt(a));
            if (q.type === 'appariement') {
                const ans = q.answer || [];
                return Array.isArray(a) && a.length === ans.length && ans.every((x, k) => parseInt(a[k]) === x);
            }
            return a === q.correct;
        },
        prepInput() {
            this.lastCorrect = false; this.courtInput = '';
            this.pickedDef = null; this.cleanupDrag();
            const q = this.q();
            this.matchSel = (q.type === 'appariement') ? Array((q.terms || []).length).fill('') : [];
        },

        // --- Appariement : glisser-déposer (pointer events) + tap-pour-placer + clavier (WCAG 2.5.7) ---
        pickedDef: null,
        dragActive: false, dragIdx: null, dragOriginSlot: null, dragGhost: null,
        dragStartX: 0, dragStartY: 0, dragOffsetX: 0, dragOffsetY: 0,
        _dragStarted: false, _boundMove: null, _boundUp: null, ignoreNextClick: false,

        poolDefs() {
            const placed = new Set(this.matchSel.filter(v => v !== ''));
            return (this.q().defs || []).map((text, idx) => ({ idx, text })).filter(d => !placed.has(d.idx));
        },
        slotAria(k) {
            const term = this.q().terms[k];
            const has = this.matchSel[k] !== '';
            return 'Emplacement pour ' + term + (has ? ', contient : ' + this.q().defs[this.matchSel[k]] : ', vide') + '.';
        },
        pickDef(defIdx) {
            if (this.answered) return;
            if (this.ignoreNextClick) { this.ignoreNextClick = false; return; }
            this.pickedDef = (this.pickedDef === defIdx) ? null : defIdx;
        },
        onKeyDef(defIdx, originSlot) {
            if (this.answered) return;
            if (originSlot === null) { this.pickDef(defIdx); } else { this.returnToPool(originSlot); }
        },
        placeOnSlot(termIdx) {
            if (this.answered) return;
            if (this.pickedDef === null || this.pickedDef === '') return;
            this.placeDefOnSlot(this.pickedDef, termIdx);
        },
        placeDefOnSlot(defIdx, termIdx) {
            if (this.answered) return;
            const prev = this.matchSel.findIndex(v => v === defIdx);
            if (prev !== -1 && prev !== termIdx) this.matchSel[prev] = '';
            this.matchSel[termIdx] = defIdx;
            this.pickedDef = null;
        },
        returnToPool(termIdx) {
            if (this.answered) return;
            if (this.ignoreNextClick) { this.ignoreNextClick = false; return; }
            if (this.matchSel[termIdx] === '') return;
            if (this.pickedDef === this.matchSel[termIdx]) this.pickedDef = null;
            this.matchSel[termIdx] = '';
        },
        onKeySlot(termIdx) { if (!this.answered) this.placeOnSlot(termIdx); },
        onPointerDownDef(e, defIdx, originSlot) {
            if (this.answered) return;
            const srcEl = e.currentTarget;
            this.dragIdx = defIdx; this.dragOriginSlot = originSlot;
            this.dragStartX = e.clientX; this.dragStartY = e.clientY; this._dragStarted = false;
            this._boundMove = (ev) => this.onPointerMove(ev, srcEl);
            this._boundUp = (ev) => this.onPointerUp(ev);
            window.addEventListener('pointermove', this._boundMove, { passive: false });
            window.addEventListener('pointerup', this._boundUp, { passive: false });
            // NE PAS preventDefault ici : sinon le « click » du tap-pour-placer est supprimé dans certains navigateurs.
        },
        onPointerMove(e, sourceEl) {
            if (!this._dragStarted) {
                if (Math.hypot(e.clientX - this.dragStartX, e.clientY - this.dragStartY) > 6) { this.startDrag(e, sourceEl); }
                else { return; }
            }
            if (!this.dragActive || !this.dragGhost) return;
            if (e.cancelable) e.preventDefault(); // pendant le glissement seulement : évite sélection de texte / scroll
            this.dragGhost.style.transform = 'translate(' + (e.clientX - this.dragOffsetX) + 'px,' + (e.clientY - this.dragOffsetY) + 'px)';
        },
        startDrag(e, sourceEl) {
            this._dragStarted = true; this.dragActive = true;
            const rect = sourceEl && sourceEl.getBoundingClientRect ? sourceEl.getBoundingClientRect() : { width: 220, left: e.clientX - 16, top: e.clientY - 16 };
            this.dragGhost = document.createElement('div');
            this.dragGhost.className = 'qt-mghost qt-mblock';
            this.dragGhost.textContent = this.q().defs[this.dragIdx];
            this.dragGhost.style.width = rect.width + 'px';
            document.body.appendChild(this.dragGhost);
            this.dragOffsetX = e.clientX - rect.left; this.dragOffsetY = e.clientY - rect.top;
            this.dragGhost.style.transform = 'translate(' + (e.clientX - this.dragOffsetX) + 'px,' + (e.clientY - this.dragOffsetY) + 'px)';
        },
        onPointerUp(e) {
            window.removeEventListener('pointermove', this._boundMove, { passive: false });
            window.removeEventListener('pointerup', this._boundUp, { passive: false });
            if (this._dragStarted) {
                const el = document.elementFromPoint(e.clientX, e.clientY);
                const slotEl = el && el.closest ? el.closest('[data-slot-index]') : null;
                if (slotEl && !this.answered) {
                    const termIdx = parseInt(slotEl.getAttribute('data-slot-index'));
                    if (!Number.isNaN(termIdx)) this.placeDefOnSlot(this.dragIdx, termIdx);
                }
                this.ignoreNextClick = true;
                this.cleanupDrag();
            }
        },
        cleanupDrag() {
            this.dragActive = false; this._dragStarted = false; this.dragIdx = null; this.dragOriginSlot = null;
            if (this.dragGhost && this.dragGhost.remove) this.dragGhost.remove();
            this.dragGhost = null;
        },
        reviewMy(i) {
            const q = this.questions[i]; const a = this.answers[i];
            if (a === null || a === undefined) return '—';
            const mark = this.isCorrect(i) ? '✅ ' : '❌ ';
            if (q.type === 'court') return mark + 'Ta réponse : ' + a;
            if (q.type === 'appariement') return mark + (this.isCorrect(i) ? 'Toutes les paires correctes' : 'Certaines associations sont fausses');
            return mark + 'Ta réponse : ' + (q.choices ? q.choices[a] : a);
        },
        reviewSol(i) {
            const q = this.questions[i];
            if (q.type === 'court') return 'Bonne réponse : ' + (q.display || '');
            if (q.type === 'appariement') return (q.terms || []).map((t, k) => t + ' → ' + q.defs[q.answer[k]]).join(' · ');
            return 'Bonne réponse : ' + (q.choices ? q.choices[q.correct] : '');
        },

        next() {
            if (this.index < 9) { this.index++; this.answered = false; this.selected = null; this.prepInput(); }
            else { this.finish(); }
        },

        finish() {
            let earned = 0, total = 0, correct = 0;
            this.questions.forEach((q, i) => {
                total += q.points;
                if (this.isCorrect(i)) { earned += q.points; correct++; }
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
                if (this.isDefi) {
                    localStorage.setItem('qt-defi-' + this.defiNumber, this.qt.toString());
                    this.defiDoneToday = true; this.bestToday = this.qt;
                }
            } catch (e) {}
            this.computeStreak();
            this.recordAttempt();
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

        themeStats() {
            const map = {
                ia: { key: 'ia', label: 'IA', emoji: '🧠' },
                numerique: { key: 'numerique', label: 'Numérique', emoji: '📱' },
                securite: { key: 'securite', label: 'Sécurité', emoji: '🔒' },
                web: { key: 'web', label: 'Web', emoji: '🌐' },
                donnees: { key: 'donnees', label: 'Données', emoji: '📊' }
            };
            const stats = {};
            this.questions.forEach((q, i) => {
                if (!map[q.theme]) return;
                if (!stats[q.theme]) stats[q.theme] = { ...map[q.theme], correct: 0, total: 0 };
                stats[q.theme].total++;
                if (this.isCorrect(i)) stats[q.theme].correct++;
            });
            return Object.values(stats)
                .map(t => ({ ...t, pct: t.total > 0 ? Math.round((t.correct / t.total) * 100) : 0 }))
                .sort((a, b) => b.pct - a.pct);
        },

        barColor(pct) {
            if (pct >= 67) return '#2E7D32';
            if (pct >= 34) return '#B7791F';
            return '#C0392B';
        },

        weakFiches() {
            const seen = new Set();
            const fiches = [];
            this.questions.forEach((q, i) => {
                if (q.fiche && !this.isCorrect(i) && !seen.has(q.fiche)) {
                    seen.add(q.fiche);
                    try {
                        const slug = new URL(q.fiche).pathname.split('/').pop().split('?')[0];
                        const label = slug.split('-').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
                        fiches.push({ label, url: q.fiche });
                    } catch (e) {}
                }
            });
            return fiches.slice(0, 6);
        },

        buildCardCanvas(ratio) {
            const sizes = ratio === '9:16' ? { width: 1080, height: 1920 } : { width: 1080, height: 1080 };
            const canvas = Object.assign(document.createElement('canvas'), sizes);
            const ctx = canvas.getContext('2d');
            const cols = [...this.rank.bg.matchAll(/#([0-9a-fA-F]{6})/g)].map(m => m[0]);
            const c1 = cols[0] || '#0B7285', c2 = cols[1] || '#064E5A';
            const g = ctx.createLinearGradient(0, 0, sizes.width, sizes.height);
            g.addColorStop(0, c1); g.addColorStop(1, c2);
            ctx.fillStyle = g; ctx.fillRect(0, 0, sizes.width, sizes.height);
            ctx.fillStyle = '#fff'; ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            const ff = 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';
            const h = sizes.height;
            const draw = (text, y, size, weight = 'normal', alpha = 1) => {
                ctx.globalAlpha = alpha;
                ctx.font = weight + ' ' + Math.round(size) + 'px ' + ff;
                ctx.shadowColor = 'rgba(0,0,0,0.28)'; ctx.shadowBlur = 8; ctx.shadowOffsetY = 2;
                ctx.fillText(text, sizes.width / 2, y);
                ctx.shadowBlur = 0; ctx.shadowOffsetY = 0; ctx.globalAlpha = 1;
            };
            draw('QT — Quotient Techno', h * 0.12, h * 0.04, 'bold');
            draw(this.rank.emoji, h * 0.28, h * 0.14);
            draw(String(this.qt), h * 0.48, h * 0.22, 'bold');
            draw(this.rank.label, h * 0.66, h * 0.06, 'bold');
            draw(this.correctCount + ' / 10', h * 0.74, h * 0.035);
            draw("Et toi, c'est quoi ton QT ?", h * 0.88, h * 0.035);
            draw('laveille.ai/outils/qt', h * 0.93, h * 0.03, 'normal', 0.8);
            return canvas;
        },

        downloadCard(ratio) {
            this.buildCardCanvas(ratio).toBlob((blob) => {
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url; a.download = 'mon-quotient-techno.png';
                document.body.appendChild(a); a.click(); document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }, 'image/png');
        },

        async shareCard(ratio) {
            const canvas = this.buildCardCanvas(ratio);
            const blob = await new Promise((res) => canvas.toBlob(res, 'image/png'));
            const file = new File([blob], 'mon-quotient-techno.png', { type: 'image/png' });
            const toast = (m) => window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: m, variant: 'success', duration: 3000 } }));
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                try {
                    await navigator.share({ files: [file], title: 'QT — Quotient Techno', text: '🧠 Mon Quotient Techno : ' + this.qt + ' — ' + this.rank.label + ' ' + this.rank.emoji + '. Et toi ?' });
                } catch (e) {}
            } else {
                this.downloadCard(ratio);
                toast('Image téléchargée 📸');
            }
        },

        shareOpen: false, pasteHint: false,

        shareUrl(source) {
            return window.location.origin + '/outils/qt?utm_source=' + source + '&utm_medium=social&utm_campaign=qt';
        },

        shareText() {
            let tag = '';
            if (this.aiUsage === 'brain') tag = ' (100 % cerveau 🧠)';
            else if (this.aiUsage === 'ai') tag = " (avec l'aide d'une IA 🤖)";
            else if (this.aiUsage === 'hint') tag = ' (un peu aidé 🤝)';
            return '🧠 Mon Quotient Techno : ' + this.qt + ' — ' + this.rank.label + ' ' + this.rank.emoji + tag + ". Et toi, c'est quoi ton QT ? 👉";
        },

        emojiGrid() {
            return this.questions.map((q, i) => this.isCorrect(i) ? '✅' : '❌').join('');
        },

        aiTag() {
            if (this.aiUsage === 'ai') return ' · 🤖';
            if (this.aiUsage === 'hint') return ' · 🤝';
            if (this.aiUsage === 'brain') return ' · 🧠';
            return '';
        },

        resultBlock() {
            const head = this.isDefi
                ? '🏆 QT — Défi du jour #' + this.defiNumber
                : '🧠 QT — Quotient Techno';
            return head
                + '\n' + this.qt + ' ' + this.rank.emoji + ' ' + this.rank.label
                + '\n' + this.emojiGrid() + '  ' + this.correctCount + '/10' + this.aiTag()
                + '\n\nEt toi, ton QT ? 👉 lurl.ca/qt'
                + '\n#QuotientTechno';
        },

        async copyResult() {
            const toast = (m, v) => window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: m, variant: v, duration: 3000 } }));
            if (!navigator.clipboard || !navigator.clipboard.writeText) { toast('Copie non disponible sur ce navigateur', 'warning'); return; }
            try {
                await navigator.clipboard.writeText(this.resultBlock());
                toast('Résultat copié ! 📋', 'success');
                this.pasteHint = true;
                this.shareOpen = true;
            } catch (e) {
                toast('Copie impossible', 'danger');
            }
        },

        isMobileShare() {
            if (!navigator.share) return false;
            if (navigator.userAgentData && navigator.userAgentData.mobile) return true;
            if (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) return true;
            return /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);
        },

        async share() {
            if (this.isMobileShare()) {
                try {
                    await navigator.share({ title: 'QT — Quotient Techno', text: this.resultBlock() });
                } catch (e) {}
            } else {
                this.pasteHint = false;
                this.shareOpen = true;
            }
        },

        shareNetwork(net) {
            const text = this.shareText();
            const url = this.shareUrl(net);
            let u = '';
            if (net === 'x') u = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(text + ' #QuotientTechno') + '&url=' + encodeURIComponent(url);
            else if (net === 'whatsapp') u = 'https://wa.me/?text=' + encodeURIComponent(text + ' ' + url);
            else if (net === 'linkedin') u = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(url);
            else if (net === 'facebook') u = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
            else return;
            window.open(u, '_blank', 'noopener');
        },

        copyShareLink() {
            const full = this.shareText() + ' ' + this.shareUrl('copie');
            const toast = (m, v) => window.dispatchEvent(new CustomEvent('toast-show', { detail: { message: m, variant: v, duration: 3000 } }));
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(full).then(() => toast('Copié ! Tu peux le coller où tu veux 📋', 'success')).catch(() => toast('Copie impossible', 'danger'));
            } else {
                toast('Copie non disponible sur ce navigateur', 'warning');
            }
        },

        recordAttempt() {
            try {
                const t = document.querySelector('meta[name=csrf-token]')?.content;
                fetch('/outils/qt/attempt', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': t || '' },
                    body: JSON.stringify({ qt: this.qt, mode: this.isDefi ? 'defi' : 'libre', defi_number: this.defiNumber, correct: this.correctCount })
                }).then(r => r.json()).then(d => { if (d && d.ok) { this.socialProof = d; } }).catch(() => {});
            } catch (e) {}
        },

        computeStreak() {
            if (!this.isDefi) return;
            try {
                const last = parseInt(localStorage.getItem('qt-streak-day'));
                const count = parseInt(localStorage.getItem('qt-streak')) || 0;
                let streak = 1;
                if (last === this.defiNumber) { streak = count; }
                else if (last === this.defiNumber - 1) { streak = count + 1; }
                localStorage.setItem('qt-streak-day', String(this.defiNumber));
                localStorage.setItem('qt-streak', String(streak));
                this.streak = streak;
            } catch (e) {}
        },

        replay() { window.location.reload(); }
    };
}
</script>
@endsection
