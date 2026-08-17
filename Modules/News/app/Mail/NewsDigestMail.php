<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\News\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Modules\Core\Mail\Traits\RoutesToWorkspaceMailer;

/**
 * Resume quotidien GROUPE des actualites collectees non publiees (brouillons) - voir
 * Modules\News\Console\NotifyNewsDigestCommand pour le calcul de l'activite nouvelle et son
 * idempotence (curseur Setting 'news.digest_last_sent_at'). Message transactionnel vers
 * l'administrateur du site (config('app.superadmin_email')) - ne contient JAMAIS le texte
 * integral d'une source, seulement titre, resume deja produit (NewsArticle::displayExcerpt) et
 * lien vers l'ecran de composition.
 */
class NewsDigestMail extends Mailable
{
    use Queueable, RoutesToWorkspaceMailer, SerializesModels;

    public function __construct(
        public Collection $articles,
        public int $totalCount,
        public int $maxItems,
    ) {}

    public function build(): static
    {
        $this->routeToWorkspaceMailer();

        $items = $this->articles->map(fn ($article) => [
            'title' => $article->title,
            // displayExcerpt() gere deja la cascade "resume, sinon accroche structuree, sinon
            // repli generique" et n'expose jamais le texte source integral (description /
            // internal_source_text) - unique source de verite pour un extrait sur ce courriel.
            'excerpt' => $article->displayExcerpt(220),
            'source' => $article->source?->name,
            'score' => $article->relevance_score,
            'compose_url' => route('admin.news.composition.show', ['article' => $article->slug]),
            // ACTION : améliorations en attente (design doc "Actus - composition manuelle
            // assistée" 2026-08-15, point 5) - mini-prompt /actu2 copiable directement dans le
            // courriel, pour éviter de recliquer "Copier" une fois sur l'écran de composition.
            // resolved_url ?: url = MÊME règle que selectedArticle.source_url côté client
            // (composition-builder.blade.php, copyQuickPrompt()) - un seul calcul, deux
            // affichages, aucune divergence.
            // MCP: SELF (<5 lignes)
            // RAISON: design doc, section "Améliorations en attente (2026-08-17)", point 5.
            'mini_prompt' => '/actu2 '.($article->resolved_url ?: $article->url).' fiche:'.$article->id,
        ]);

        $shownCount = $items->count();
        $moreCount = max(0, $this->totalCount - $shownCount);

        return $this->subject($this->totalCount > 1
                ? "{$this->totalCount} nouvelles actualités à trier"
                : 'Une nouvelle actualité à trier')
            ->markdown('news::emails.digest')
            ->with([
                'items' => $items,
                'total_count' => $this->totalCount,
                'shown_count' => $shownCount,
                'more_count' => $moreCount,
                'composition_index_url' => route('admin.news.composition.index'),
                'brand_name' => config('app.name'),
            ]);
    }
}
