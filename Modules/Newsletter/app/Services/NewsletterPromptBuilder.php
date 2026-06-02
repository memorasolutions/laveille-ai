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
     * 'shape'        : FORME EXACTE à écrire dans NewsletterIssue.content (injectée dans le prompt PERSONNALISER)
     *
     * @return array<string, array{label: string, content_keys: string[], auto_source: string, field_type: string, placeholder: string, shape: string}>
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
                'shape'        => "content['editorial'] = chaîne HTML (ex: '<p>…</p><p>— Stef</p>'). Rendue via {!! !!} dans le gabarit. Maximum 50 mots. Terminer par '— Stef' ou '- Stef'. INTERDIT : pas de Markdown, pas de **, pas de *, pas de #.",
            ],
            'challenge' => [
                'label'        => 'Défi de la semaine',
                'content_keys' => ['weekly_prompt', 'wellness_challenge'],
                'auto_source'  => 'DigestContentService::getWellnessChallenge() + generateWeeklyPrompt() (rotation config)',
                'field_type'   => 'textarea',
                'placeholder'  => "ex: Cette semaine, essaie de résumer une réunion avec NotebookLM. Durée : 10 min.",
                'shape'        => <<<'SHAPE'
Choisir UNE des deux structures selon la nature de la consigne :

OPTION A — Défi action/bien-être (essaie un outil, fais une action concrète) :
  content['wellness_challenge'] = [
      'hook'      => 'phrase d\'accroche expliquant le défi (HTML permis)',
      'subtitle'  => 'optionnel — sous-titre court',
      'steps'     => ['étape 1', 'étape 2', '...'],  // au moins 2 étapes, HTML permis par étape
      'cta_url'   => 'optionnel — URL vers l\'outil ou la ressource',
      'cta_label' => 'optionnel — libellé du bouton CTA',
  ];
  Laisser content['weekly_prompt'] à null (ou ne pas l'écrire).

OPTION B — Défi prompt copy-paste (fournir un prompt à copier dans ChatGPT/Claude/Gemini) :
  content['weekly_prompt'] = [
      'intro' => 'phrase d\'introduction italique (1 ligne)',
      'parts' => [
          [
              'label'     => 'Partie 1',        // libellé de bloc (ex: 'Contexte', 'Prompt principal')
              'content'   => 'le texte du prompt à copier-coller',
              'pre_note'  => 'optionnel — note avant le bloc',
              'post_note' => 'optionnel — note après le bloc',
          ],
          // ajouter d'autres parties si nécessaire
      ],
  ];
  Laisser content['wellness_challenge'] à null (ou ne pas l'écrire).

Règle de choix : si la consigne dit "essaie X" / "fais X en N min" / outil concret → OPTION A. Si la consigne fournit un texte de prompt à copier → OPTION B.
SHAPE
            ],
            'highlight' => [
                'label'        => 'Actualité vedette',
                'content_keys' => ['highlight_id'],
                'auto_source'  => 'NewsArticle le plus récent et pertinent (relevance_score desc, 7 derniers jours)',
                'field_type'   => 'text',
                'placeholder'  => "ex: mets en vedette l'article sur la Loi 25 et les données d'entreprise",
                'shape'        => "content['highlight_id'] = ID (entier) d'un enregistrement NewsArticle EXISTANT en DB. Cherche via : NewsArticle::where('title', 'like', '%mot-clé%')->first()->id ou en cherchant par sujet dans la table news_articles.",
            ],
            'top_news' => [
                'label'        => 'Top actualités (5)',
                'content_keys' => ['top_news_ids'],
                'auto_source'  => 'Top 5 NewsArticle (relevance_score desc, 7 derniers jours, hors vedette)',
                'field_type'   => 'text',
                'placeholder'  => "ex: privilégie les actus sur l'IA en éducation au Québec cette semaine",
                'shape'        => "content['top_news_ids'] = tableau d'IDs (entiers) de NewsArticle EXISTANTS (ex: [12, 45, 78]). Cherche 5 articles correspondant à la consigne via NewsArticle::where(…)->pluck('id')->toArray().",
            ],
            'tool' => [
                'label'        => 'Outil de la semaine',
                'content_keys' => ['tool_id'],
                'auto_source'  => 'DigestContentService::getUnsentItem(\'tool\') — rotation anti-répétition parmi les outils publiés',
                'field_type'   => 'text',
                'placeholder'  => "ex: mets en vedette un outil de transcription audio gratuit",
                'shape'        => "content['tool_id'] = ID (entier) d'un enregistrement Directory\\Tool EXISTANT en DB. Cherche via : Tool::where('name', 'like', '%mot-clé%')->orWhere('category', '…')->first()->id dans la table directory_tools.",
            ],
            'term' => [
                'label'        => 'Terme IA de la semaine',
                'content_keys' => ['term_id'],
                'auto_source'  => 'DigestContentService::getUnsentItem(\'term\') — rotation anti-répétition parmi les termes publiés',
                'field_type'   => 'text',
                'placeholder'  => "ex: explique le concept de RAG (Retrieval-Augmented Generation)",
                'shape'        => "content['term_id'] = ID (entier) d'un enregistrement Dictionary\\Term EXISTANT en DB. Cherche via : Term::where('name', 'like', '%mot-clé%')->first()->id dans la table dictionary_terms.",
            ],
            'article' => [
                'label'        => 'Article de blogue vedette',
                'content_keys' => ['article_id'],
                'auto_source'  => 'Blog\\Article::published()->latest(\'published_at\')->first()',
                'field_type'   => 'text',
                'placeholder'  => "ex: mets en vedette l'article sur l'adoption de l'IA dans les PME québécoises",
                'shape'        => "content['article_id'] = ID (entier) d'un enregistrement Blog\\Article EXISTANT et publié. Cherche via : Article::published()->where('title', 'like', '%mot-clé%')->first()->id dans la table blog_articles (ou articles).",
            ],
            'interactive_tool' => [
                'label'        => 'Outil interactif (outil gratuit)',
                'content_keys' => ['interactive_tool_id'],
                'auto_source'  => 'DigestContentService::getUnsentItem(\'interactive_tool\') — rotation parmi Tools actifs',
                'field_type'   => 'text',
                'placeholder'  => "ex: mets en avant le générateur de quiz IA",
                'shape'        => "content['interactive_tool_id'] = ID (entier) d'un enregistrement Tools\\Tool EXISTANT et actif. Cherche via : Tool::where('name', 'like', '%mot-clé%')->first()->id dans la table tools.",
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
                if (! empty($meta['shape'])) {
                    // Indente chaque ligne du shape pour l'alignement visuel dans le prompt
                    $shapeLines = explode("\n", rtrim((string) $meta['shape']));
                    $lines[]    = '  FORME     : ' . array_shift($shapeLines);
                    foreach ($shapeLines as $shapeLine) {
                        $lines[] = '              ' . $shapeLine;
                    }
                }
                $lines[] = '  ACTION    : Écris cette consigne dans la/les clé(s) indiquée(s) en respectant la FORME ci-dessus.';
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
