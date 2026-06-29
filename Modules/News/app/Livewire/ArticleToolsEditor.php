<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Éditeur inline « Outils liés » sur la page publique d'une actualité.
 * Réservé aux admins (gâté view_admin_panel), façon « gestion 100 % front-end ».
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR - NON NÉGOCIABLE) :
 *  - L'identifiant de l'article est figé au montage (#[Locked]).
 *  - À CHAQUE mutation, l'article est RE-RÉSOLU depuis la BD et ré-autorisé.
 *  - On ne fait JAMAIS confiance aux IDs venant du navigateur.
 */

declare(strict_types=1);

namespace Modules\News\Livewire;

use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Directory\Models\Tool;
use Modules\News\Actions\NewsToolSyncAction;
use Modules\News\Models\NewsArticle;

class ArticleToolsEditor extends Component
{
    /** Identifiant de l'article (figé au montage, source de vérité serveur). */
    #[Locked]
    public int $articleId;

    /**
     * IDs des outils actuellement sélectionnés.
     *
     * @var array<int, int>
     */
    public array $selectedToolIds = [];

    /**
     * Catalogue complet des outils publiés (sérialisé dans le snapshot Livewire).
     * Filtrage par nom fait côté client via Alpine.js.
     *
     * @var array<int, array{id: int, name: string, slug: string}>
     */
    public array $allTools = [];

    public function mount(NewsArticle $article): void
    {
        $this->authorize('view_admin_panel');

        $this->articleId = $article->id;

        $this->selectedToolIds = $article->tools()
            ->pluck('directory_tools.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $locale = app()->getLocale();

        $this->allTools = Tool::published()
            ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"fr_CA\"'))")
            ->get(['id', 'name', 'slug'])
            ->map(fn (Tool $tool) => [
                'id'   => $tool->id,
                'name' => $tool->getTranslation('name', $locale, false)
                    ?: $tool->getTranslation('name', 'fr_CA', false)
                    ?: $tool->getTranslation('name', 'en', false)
                    ?: '',
                'slug' => $tool->getTranslation('slug', $locale, false)
                    ?: $tool->getTranslation('slug', 'fr_CA', false)
                    ?: $tool->getTranslation('slug', 'en', false)
                    ?: '',
            ])
            ->values()
            ->all();
    }

    /** Ajoute un outil à la sélection (sans enregistrer). */
    public function addTool(int $id): void
    {
        $this->authorize('view_admin_panel');

        if (! in_array($id, $this->selectedToolIds, true)) {
            $this->selectedToolIds[] = $id;
        }
    }

    /** Retire un outil de la sélection (sans enregistrer). */
    public function removeTool(int $id): void
    {
        $this->authorize('view_admin_panel');

        $this->selectedToolIds = array_values(
            array_filter($this->selectedToolIds, fn (int $v) => $v !== $id)
        );
    }

    /** Enregistre la sélection en base (sync pivot). */
    public function save(): void
    {
        $article = NewsArticle::findOrFail($this->articleId);
        $this->authorize('view_admin_panel');

        app(NewsToolSyncAction::class)->sync($article, $this->selectedToolIds);

        // Recharge depuis la BD pour refléter les IDs validés.
        $this->selectedToolIds = $article->tools()
            ->pluck('directory_tools.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        session()->flash('news_tools_editor_status', 'Outils enregistrés.');
    }

    /** Suggère les outils détectés automatiquement (sans enregistrer). */
    public function suggestTools(): void
    {
        $article = NewsArticle::findOrFail($this->articleId);
        $this->authorize('view_admin_panel');

        $suggested = app(NewsToolSyncAction::class)->suggest($article);

        $merged = collect($this->selectedToolIds)
            ->merge($suggested)
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->selectedToolIds = $merged;

        session()->flash('news_tools_editor_status', count($suggested) . ' outil(s) suggéré(s) ajouté(s).');
    }

    public function render(): \Illuminate\View\View
    {
        return view('news::livewire.article-tools-editor');
    }
}
