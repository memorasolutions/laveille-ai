<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Services;

/**
 * Assemble un PROMPT texte structuré destiné à Claude Code CLI pour générer
 * la newsletter hebdomadaire de « La veille de Stef » via le gabarit digest-weekly.
 *
 * Chaque section peut être AUTO (laisser DigestContentService/EditorialBank agir)
 * ou PERSONNALISÉE (injecter une valeur précise dans NewsletterIssue.content).
 */
class NewsletterPromptBuilder
{
    /**
     * Mapping centralisé des sections du gabarit digest-weekly.
     * Ordre = ordre d'apparition dans digest-weekly.blade.php.
     *
     * 'content_keys' : clé(s) dans NewsletterIssue.content à écrire si personnalisé.
     * 'auto_source'  : description du comportement automatique (pour le prompt).
     * 'field_type'   : 'textarea' | 'text' (pour la vue)
     * 'placeholder'  : hint dans la vue
     *
     * @return array<string, array{label: string, content_keys: string[], auto_source: string, field_type: string, placeholder: string}>
     */
    public static function sectionsMap(): array
    {
        return [
            'editorial' => [
                'label'        => 'Éditorial',
                'content_keys' => ['editorial'],
                'auto_source'  => 'EditorialBank::getNextEditorial() ou DigestContentService::generateEditorial() (deepseek via OpenRouter)',
                'field_type'   => 'textarea',
                'placeholder'  => "ex: Cette semaine, l'IA sort des labos pour entrer dans nos cuisines — littéralement.\n- Stef",
            ],
            'challenge' => [
                'label'        => 'Défi de la semaine',
                'content_keys' => ['weekly_prompt', 'wellness_challenge'],
                'auto_source'  => 'DigestContentService::getWellnessChallenge() + generateWeeklyPrompt() (rotation config)',
                'field_type'   => 'textarea',
                'placeholder'  => "ex: Cette semaine, essaie de résumer une réunion avec NotebookLM. Durée : 10 min.",
            ],
            'highlight' => [
                'label'        => 'Actualité vedette',
                'content_keys' => ['highlight_id'],
                'auto_source'  => 'NewsArticle le plus récent et pertinent (relevance_score desc, 7 derniers jours)',
                'field_type'   => 'text',
                'placeholder'  => "ex: mets en vedette l'article sur la Loi 25 et les données d'entreprise",
            ],
            'top_news' => [
                'label'        => 'Top actualités (5)',
                'content_keys' => ['top_news_ids'],
                'auto_source'  => 'Top 5 NewsArticle (relevance_score desc, 7 derniers jours, hors vedette)',
                'field_type'   => 'text',
                'placeholder'  => "ex: privilégie les actus sur l'IA en éducation au Québec cette semaine",
            ],
            'tool' => [
                'label'        => 'Outil de la semaine',
                'content_keys' => ['tool_id'],
                'auto_source'  => 'DigestContentService::getUnsentItem(\'tool\') — rotation anti-répétition parmi les outils publiés',
                'field_type'   => 'text',
                'placeholder'  => "ex: mets en vedette un outil de transcription audio gratuit",
            ],
            'term' => [
                'label'        => 'Terme IA de la semaine',
                'content_keys' => ['term_id'],
                'auto_source'  => 'DigestContentService::getUnsentItem(\'term\') — rotation anti-répétition parmi les termes publiés',
                'field_type'   => 'text',
                'placeholder'  => "ex: explique le concept de RAG (Retrieval-Augmented Generation)",
            ],
            'article' => [
                'label'        => 'Article de blogue vedette',
                'content_keys' => ['article_id'],
                'auto_source'  => 'Blog\\Article::published()->latest(\'published_at\')->first()',
                'field_type'   => 'text',
                'placeholder'  => "ex: mets en vedette l'article sur l'adoption de l'IA dans les PME québécoises",
            ],
            'interactive_tool' => [
                'label'        => 'Outil interactif (outil gratuit)',
                'content_keys' => ['interactive_tool_id'],
                'auto_source'  => 'DigestContentService::getUnsentItem(\'interactive_tool\') — rotation parmi Tools actifs',
                'field_type'   => 'text',
                'placeholder'  => "ex: mets en avant le générateur de quiz IA",
            ],
        ];
    }

