<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 */

declare(strict_types=1);

namespace Modules\Journal\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalBlock extends Model
{
    protected $fillable = [
        'journal_id',
        'type',
        'source_type',
        'source_id',
        'payload',
        'sort_order',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * HTML du bloc, purifie avant affichage.
     *
     * Faille corrigee le 2026-08-26 : la vue affichait `{!! $block->payload['html'] !!}` en brut.
     * Or ce HTML est saisi par l'utilisateur (JournalBuilder), et JournalPolicy::view() autorise
     * la lecture a TOUT LE MONDE, visiteur anonyme compris, des que le journal est publie.
     * N'importe quel inscrit pouvait donc publier un journal porteur de HTML malveillant.
     *
     * Purification a l'AFFICHAGE et non a l'ecriture : cela protege aussi les blocs deja
     * enregistres, sans migration ni reecriture de donnees existantes.
     *
     * Meme approche que Article::safeContent() (Modules/Blog), pour rester coherent.
     */
    public function safeHtml(): string
    {
        $html = (string) ($this->payload['html'] ?? '');

        if ($html === '') {
            return '';
        }

        if (! class_exists(\Mews\Purifier\Facades\Purifier::class)) {
            // Sans purificateur, on echappe plutot que de laisser passer du HTML brut.
            return e($html);
        }

        // Profil 'article' et NON le profil par defaut : mesure du 2026-08-26, le profil par
        // defaut RETIRE h2/h3 et blockquote. L'editeur du journal (Tiptap) autorise pourtant les
        // titres h2/h3 et les citations, et la vue porte du CSS dedie a `.journal-block-content
        // blockquote`. Purifier avec le mauvais profil aurait donc mutile silencieusement des
        // journaux DEJA publies - une casse de contenu provoquee par un correctif de securite.
        // Verifie : le profil 'article' neutralise onerror et <script> aussi bien que le defaut.
        return \Mews\Purifier\Facades\Purifier::clean($html, 'article');
    }
}
