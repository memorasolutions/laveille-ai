<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Concerns\HasAdminShareContents;
use Modules\Tools\Models\Concerns\Shareable;

class Tool extends Model
{
    use HasAdminShareContents;
    use Shareable;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'answer_summary',
        'answer_points',
        'icon',
        'featured_image',
        'is_active',
        'is_under_construction',
        'construction_mode',
        'sort_order',
        'category',
        'views_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_under_construction' => 'boolean',
        'views_count' => 'integer',
        'answer_points' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Contenus de partage admin (superadmin) pour un outil INTERACTIF (/outils/{slug}) :
     * résumé NotebookLM, prompts infographie + diapositives, posts LinkedIn/Facebook/X +
     * légende Instagram. Réutilise le trait DRY HasAdminShareContents (zéro duplication de logique
     * sociale). Robuste si la description est vide. Angle : faire ESSAYER l'outil (gratuit).
     */
    public function adminShareContents(): array
    {
        $name = trim((string) ($this->name ?? '')) !== '' ? (string) $this->name : 'cet outil';
        $url = url('/outils/' . $this->slug);

        $description = trim((string) ($this->description ?? ''));
        $answerSummary = trim((string) ($this->answer_summary ?? ''));

        // Résumé (NotebookLM) : markdown propre, sans liens.
        $resume = "# {$name}\n\n";
        if ($description !== '') {
            $resume .= "## Description\n{$description}\n\n";
        }
        if ($answerSummary !== '') {
            $resume .= "## En bref\n{$answerSummary}\n\n";
        }
        if ($description === '' && $answerSummary === '') {
            $resume .= "## Description\nOutil IA gratuit proposé par La veille de Stef.\n\n";
        }
        $resume = $this->stripLinks($resume);

        // Prompts NotebookLM.
        $prompt = $this->infographiePrompt($url, 'Présente l\'outil interactif « ' . $name . ' » dans une infographie : à quoi il sert, à qui il s\'adresse, pourquoi l\'essayer. Public : curieux sans connaissances préalables.');
        $slides = $this->slidesPrompt($url, 'Objectif : présenter l\'outil interactif « ' . $name . ' » : à quoi il sert, pour qui, comment l\'essayer. Public : curieux, sans connaissances préalables.');

        // Base sociale (sans lien).
        $plainSource = $description !== '' ? $description : $answerSummary;
        $plainDef = $this->smartTrim($this->stripLinks($plainSource), 200);
        if ($plainDef === '') {
            $plainDef = "Un outil IA gratuit, simple à essayer.";
        }
        $interest = $answerSummary !== '' && $answerSummary !== $plainSource
            ? $this->smartTrim($this->stripLinks($answerSummary), 180)
            : "Le genre d'outil gratuit qui peut vite devenir indispensable.";

        $hook = "Connais-tu {$name} ? Un outil gratuit pour te simplifier la vie avec l'IA. 👀";
        $cta = "Tu l'essaies ? Dis-moi ce que tu en penses en commentaire 👇";
        $hashtags = ['#IA', '#OutilsIA', '#' . $this->normalizeShareHashtag($name), '#Québec'];

        $linkedin = $this->buildLinkedInPost($hook, $plainDef, $interest, $cta, $hashtags);
        $facebook = $this->buildFacebookPost($hook, $plainDef, $interest, $cta, $hashtags);

        // Post X (Twitter) : court (≤ 280 car), punché, 1-2 hashtags, aucun lien.
        $xHook = $this->stripLinks("{$name} : un outil IA gratuit à essayer 👀");
        $xBody = $this->smartTrim($plainDef, 160);
        $xTags = '#IA #OutilsIA';
        $x = $this->smartTrim(trim($xHook . "\n\n" . $xBody . "\n\n" . $xTags), 278);

        // Légende Instagram : accroche + 2-3 lignes + bloc de hashtags plus fourni.
        $igHashtags = array_unique(array_filter([
            '#IA', '#IntelligenceArtificielle', '#OutilsIA', '#Productivité', '#Québec',
            '#' . $this->normalizeShareHashtag($name),
        ]));
        $instagram = trim(implode("\n\n", array_filter([
            "✨ Connais-tu {$name} ?",
            $plainDef,
            "Un outil gratuit à essayer dès maintenant. Le lien est dans la bio 👆",
        ]))) . "\n\n" . implode(' ', $igHashtags);

        return [
            ['label' => 'Résumé (NotebookLM)', 'icon' => '📄', 'text' => $resume],
            ['label' => 'NotebookLM Infographie', 'icon' => '🤖', 'text' => $prompt],
            ['label' => 'NotebookLM Diapositives', 'icon' => '🖼️', 'text' => $slides],
            ['label' => 'Post LinkedIn', 'icon' => '💼', 'text' => $linkedin],
            ['label' => 'Post Facebook', 'icon' => '📘', 'text' => $facebook],
            ['label' => 'Post X', 'icon' => '✖️', 'text' => $x],
            ['label' => 'Légende Instagram', 'icon' => '📸', 'text' => $instagram],
        ];
    }
}
