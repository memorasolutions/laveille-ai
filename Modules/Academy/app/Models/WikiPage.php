<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F19 - WIKI : page collaborative d'un item de leçon « wiki » (type Moodle « Wiki »).
 * Porte le contenu COURANT (title/body/revision) ; l'historique des états précédents vit
 * dans WikiRevision (hasMany). created_by = auteur d'origine, edited_by = dernier éditeur
 * (= auteur du contenu courant). is_home (page d'accueil) / is_locked (verrou gérant) sont
 * des drapeaux de modération. SoftDeletes : une page supprimée est conservée (audit) et
 * exclue des listes par le scope par défaut.
 */

declare(strict_types=1);

namespace Modules\Academy\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int         $id
 * @property int         $lesson_item_id
 * @property string      $title
 * @property string      $slug
 * @property string|null $body
 * @property int|null    $created_by
 * @property int|null    $edited_by
 * @property int         $revision
 * @property bool        $is_home
 * @property bool        $is_locked
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class WikiPage extends Model
{
    use SoftDeletes;

    protected $table = 'academy_wiki_pages';

    protected $fillable = [
        'lesson_item_id',
        'title',
        'slug',
        'body',
        'created_by',
        'edited_by',
        'revision',
        'is_home',
        'is_locked',
    ];

    protected $casts = [
        'lesson_item_id' => 'integer',
        'created_by'     => 'integer',
        'edited_by'      => 'integer',
        'revision'       => 'integer',
        'is_home'        => 'boolean',
        'is_locked'      => 'boolean',
    ];

    public function lessonItem(): BelongsTo
    {
        return $this->belongsTo(LessonItem::class, 'lesson_item_id');
    }

    /** Auteur d'origine (création de la page). */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Dernier éditeur (= auteur du contenu courant). */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(WikiRevision::class, 'wiki_page_id');
    }

    /** Scope : pages d'un item « wiki » donné. */
    public function scopeForItem(Builder $query, int $itemId): Builder
    {
        return $query->where('lesson_item_id', $itemId);
    }

    /**
     * Corps rendu en HTML SÛR (anti-XSS stockée) : même pipeline que ForumPost /
     * Announcement (LessonItem::renderRichText, html_input=strip + allow_unsafe_links=false).
     * Le résultat peut être rendu via {!! ... !!} en toute sûreté (aucun HTML brut ne survit).
     * Les liens inter-pages [[Titre]] sont gérés à part par WikiService::renderBody().
     */
    public function renderedBody(): string
    {
        return LessonItem::renderRichText($this->body);
    }
}
