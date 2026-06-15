<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Dictionary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Concerns\HasAdminShareContents;
use Modules\Core\Contracts\Searchable;
use Modules\Core\Traits\HasPublishedState;
use Modules\Core\Traits\LogsActivityStandard;
use Modules\Directory\Traits\HasSuggestions;
use Spatie\Translatable\HasTranslations;

class Term extends Model implements Searchable
{
    use HasAdminShareContents;
    use HasPublishedState;
    use HasSuggestions;
    use HasTranslations;
    use LogsActivityStandard;

    protected array $activitylogFields = ['name', 'definition', 'analogy', 'example', 'did_you_know', 'is_published'];
    protected string $activitylogName = 'term';

    protected array $suggestableFields = [
        'definition' => 'Définition',
        'analogy' => 'Analogie',
        'example' => 'Exemple',
        'did_you_know' => 'Le saviez-vous',
        'other' => 'Autre',
    ];

    protected $table = 'dictionary_terms';

    public array $translatable = ['name', 'slug', 'definition', 'analogy', 'example', 'did_you_know', 'one_sentence_answer'];

    protected $fillable = [
        'name',
        'acronym_full',
        'slug',
        'definition',
        'analogy',
        'example',
        'did_you_know',
        'one_sentence_answer', // 2026-05-26 #295 AEO : phrase-réponse ≤ 40 mots pour AI Overviews + LLM citation
        'faq', // 2026-05-26 #295 AEO : array of {question, answer} pour FAQPage Schema.org
        'sources', // 2026-05-26 #295 GEO : array of {label, url, year, author} sources externes vérifiées
        'difficulty',
        'icon',
        'hero_image',
        'reference_url',
        'reference_label',
        'type',
        'dictionary_category_id',
        'is_published',
        'match_strategy', // 2026-05-05 #145 WSD : loose | partial_case_sensitive | case_sensitive | exact_phrase | never_auto
        'aliases', // 2026-05-05 #151 : variations d'écriture (ex ["tokens","Tokens"])
        'sort_order',
        'broader_slugs', // 2026-05-27 #304 Schema.org knowledge graph : slugs des termes parents (broader)
        'narrower_slugs', // 2026-05-27 #304 Schema.org knowledge graph : slugs des termes enfants (narrower)
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
        return $this->belongsTo(Category::class, 'dictionary_category_id');
    }

    // 2026-05-05 #144 : scopePublished mutualise via HasPublishedState (DRY Core).

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public static function searchableFields(): array
    {
        return ['name', 'definition', 'analogy', 'example', 'did_you_know'];
    }

    public static function searchSectionKey(): string
    {
        return 'glossaire';
    }

    public static function searchSectionLabel(): string
    {
        return __('Glossaire');
    }

    public static function searchSectionIcon(): string
    {
        return '📚';
    }

    public static function searchPriority(): int
    {
        return 40;
    }

    public function searchableResultTitle(): string
    {
        return $this->name;
    }

    public function searchableResultExcerpt(): string
    {
        return \Illuminate\Support\Str::limit(strip_tags($this->definition ?: ''), 200);
    }

    public function searchableResultUrl(): string
    {
        return route('dictionary.show', $this->slug);
    }

    public function adminShareContents(): array
    {
        $name = $this->name ?? '';
        $resume = "# {$name}\n\n";
        if (($v = $this->definition ?? '') !== '') { $resume .= "## Définition\n{$v}\n\n"; }
        if (($v = $this->analogy ?? '') !== '') { $resume .= "## En termes simples\n{$v}\n\n"; }
        if (($v = $this->example ?? '') !== '') { $resume .= "## Exemple\n{$v}\n\n"; }
        if (($v = $this->did_you_know ?? '') !== '') { $resume .= "## Le saviez-vous\n{$v}\n\n"; }
        if (($v = $this->one_sentence_answer ?? '') !== '') { $resume .= "## Réponse courte\n{$v}\n\n"; }
        if (is_array($this->faq) && $this->faq !== []) {
            $resume .= "## FAQ\n";
            foreach ($this->faq as $item) {
                $q = $item['question'] ?? ''; $a = $item['answer'] ?? '';
                if ($q !== '' || $a !== '') { $resume .= "**{$q}**\n{$a}\n\n"; }
            }
        }
        $resume = $this->stripLinks($resume);
        $prompt = $this->infographiePrompt('https://laveille.ai/glossaire', 'Vulgarise le concept « ' . $name . ' » dans une infographie pédagogique. Public : débutants sans connaissances préalables.');
        $slides = $this->slidesPrompt('https://laveille.ai/glossaire', 'Objectif : faire comprendre le terme « ' . $name . ' » et son utilité au quotidien. Public : débutants, sans connaissances préalables.');
        // Post réseaux sociaux « 2026 » : curiosity-gap + En clair + 👉 + CTA discussion (sans lien ni promo).
        $plainDef = $this->smartTrim($this->stripLinks((string) ($this->one_sentence_answer ?: ($this->analogy ?: $this->definition))), 200);
        $interest = $this->smartTrim($this->stripLinks((string) ($this->did_you_know ?? '')), 180);
        if ($interest === '') {
            $interest = $this->smartTrim($this->stripLinks((string) ($this->example ?? '')), 180);
        }
        if ($interest === '') {
            $interest = "Un terme clé à connaître pour suivre l'actualité de l'IA.";
        }
        $hook = "{$name}. Tu vois ce mot passer partout en IA… mais tu sais vraiment ce que ça veut dire ? 🤔";
        $cta = "Tu le savais, toi ? Dis-le-moi en commentaire 👇";
        $social = $this->buildEngagingSocialPost(
            $hook,
            $plainDef,
            $interest,
            $cta,
            ['#IA', '#' . $this->normalizeShareHashtag((string) $name), '#Glossaire', '#Québec']
        );
        return [
            ['label' => 'Résumé (NotebookLM)', 'icon' => '📄', 'text' => $resume],
            ['label' => 'NotebookLM Infographie', 'icon' => '🤖', 'text' => $prompt],
            ['label' => 'NotebookLM Diapositives', 'icon' => '🖼️', 'text' => $slides],
            ['label' => 'Post réseaux sociaux', 'icon' => '📣', 'text' => $social],
        ];
    }
}
