<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Popup rapide « Outils liés » depuis la liste /actualites (icône engrenage sur
 * chaque carte). Réutilise ArticleToolsEditor tel quel (zéro duplication) : ce
 * wrapper se contente de choisir QUELLE actualité éditer et de piloter le modal
 * générique x-core::modal existant. L'autorisation reste vérifiée à deux niveaux
 * (ici ET dans ArticleToolsEditor::mount()) - défense en profondeur, pas une
 * duplication nuisible.
 */

declare(strict_types=1);

namespace Modules\News\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\News\Models\NewsArticle;

class NewsToolsQuickEditModal extends Component
{
    public ?int $articleId = null;

    #[On('open-news-tools-editor')]
    public function open(int $articleId): void
    {
        $this->authorize('view_admin_panel');

        $this->articleId = $articleId;
        $this->dispatch('open-news-tools-modal');
    }

    public function render(): View
    {
        return view('news::livewire.news-tools-quick-edit-modal', [
            'article' => $this->articleId ? NewsArticle::find($this->articleId) : null,
        ]);
    }
}