    /**
     * Compile le prompt à partir des blocs du formulaire admin.
     *
     * Structure attendue dans $blocks :
     *   subject        (string)  Objet du courriel (optionnel)
     *   test_email     (string)  Adresse courriel de test
     *   extra_notes    (string)  Notes libres additionnelles
     *   sections       (array)   Clé = section_key, valeur = ['mode' => 'auto'|'custom', 'value' => string]
     */
    public function compile(array $blocks): string
    {
        $subject    = trim((string) ($blocks['subject'] ?? ''));
        $testEmail  = trim((string) ($blocks['test_email'] ?? ''));
        $extraNotes = trim((string) ($blocks['extra_notes'] ?? ''));
        $sections   = (array) ($blocks['sections'] ?? []);

        $appName    = config('app.name', 'la newsletter');
        $now        = now(config('app.timezone', 'America/Toronto'));
        $year       = (int) $now->year;
        $week       = (int) $now->weekOfYear;

        $lines = [];
        $lines[] = '=== PROMPT NEWSLETTER — ' . mb_strtoupper($appName) . ' ===';
        $lines[] = '';
        $lines[] = '## Contexte et cible';
        $lines[] = 'Tu travailles dans le dépôt Laravel de « ' . $appName . ' » (Modules/Newsletter).';
        $lines[] = 'Gabarit utilisé : digest-weekly (resources/views/emails/digest-weekly.blade.php).';
        $lines[] = '';
        $lines[] = '## Cible : NewsletterIssue de la semaine courante';
        $lines[] = 'Trouve ou crée le NewsletterIssue correspondant à :';
        $lines[] = '  year        = ' . $year;
        $lines[] = '  week_number = ' . $week;
        $lines[] = 'Si un NewsletterIssue (year=' . $year . ', week_number=' . $week . ') existe déjà, mets-le à jour.';
        $lines[] = 'Sinon crée-le avec ces valeurs initiales, en laissant gatherFreshContent() remplir le reste.';
        $lines[] = '';

        if ($subject !== '') {
            $lines[] = '## Objet du courriel';
            $lines[] = 'Utilise cet objet (max 45 caractères, mobile-first) :';
            $lines[] = '  ' . $subject;
            $lines[] = '';
        }

        $lines[] = '## Instructions section par section';
        $lines[] = 'Pour CHAQUE section ci-dessous, la mention AUTO signifie que tu NE touches PAS cette clé :';
        $lines[] = 'laisse DigestContentService, EditorialBank ou gatherFreshContent() la remplir normalement.';
        $lines[] = 'La mention PERSONNALISER signifie que tu dois écrire la valeur indiquée dans';
        $lines[] = 'NewsletterIssue.content[<clé>] AVANT de sauvegarder.';
        $lines[] = '';

        $map = self::sectionsMap();
        foreach ($map as $key => $meta) {
            $mode  = $sections[$key]['mode']  ?? 'auto';
            $value = trim((string) ($sections[$key]['value'] ?? ''));

            $lines[] = '### ' . $meta['label'];

            if ($mode === 'custom' && $value !== '') {
                $keysStr = implode(', ', $meta['content_keys']);
                $lines[] = '  MODE      : PERSONNALISER';
                $lines[] = '  CLÉ(S)    : content[' . $keysStr . ']';
                $lines[] = '  CONSIGNE  : ' . $value;
                $lines[] = '  ACTION    : Écris cette consigne dans la/les clé(s) indiquée(s). Si la clé attend';
                $lines[] = '              un ID (highlight_id, tool_id, term_id, article_id, interactive_tool_id,';
                $lines[] = '              top_news_ids), trouve l\'enregistrement correspondant en DB et utilise son ID.';
            } else {
                $lines[] = '  MODE      : AUTO — ne touche pas cette section';
                $lines[] = '  SOURCE    : ' . $meta['auto_source'];
            }

            $lines[] = '';
        }

        $lines[] = '## Sauvegarder et envoyer';
        $lines[] = '1. Après avoir mis à jour NewsletterIssue.content, appelle $issue->save().';
        $lines[] = '2. Lance la commande artisan depuis le SERVEUR DE PRODUCTION (Brevo n\'autorise que';
        $lines[] = '   l\'IP du serveur prod — l\'envoi test ne fonctionnera PAS en local) :';

        if ($testEmail !== '') {
            $lines[] = '   php artisan newsletter:digest --test-email=' . $testEmail;
        } else {
            $lines[] = '   php artisan newsletter:digest --test-email=<ton_adresse_test>';
        }

        $lines[] = '3. Vérifie la réception du courriel test avant de considérer la tâche terminée.';
        $lines[] = '';

        $lines[] = '## Consignes éditoriales strictes';
        $lines[] = '- Rédige en français québécois professionnel (courriel, fin de semaine, etc.)';
        $lines[] = '- Zéro fait inventé : si une donnée est absente de la DB, reste général mais pertinent';
        $lines[] = '- Loi 25 QC : mentionne-la UNE SEULE FOIS si le sujet touche la vie privée/données personnelles';
        $lines[] = '- Ne réinvente pas DigestCommand, DigestContentService ni le gabarit — utilise-les';
        $lines[] = '- Variables dynamiques {{ $weekNumber }}, {{ $subject }}, etc. sont injectées au runtime';
        $lines[] = '';

        if ($extraNotes !== '') {
            $lines[] = '## Notes complémentaires';
            $lines[] = $extraNotes;
            $lines[] = '';
        }

        $lines[] = '=== FIN DU PROMPT ===';

        return implode("\n", $lines) . "\n";
    }
}
