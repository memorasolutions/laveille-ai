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
 * la newsletter hebdomadaire de « La veille de Stef ».
 */
class NewsletterPromptBuilder
{
    /**
     * Compile le prompt à partir des blocs du formulaire admin.
     *
     * Clés attendues dans $blocks (toutes optionnelles) :
     *   subject, angle, tone, challenge_instruction, challenge_duration,
     *   word_count, audience, selected_articles (array [{title,url}]),
     *   custom_sections (array [{title,content}]),
     *   send_test_email (bool), test_email (string), extra_notes (string)
     */
    /**
     * Construit l'instruction de fallback pour une section vide.
     * Centralisée ici pour être DRY — jamais dupliquée dans la vue.
     */
    private function fallback(string $sectionLabel): string
    {
        return '⚠️  Section [' . $sectionLabel . '] laissée vide → applique le comportement AUTOMATIQUE PAR DÉFAUT'
            . ' (comme le workflow concentré/newsletter habituel du projet) pour cette section.';
    }

    public function compile(array $blocks): string
    {
        $subject              = (string) ($blocks['subject'] ?? '');
        $angle                = (string) ($blocks['angle'] ?? '');
        $tone                 = (string) ($blocks['tone'] ?? 'professionnel et chaleureux (Québec)');
        $challengeInstruction = (string) ($blocks['challenge_instruction'] ?? '');
        $challengeDuration    = (string) ($blocks['challenge_duration'] ?? '5 minutes');
        $wordCount            = (string) ($blocks['word_count'] ?? '300-500 mots');
        $audience             = (string) ($blocks['audience'] ?? 'professionnels québécois en veille stratégique IA');
        $selectedArticles     = (array)  ($blocks['selected_articles'] ?? []);
        $customSections       = (array)  ($blocks['custom_sections'] ?? []);
        // Note : le formulaire envoie 'sections' (non 'custom_sections') — support des deux clés
        if ($customSections === []) {
            $customSections = (array) ($blocks['sections'] ?? []);
        }
        $sendTestEmail        = (bool)   ($blocks['send_test_email'] ?? false);
        $testEmail            = (string) ($blocks['test_email'] ?? '');
        $extraNotes           = (string) ($blocks['extra_notes'] ?? '');

        $lines = [];

        $appName = config('app.name', 'Newsletter');
        $lines[] = '=== PROMPT NEWSLETTER — ' . mb_strtoupper($appName) . ' ===';
        $lines[] = '';
        $lines[] = '## Contexte projet';
        $lines[] = 'Tu travailles dans le dépôt Laravel de « ' . $appName . ' » (Modules/Newsletter).';
        $lines[] = 'La newsletter hebdomadaire est stockée dans le modèle NewsletterIssue et envoyée via Brevo.';
        $lines[] = 'Utilise le workflow newsletter/concentré IA existant du projet (DigestCommand, DigestContentService,';
        $lines[] = 'NewsletterIssue) — ne réinvente pas ces mécanismes.';
        $lines[] = '';
        $lines[] = '## Structure obligatoire (best-practice 2026, mobile-first)';
        $lines[] = '1. Objet courriel : court, mobile-first, max 45 caractères';
        $lines[] = '2. Longueur : ' . $wordCount;
        $lines[] = '3. 1 seule idée centrale, déclinée du début à la fin';
        $lines[] = '4. Ouverture : hook percutant + promesse claire pour le lecteur';
        $lines[] = '5. Contenu principal : développe l\'angle ci-dessous + 1 chiffre ou donnée vérifiable';
        $lines[] = '6. Encadré « Défi de la semaine » : micro-action mesurable + durée estimée';
        $lines[] = '7. Un SEUL CTA (appel à l\'action) en bas de newsletter';
        $lines[] = '8. Footer sobre : désabonnement, crédits minimaux';
        $lines[] = '';
        $lines[] = '## Paramètres éditoriaux';

        // --- Éditorial ---
        $editorialEmpty = ($subject === '' && $angle === '');
        if ($editorialEmpty) {
            $lines[] = $this->fallback('Éditorial');
        } else {
            if ($subject !== '') {
                $lines[] = '- Sujet/angle central : ' . $subject;
            }
            if ($angle !== '') {
                $lines[] = '- Angle rédactionnel : ' . $angle;
            }
        }

        $lines[] = '- Ton demandé : ' . $tone;
        $lines[] = '- Public cible : ' . $audience;
        $lines[] = '';

        // --- Défi de la semaine ---
        if ($challengeInstruction !== '') {
            $lines[] = '## Défi de la semaine';
            $lines[] = 'Consigne : ' . $challengeInstruction;
            $lines[] = 'Durée estimée : ' . $challengeDuration;
            $lines[] = 'Formule ceci comme un encadré visuel distinct dans le HTML.';
            $lines[] = '';
        } else {
            $lines[] = '## Défi de la semaine';
            $lines[] = $this->fallback('Défi de la semaine');
            $lines[] = '';
        }

        // --- Articles / Actualités ---
        if ($selectedArticles !== []) {
            $lines[] = '## Articles / concentrés à utiliser';
            $lines[] = 'Ces contenus existants du site servent de base factuelle — cite-les ou synthétise-les :';
            $lines[] = $this->buildArticlesSection($selectedArticles);
            $lines[] = '';
        } else {
            $lines[] = '## Articles / concentrés à utiliser';
            $lines[] = $this->fallback('Actualités à inclure');
            $lines[] = '';
        }

        // --- Sections personnalisées ---
        $customSectionsNonEmpty = array_filter(
            $customSections,
            static fn (array $s): bool => ($s['title'] ?? '') !== '' || ($s['content'] ?? '') !== ''
        );
        if ($customSectionsNonEmpty !== []) {
            $lines[] = '## Sections personnalisées à intégrer';
            $lines[] = $this->buildCustomSections(array_values($customSectionsNonEmpty));
            $lines[] = '';
        } else {
            $lines[] = '## Sections personnalisées';
            $lines[] = $this->fallback('Sections personnalisées');
            $lines[] = '';
        }

        $lines[] = '## Consignes strictes';
        $lines[] = '- Rédige en français québécois professionnel (courriel, fin de semaine, etc.)';
        $lines[] = '- Zéro fait inventé : si une donnée est absente, reste général mais pertinent';
        $lines[] = '- Loi 25 QC : mentionne-la UNE SEULE FOIS si le sujet touche la vie privée/données personnelles';
        $lines[] = '- Variables {{ SUJET }} et {{ SEMAINE }} = remplacées automatiquement au runtime CLI — utilise-les';
        $lines[] = '- HTML de sortie : inline CSS léger, mobile-first, PAS de <html>/<body> complets';
        $lines[] = '- Structure visuelle : h1 titre, h2 sections, encadré défi dans <blockquote> ou <div> distinct';
        $lines[] = '';

        if ($extraNotes !== '') {
            $lines[] = '## Notes complémentaires';
            $lines[] = $extraNotes;
            $lines[] = '';
        }

        if ($sendTestEmail && $testEmail !== '') {
            $lines[] = '## Action après rédaction';
            $lines[] = 'Une fois le contenu HTML rédigé et inséré dans la DB (NewsletterIssue) :';
            $lines[] = '1. Envoie un courriel test à : ' . $testEmail . ' (via artisan newsletter:digest --test ou Brevo)';
            $lines[] = '2. Attends ma validation avant de considérer la newsletter terminée';
            $lines[] = '3. Itère sur mes corrections jusqu\'au résultat approuvé';
            $lines[] = '';
        }

        $lines[] = '=== FIN DU PROMPT ===';

        return implode("\n", $lines) . "\n";
    }

    private function buildArticlesSection(array $articles): string
    {
        $parts = [];
        foreach ($articles as $article) {
            $title = str_replace(["\n", "\r"], ' ', (string) ($article['title'] ?? ''));
            $url   = (string) ($article['url'] ?? '');
            $parts[] = '- ' . $title . ($url !== '' ? ' (' . $url . ')' : '');
        }

        return implode("\n", $parts);
    }

    private function buildCustomSections(array $sections): string
    {
        $parts = [];
        foreach ($sections as $section) {
            $title   = (string) ($section['title'] ?? '');
            $content = (string) ($section['content'] ?? '');
            if ($title !== '' || $content !== '') {
                $parts[] = '### ' . $title;
                $parts[] = $content;
            }
        }

        return implode("\n", $parts);
    }
}
