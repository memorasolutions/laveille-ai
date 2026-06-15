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
                'shape'        => <<<'SHAPE'
content['editorial'] = chaîne HTML (ex: '<p>…</p><p>— Stef</p>'), rendue via {!! !!} dans le gabarit.
STRUCTURE (best practices 2026, plaisant à lire, pas une simple affirmation) :
  1. Hook concret et humain (1-2 phrases) : un moment vécu, une petite scène (ex. « Lundi matin, tu colles le courriel d'un client dans ChatGPT… »). Éviter les ouvertures génériques (« Cette semaine, nous allons parler de… »).
  2. L'enjeu / le contexte en 1-2 phrases (pourquoi ça compte pour le lecteur).
  3. La leçon à retenir (1 phrase).
  4. Un pont vers le défi de la semaine ou un contenu de l'infolettre.
Ton conversationnel, tutoiement, « tu » et « je », phrases courtes. 50 à 80 mots.
Loi 25 : la nommer UNE seule fois, et seulement si le sujet touche la vie privée / les données personnelles.
Terminer par '— Stef' ou '- Stef'. INTERDIT : pas de Markdown, pas de **, pas de *, pas de #.
SHAPE

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
      'title'     => 'titre clair du défi (PAS « Défi de la semaine » : le bandeau est déjà posé par le gabarit)',
      'hook'      => 'phrase d'accroche concrète qui plante le décor (HTML permis)',
      'subtitle'  => 'optionnel — sous-titre court',
      'steps'     => ['étape 1', 'étape 2', '…'],  // 3 à 4 étapes GUIDÉES pas-à-pas : une seule action concrète par étape, sans surcharger ; HTML permis par étape
      'cta_url'   => 'URL vers l'outil ou la ressource',
      'cta_label' => 'libellé du bouton CTA',
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
                'field_type'   => 'combobox',
                'combobox_type'=> 'news',
                'multi'        => false,
                'placeholder'  => 'Chercher une actualité par titre ou mot-clé…',
                'shape'        => "content['highlight_id'] = ID (entier) sélectionné directement via le sélecteur DB du générateur de prompt.",
            ],
            'top_news' => [
                'label'        => 'Top actualités (5)',
                'content_keys' => ['top_news_ids'],
                'auto_source'  => 'Top 5 NewsArticle (relevance_score desc, 7 derniers jours, hors vedette)',
                'field_type'   => 'combobox',
                'combobox_type'=> 'news',
                'multi'        => true,
                'max_items'    => 5,
                'placeholder'  => 'Chercher des actualités (jusqu\'à 5)…',
                'shape'        => "content['top_news_ids'] = tableau JSON d'IDs entiers sélectionnés directement via le sélecteur DB du générateur de prompt (ex: [12, 45, 78]).",
            ],
            'tool' => [
                'label'        => 'Outil de la semaine',
                'content_keys' => ['tool_id'],
                'auto_source'  => 'DigestContentService::getUnsentItem(\'tool\') — rotation anti-répétition parmi les outils publiés',
                'field_type'   => 'combobox',
                'combobox_type'=> 'tool',
                'multi'        => false,
                'placeholder'  => 'Chercher un outil par nom…',
                'shape'        => "content['tool_id'] = ID (entier) sélectionné directement via le sélecteur DB du générateur de prompt.",
            ],
            'term' => [
                'label'        => 'Terme IA de la semaine',
                'content_keys' => ['term_id'],
                'auto_source'  => 'DigestContentService::getUnsentItem(\'term\') — rotation anti-répétition parmi les termes publiés',
                'field_type'   => 'combobox',
                'combobox_type'=> 'term',
                'multi'        => false,
                'placeholder'  => 'Chercher un terme IA…',
                'shape'        => "content['term_id'] = ID (entier) sélectionné directement via le sélecteur DB du générateur de prompt.",
            ],
            'article' => [
                'label'        => 'Article de blogue vedette',
                'content_keys' => ['article_id'],
                'auto_source'  => 'Blog\\Article::published()->latest(\'published_at\')->first()',
                'field_type'   => 'combobox',
                'combobox_type'=> 'article',
                'multi'        => false,
                'placeholder'  => 'Chercher un article de blogue…',
                'shape'        => "content['article_id'] = ID (entier) sélectionné directement via le sélecteur DB du générateur de prompt.",
            ],
            'interactive_tool' => [
                'label'        => 'Outil interactif (outil gratuit)',
                'content_keys' => ['interactive_tool_id'],
                'auto_source'  => 'DigestContentService::getUnsentItem(\'interactive_tool\') — rotation parmi Tools actifs',
                'field_type'   => 'combobox',
                'combobox_type'=> 'interactive_tool',
                'multi'        => false,
                'placeholder'  => 'Chercher un outil interactif…',
                'shape'        => "content['interactive_tool_id'] = ID (entier) sélectionné directement via le sélecteur DB du générateur de prompt.",
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
                $keysStr   = implode(', ', $meta['content_keys']);
                $isCombobox = ($meta['field_type'] ?? '') === 'combobox';

                $lines[] = '  MODE      : PERSONNALISER';
                $lines[] = '  CLÉ(S)    : content[' . $keysStr . ']';

                if ($isCombobox) {
                    // La valeur est un ID entier (single) ou un tableau JSON d'IDs (multi)
                    // Sélectionné directement depuis la DB via le sélecteur — aucune recherche nécessaire.
                    $isMulti = ! empty($meta['multi']);
                    if ($isMulti) {
                        // Valeur = JSON d'IDs ex: [12, 45, 78]
                        $decoded = json_decode($value, true);
                        if (is_array($decoded)) {
                            $ids = array_map('intval', $decoded);
                            $lines[] = '  IDS       : ' . json_encode($ids);
                            $lines[] = '  ACTION    : Écris content[\'' . $keysStr . '\'] = ' . json_encode($ids) . ' directement (IDs sélectionnés en DB, aucune recherche requise).';
                        } else {
                            // Valeur texte legacy — rétro-compat
                            $lines[] = '  CONSIGNE  : ' . $value;
                            $lines[] = '  ACTION    : Cherche les IDs correspondant à cette consigne et écris content[\'' . $keysStr . '\'] = tableau d\'IDs entiers.';
                        }
                    } else {
                        // Valeur = ID entier sous forme de string ex: "123"
                        if (ctype_digit($value)) {
                            $id = (int) $value;
                            $lines[] = '  ID        : ' . $id;
                            $lines[] = '  ACTION    : Écris content[\'' . $keysStr . '\'] = ' . $id . ' directement (ID sélectionné en DB, aucune recherche requise).';
                        } else {
                            // Valeur texte legacy — rétro-compat
                            $lines[] = '  CONSIGNE  : ' . $value;
                            $lines[] = '  ACTION    : Cherche l\'ID correspondant à cette consigne et écris content[\'' . $keysStr . '\'] = ID entier.';
                        }
                    }
                } else {
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
                }
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
        $lines[] = "- Outil de la semaine : le nombre de tutoriels approuvés de l'outil s'affiche AUTOMATIQUEMENT dans le courriel (« 🎓 N tutoriels pour bien démarrer »), masqué si 0 — rien à écrire.";
        $lines[] = "- Bloc « Le saviez-vous » (raccourcisseur) : le gabarit nomme les domaines et rappelle que 1lien.ca et unlien.ca (et les autres) mènent au même lien (partage résilient). Géré automatiquement.";
        $lines[] = "- ENVOI OFFICIEL : c'est TOUJOURS la dernière version travaillée du brouillon (content de l'issue) qui part aux abonnés via --send ; ne jamais régénérer en AUTO par-dessus un contenu déjà personnalisé.";
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
