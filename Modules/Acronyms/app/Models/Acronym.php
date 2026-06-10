<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Acronyms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\HasAdminShareContents;
use Modules\Core\Contracts\Searchable;
use Modules\Core\Traits\HasPublishedState;
use Modules\Core\Traits\LogsActivityStandard;
use Modules\Directory\Traits\HasSuggestions;
use Spatie\Translatable\HasTranslations;

class Acronym extends Model implements Searchable
{
    use \Modules\Core\Traits\HasModerationStatus;
    use HasPublishedState;
    use HasSuggestions;
    use HasTranslations;
    use LogsActivityStandard;
    use \Modules\Voting\Traits\HasCommunityVotes;
    use HasAdminShareContents;

    protected array $activitylogFields = ['acronym', 'full_name', 'description', 'website_url', 'is_published'];
    protected string $activitylogName = 'acronym';

    protected array $suggestableFields = [
        'full_name' => 'Nom complet',
        'description' => 'Description',
        'website_url' => 'Site web',
        'other' => 'Autre',
    ];

    protected $table = 'acronyms';

    public array $translatable = ['acronym', 'full_name', 'slug', 'description', 'one_sentence_answer', 'analogy', 'example', 'did_you_know'];

    protected $fillable = [
        'acronym',
        'full_name',
        'slug',
        'description',
        'website_url',
        'logo_url',
        'domain',
        'acronym_category_id',
        'is_published',
        'match_strategy', // 2026-05-05 #145 WSD : loose | partial_case_sensitive | case_sensitive (défaut) | exact_phrase | never_auto
        'aliases', // 2026-05-05 #151 : variations d'écriture
        'sort_order',
        // 2026-06-10 #304 — enrichissement parité glossaire (AEO/SEO/GEO)
        'one_sentence_answer', 'analogy', 'example', 'did_you_know', 'faq', 'sources',
        'icon', 'difficulty', 'reference_url', 'reference_label', 'broader_slugs', 'narrower_slugs',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'aliases' => 'array',
        'faq' => 'array',
        'sources' => 'array',
        'broader_slugs' => 'array',
        'narrower_slugs' => 'array',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AcronymCategory::class, 'acronym_category_id');
    }

    // 2026-05-05 #146 : scopePublished mutualise via HasPublishedState (DRY Core).

    public function scopeOfDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    public static function searchableFields(): array
    {
        return ['acronym', 'full_name', 'description'];
    }

    public static function searchSectionKey(): string
    {
        return 'acronymes';
    }

    public static function searchSectionLabel(): string
    {
        return __('Acronymes');
    }

    public static function searchSectionIcon(): string
    {
        return '🔤';
    }

    public static function searchPriority(): int
    {
        return 50;
    }

    public function searchableResultTitle(): string
    {
        return $this->acronym;
    }

    public function searchableResultExcerpt(): string
    {
        return \Illuminate\Support\Str::limit(strip_tags($this->description ?: ''), 200);
    }

    public function searchableResultUrl(): string
    {
        return route('acronyms.show', $this->slug);
    }

    public function adminShareContents(): array
    {
        $acr = (string) ($this->acronym ?? '');
        $full = (string) ($this->full_name ?? '');
        $desc = (string) ($this->description ?? '');

        $resume = "# {$acr}\n\n";
        if ($full !== '') { $resume .= "## Signification\n{$full}\n\n"; }
        if ($desc !== '') { $resume .= "## Description\n{$desc}\n\n"; }
        $resume = $this->stripLinks($resume);

        $prompt = $this->infographiePrompt(
            'https://laveille.ai/acronymes-education',
            'Explique l\'acronyme « ' . $acr . ' » (' . $full . ') dans une infographie pédagogique simple. Public : débutants du milieu de l\'éducation.'
        );

        $social = $this->buildSocialPost(
            "tu sais ce que veut dire {$acr} ?",
            [$full, $desc],
            'garde ça pour plus tard, ça va resservir.',
            ['#Éducation', '#Acronymes', '#' . $this->normalizeShareHashtag($acr), '#Québec']
        );

        return [
            ['label' => 'Résumé (NotebookLM)', 'icon' => '📄', 'text' => $resume],
            ['label' => 'NotebookLM Infographie', 'icon' => '🤖', 'text' => $prompt],
            ['label' => 'Post réseaux sociaux', 'icon' => '📣', 'text' => $social],
        ];
    }
}
