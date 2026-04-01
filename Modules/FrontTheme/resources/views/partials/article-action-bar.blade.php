{{-- Barre d'interactions article — réutilisable (blog + actualités)
     Usage: @include('fronttheme::partials.article-action-bar', ['model' => $article, 'modelType' => 'Modules\\News\\Models\\NewsArticle']) --}}

<style>
.aab { display: flex; gap: 0.75rem; padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb; align-items: center; flex-wrap: wrap; }
.aab-btn { background: transparent; border: 1px solid #e5e7eb; border-radius: 8px; padding: 0.5rem 0.75rem; display: inline-flex; align-items: center; gap: 0.375rem; cursor: pointer; font-size: 0.8125rem; color: #374151; transition: all 0.15s; }
.aab-btn:hover { background: #f3f4f6; border-color: #d1d5db; }
.aab-btn svg { width: 18px; height: 18px; flex-shrink: 0; }
.aab-btn-active { color: #ef4444; border-color: #fecaca; }
.aab-feedback { font-size: 0.75rem; color: #10b981; font-weight: 500; }
.aab-report-form { position: absolute; top: 100%; left: 0; z-index: 100; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; width: 280px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
@media (max-width: 767px) { .aab-label { display: none; } }
</style>

<div class="aab" x-data="{
    saved: {{ (auth()->check() && Route::has('bookmark.toggle') && class_exists(\Modules\Core\Models\Bookmark::class) && \Modules\Core\Models\Bookmark::where('user_id', auth()->id())->where('bookmarkable_type', $modelType)->where('bookmarkable_id', $model->id)->exists()) ? 'true' : 'false' }},
    copied: false,
    showReport: false,
    reportReason: '',
    reportDetail: '',
    reportSent: false
}">

    {{-- Sauvegarder --}}
    @if(Route::has('bookmark.toggle'))
    <button class="aab-btn" :class="saved && 'aab-btn-active'"
        @click="
            @auth
                fetch('{{ route('bookmark.toggle') }}', {
                    method: 'POST',
                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
                    body: JSON.stringify({type: '{{ addslashes($modelType) }}', id: {{ $model->id }}})
                }).then(r => r.json()).then(d => saved = d.bookmarked)
            @else
                window.dispatchEvent(new CustomEvent('open-auth-modal'))
            @endauth
        "
        :title="saved ? '{{ __('Retirer des favoris') }}' : '{{ __('Sauvegarder') }}'">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" :fill="saved ? '#ef4444' : 'none'" :stroke="saved ? '#ef4444' : 'currentColor'" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        <span class="aab-label" x-text="saved ? '{{ __('Sauvegardé') }}' : '{{ __('Sauvegarder') }}'">{{ __('Sauvegarder') }}</span>
    </button>
    @endif

    {{-- Copier le lien --}}
    <button class="aab-btn" @click="navigator.clipboard.writeText(window.location.href); copied = true; setTimeout(() => copied = false, 2000)">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        <span class="aab-label" x-show="!copied">{{ __('Copier le lien') }}</span>
        <span class="aab-feedback" x-show="copied" x-cloak>{{ __('Copié !') }}</span>
    </button>

    {{-- Partage social --}}
    <a href="#" class="aab-btn" @click.prevent="window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(window.location.href), '_blank', 'noopener,noreferrer')" aria-label="{{ __('Partager sur Facebook') }}">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.128 22 16.991 22 12z"/></svg>
        <span class="aab-label">Facebook</span>
    </a>
    <a href="#" class="aab-btn" @click.prevent="window.open('https://twitter.com/intent/tweet?url=' + encodeURIComponent(window.location.href), '_blank', 'noopener,noreferrer')" aria-label="{{ __('Partager sur X') }}">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        <span class="aab-label">X</span>
    </a>
    <a href="#" class="aab-btn" @click.prevent="window.open('https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(window.location.href), '_blank', 'noopener,noreferrer')" aria-label="{{ __('Partager sur LinkedIn') }}">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
        <span class="aab-label">LinkedIn</span>
    </a>

    {{-- Signaler --}}
    @auth
    <div style="position: relative;">
        <button class="aab-btn" @click="showReport = !showReport">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
            <span class="aab-label">{{ __('Signaler') }}</span>
        </button>
        <div class="aab-report-form" x-show="showReport" x-cloak @click.outside="showReport = false" x-transition>
            <template x-if="!reportSent">
                <div>
                    <select x-model="reportReason" class="form-control" style="margin-bottom: 0.5rem; font-size: 0.8125rem;">
                        <option value="">{{ __('Raison du signalement') }}</option>
                        <option value="Contenu inexact">{{ __('Contenu inexact') }}</option>
                        <option value="Traduction incorrecte">{{ __('Traduction incorrecte') }}</option>
                        <option value="Lien cassé">{{ __('Lien cassé') }}</option>
                        <option value="Autre">{{ __('Autre') }}</option>
                    </select>
                    <textarea x-model="reportDetail" class="form-control" rows="2" placeholder="{{ __('Précisez si nécessaire...') }}" style="margin-bottom: 0.5rem; font-size: 0.8125rem;"></textarea>
                    <div style="display: flex; gap: 0.5rem;">
                        <button type="button" class="btn btn-default btn-sm" @click="showReport = false">{{ __('Annuler') }}</button>
                        <button type="button" class="btn btn-primary btn-sm" :disabled="!reportReason"
                            @click="
                                fetch('{{ Route::has('report.store') ? route('report.store') : '#' }}', {
                                    method: 'POST',
                                    headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
                                    body: JSON.stringify({reportable_type: '{{ addslashes($modelType) }}', reportable_id: {{ $model->id }}, reason: reportReason, details: reportDetail})
                                });
                                reportSent = true;
                                setTimeout(() => { showReport = false; reportSent = false; reportReason = ''; reportDetail = ''; }, 2000);
                            ">{{ __('Envoyer') }}</button>
                    </div>
                </div>
            </template>
            <template x-if="reportSent">
                <p style="text-align: center; color: #10b981; font-weight: 500; margin: 0;">{{ __('Merci pour votre signalement.') }}</p>
            </template>
        </div>
    </div>
    @endauth
</div>
