@extends(fronttheme_layout())

@section('title', $chapter['title'] . ' — ' . $meta['title'] . ' · La veille')
@section('meta_description', 'Chapitre ' . $chapter['number'] . ' : ' . $chapter['subtitle'])

@push('robots')
<meta name="robots" content="noindex, nofollow">
@endpush

@section('content')
<style>
/* Quête narrative — utilise les tokens charte globale (:root) sans shadowing */
[x-cloak] { display: none !important; }
.quest-ch { padding: 1.5rem 1rem 4rem; max-width: 760px; margin: 0 auto; font-family: var(--f-body); color: var(--c-text-secondary); }
.quest-ch *, .quest-ch *::before, .quest-ch *::after { box-sizing: border-box; }
.quest-ch h1, .quest-ch h2 { font-family: var(--f-heading); color: var(--c-dark); }
.quest-ch__crumbs { display: flex; align-items: center; gap: .5rem; font-size: .875rem; color: var(--c-text-muted); margin-bottom: 1rem; }
.quest-ch__crumbs a { color: var(--c-primary); font-weight: 600; text-decoration: none; }
.quest-ch__crumbs a:hover { color: var(--c-primary-hover); text-decoration: underline; }
.quest-ch__head { background: linear-gradient(135deg, var(--c-primary) 0%, var(--c-primary-hover) 100%); color: #fff; border-radius: var(--r-base); padding: 1.75rem 1.5rem; margin-bottom: 1.5rem; box-shadow: 0 4px 14px rgba(11, 114, 133, .14); }
.quest-ch__act { font-size: .75rem; text-transform: uppercase; letter-spacing: 1.5px; opacity: .92; font-weight: 700; font-family: var(--f-heading); }
.quest-ch__title { margin: .5rem 0 .35rem; font-size: clamp(1.5rem, 4vw, 2rem); line-height: 1.2; color: #fff; font-weight: 800; }
.quest-ch__subtitle { margin: 0; opacity: .95; font-size: 1rem; line-height: 1.5; }
.quest-ch__meta { display: flex; gap: .5rem; margin-top: 1rem; flex-wrap: wrap; }
.quest-ch__chip { display: inline-flex; align-items: center; gap: .35rem; background: rgba(255,255,255,.18); padding: .35rem .85rem; border-radius: 999px; font-size: .8rem; font-weight: 600; font-family: var(--f-heading); }
.quest-ch__progress { background: var(--c-primary-light); padding: .85rem 1.15rem; border-radius: var(--r-base); margin-bottom: 1.25rem; display: flex; align-items: center; gap: 1rem; border: 1px solid var(--c-primary-badge); }
.quest-ch__progress-bar { flex: 1; background: #fff; height: 8px; border-radius: 999px; overflow: hidden; box-shadow: inset 0 0 0 1px var(--c-primary-badge); }
.quest-ch__progress-fill { background: var(--c-accent); height: 100%; transition: width .3s ease; }
.quest-ch__progress-label { font-size: .85rem; color: var(--c-text-muted); font-weight: 700; font-family: var(--f-heading); white-space: nowrap; }
.quest-opening { background: var(--c-accent-light); border-left: 4px solid var(--c-accent); border-radius: var(--r-base); padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; font-style: italic; color: var(--c-dark); line-height: 1.7; }
.quest-scene { background: #fff; border-radius: var(--r-base); padding: 1.75rem 1.5rem; margin-bottom: 1.25rem; border: 1px solid #E5E7EB; box-shadow: 0 2px 8px rgba(15, 23, 42, .04); }
.quest-scene__narrative { font-size: 1.0625rem; line-height: 1.7; color: var(--c-dark); }
.quest-scene__narrative p { margin: 0 0 .85rem; }
.quest-scene__narrative p:last-child { margin-bottom: 0; }
.quest-scene__narrative em { color: var(--c-primary); font-style: italic; font-weight: 600; }
.quest-scene__narrative strong { color: var(--c-accent); }
.quest-scene__question { margin: 1.5rem 0 .85rem; font-weight: 700; color: var(--c-primary); font-size: 1.0625rem; font-family: var(--f-heading); }
.quest-choices { display: flex; flex-direction: column; gap: .75rem; margin-top: 1rem; }
.quest-choice { display: flex; align-items: center; gap: .85rem; padding: 1rem 1.25rem; background: var(--c-surface); border: 2px solid #E5E7EB; border-radius: var(--r-base); cursor: pointer; text-align: left; font-size: .9375rem; color: var(--c-dark); transition: border-color .15s ease, background .15s ease, transform .15s ease; min-height: 56px; font-family: var(--f-body); }
.quest-choice:hover { border-color: var(--c-accent); background: #fff; transform: translateX(2px); }
.quest-choice:focus-visible { outline: 3px solid var(--c-accent); outline-offset: 2px; border-color: var(--c-accent); }
.quest-choice__letter { background: var(--c-primary); color: #fff; width: 32px; height: 32px; min-width: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: .9rem; flex-shrink: 0; font-family: var(--f-heading); }
.quest-choice__text { flex: 1; line-height: 1.45; }
.quest-ending { background: linear-gradient(135deg, var(--c-accent-light) 0%, #fff 100%); border: 2px solid var(--c-accent); border-radius: var(--r-base); padding: 2rem 1.5rem; text-align: center; }
.quest-ending__badge { font-size: 4rem; line-height: 1; margin: 1rem 0 .5rem; animation: badgePop .6s ease-out; }
@keyframes badgePop { 0% { transform: scale(0); } 60% { transform: scale(1.2); } 100% { transform: scale(1); } }
.quest-ending__title { color: var(--c-primary); margin: 0 0 .5rem; font-size: 1.5rem; font-family: var(--f-heading); font-weight: 800; }
.quest-ending__desc { color: var(--c-text-muted); margin: 0 0 1.5rem; }
.quest-actions { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; justify-content: center; }
.quest-btn { display: inline-flex; align-items: center; gap: .35rem; padding: .75rem 1.5rem; border-radius: var(--r-btn); font-weight: 700; cursor: pointer; text-decoration: none; min-height: 44px; transition: background .15s ease, color .15s ease, border-color .15s ease; font-family: var(--f-heading); font-size: .95rem; }
.quest-btn--primary { background: var(--c-primary); color: #fff; border: 2px solid var(--c-primary); }
.quest-btn--primary:hover, .quest-btn--primary:focus-visible { background: var(--c-primary-hover); border-color: var(--c-primary-hover); color: #fff; outline: 3px solid var(--c-accent); outline-offset: 2px; }
.quest-btn--outline { background: transparent; color: var(--c-primary); border: 2px solid var(--c-primary); }
.quest-btn--outline:hover, .quest-btn--outline:focus-visible { background: var(--c-primary); color: #fff; outline: 3px solid var(--c-accent); outline-offset: 2px; }
.quest-btn--ghost { background: transparent; color: var(--c-text-muted); border: 1px solid #D5D9E0; }
.quest-btn--ghost:hover, .quest-btn--ghost:focus-visible { background: var(--c-surface); color: var(--c-dark); border-color: var(--c-primary); outline: 3px solid var(--c-accent); outline-offset: 2px; }
.sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
@media (prefers-reduced-motion: reduce) {
    .quest-choice, .quest-btn { transition: none; }
    .quest-choice:hover { transform: none; }
    .quest-ending__badge { animation: none; }
    .quest-ch__progress-fill { transition: none; }
}
@media (max-width: 480px) {
    .quest-ch { padding: 1rem .85rem 3rem; }
    .quest-ch__head { padding: 1.25rem 1.15rem; border-radius: var(--r-btn); }
    .quest-scene { padding: 1.25rem 1.15rem; }
    .quest-choice { padding: .85rem 1rem; min-height: 44px; }
}
</style>

<div class="quest-ch" x-data="chapterApp(@js($chapter), @js($email), @js($badges))" x-cloak>
    <nav class="quest-ch__crumbs" aria-label="Fil d'ariane">
        <a href="{{ route('tools.quest.index') }}">← Retour à la carte</a>
    </nav>

    <header class="quest-ch__head">
        <div class="quest-ch__act">{{ $chapter['act'] }} · Chapitre {{ $chapter['number'] }}</div>
        <h1 class="quest-ch__title">{{ $chapter['title'] }}</h1>
        <p class="quest-ch__subtitle">{{ $chapter['subtitle'] }}</p>
        <div class="quest-ch__meta">
            <span class="quest-ch__chip">⏱ {{ $chapter['estimated_minutes'] }} min</span>
            <span class="quest-ch__chip">📖 {{ $chapter['concept_taught'] }}</span>
        </div>
    </header>

    <div class="quest-ch__progress" aria-label="Progression du chapitre">
        <span class="quest-ch__progress-label" x-text="`Scène ${sceneIndex + 1} / ${totalScenes}`"></span>
        <div class="quest-ch__progress-bar" role="progressbar" :aria-valuenow="sceneIndex + 1" :aria-valuemin="1" :aria-valuemax="totalScenes">
            <div class="quest-ch__progress-fill" :style="`width: ${((sceneIndex + 1) / totalScenes) * 100}%`"></div>
        </div>
    </div>

    <div class="quest-opening" x-show="sceneIndex === 0 && !isEnding" aria-label="Ouverture du chapitre">
        {!! $chapter['opening'] !!}
    </div>

    <article class="quest-scene" x-show="!isEnding" role="region" aria-live="polite">
        <div class="quest-scene__narrative" x-html="currentScene.narrative"></div>
        <p class="quest-scene__question" x-show="currentScene.question" x-text="currentScene.question"></p>
        <div class="quest-choices" x-show="currentScene.choices && currentScene.choices.length">
            <template x-for="(choice, idx) in currentScene.choices" :key="choice.id">
                <button class="quest-choice" type="button" @click="selectChoice(choice)" :aria-label="`Choix ${String.fromCharCode(65 + idx)} : ${choice.label}`">
                    <span class="quest-choice__letter" x-text="String.fromCharCode(65 + idx)" aria-hidden="true"></span>
                    <span class="quest-choice__text" x-text="choice.label"></span>
                </button>
            </template>
        </div>
    </article>

    <section class="quest-ending" x-show="isEnding" role="region" aria-live="polite">
        <div class="quest-scene__narrative" x-html="currentScene.narrative" style="text-align:left;"></div>
        <template x-if="earnedBadge">
            <div>
                <div class="quest-ending__badge" x-text="earnedBadge.icon"></div>
                <h2 class="quest-ending__title">Badge débloqué : <span x-text="earnedBadge.name"></span></h2>
                <p class="quest-ending__desc" x-text="earnedBadge.description"></p>
            </div>
        </template>
        <div class="quest-actions">
            <a href="{{ route('tools.quest.index') }}" class="quest-btn quest-btn--primary">📜 Retour à la carte</a>
            <button type="button" class="quest-btn quest-btn--outline" @click="resetChapter()">↻ Rejouer ce chapitre</button>
        </div>
    </section>

    <div class="quest-actions" x-show="!isEnding">
        <button type="button" class="quest-btn quest-btn--ghost" @click="resetChapter()">↻ Recommencer</button>
        <a href="{{ route('tools.quest.index') }}" class="quest-btn quest-btn--ghost">← Carte</a>
    </div>
</div>

<script>
function chapterApp(chapter, email, badges) {
    return {
        chapter,
        email,
        badges,
        scenes: chapter.scenes,
        sceneId: chapter.scenes[0]?.id ?? null,
        choicesMade: [],
        isEnding: false,
        earnedBadge: null,
        completed: false,

        get totalScenes() { return this.scenes.length; },
        get sceneIndex() { return Math.max(0, this.scenes.findIndex(s => s.id === this.sceneId)); },
        get currentScene() { return this.scenes.find(s => s.id === this.sceneId) ?? this.scenes[0]; },

        init() {
            this.checkEnding();
        },

        selectChoice(choice) {
            if (! choice) return;
            this.choicesMade.push({ scene: this.sceneId, choice: choice.id });
            if (choice.next) {
                this.sceneId = choice.next;
                this.checkEnding();
                this.$nextTick(() => { window.scrollTo({ top: 0, behavior: 'smooth' }); });
            }
        },

        checkEnding() {
            const s = this.currentScene;
            if (s && s.is_ending) {
                this.isEnding = true;
                if (s.badge_earned && this.badges[s.badge_earned]) {
                    this.earnedBadge = this.badges[s.badge_earned];
                }
                this.persistCompletion();
            }
        },

        async persistCompletion() {
            if (this.completed) return;
            this.completed = true;
            try {
                await fetch('{{ route("tools.quest.complete", ["slug" => $chapter["slug"]]) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' },
                    body: JSON.stringify({
                        choices: this.choicesMade,
                        badge_earned: this.earnedBadge?.id ?? null,
                    }),
                });
            } catch (e) { console.error('Quest complete fail', e); }

            try {
                const key = 'lv_quest_v1';
                const data = JSON.parse(localStorage.getItem(key) || '{"completed":[],"badges":[]}');
                if (! data.completed.includes(chapter.slug)) data.completed.push(chapter.slug);
                if (this.earnedBadge && ! data.badges.includes(this.earnedBadge.id)) data.badges.push(this.earnedBadge.id);
                localStorage.setItem(key, JSON.stringify(data));
            } catch (e) {}
        },

        resetChapter() {
            this.sceneId = this.scenes[0].id;
            this.choicesMade = [];
            this.isEnding = false;
            this.earnedBadge = null;
            this.completed = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    };
}
</script>
@endsection
