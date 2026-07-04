{{--
    Popup rapide « Outils liés » (icône engrenage sur la liste /actualites).
    Réutilise le modal générique x-core::modal et le composant ArticleToolsEditor
    tel quel - aucune logique dupliquée ici, juste l'aiguillage vers la bonne
    actualité choisie par l'admin.

    @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
    @project laveille.ai
--}}
<div>
    <x-core::modal name="news-tools-modal" title="{{ $article ? $article->title : __('Gérer les outils liés') }}" title-icon="🔧" max-width="640px">
        @if($article)
            <livewire:news.article-tools-editor :article="$article" :key="'ate-modal-'.$article->id" />
        @else
            <p style="color:#6b7280; font-size:0.9rem;">{{ __('Sélectionnez une actualité pour gérer ses outils.') }}</p>
        @endif
    </x-core::modal>
</div>
