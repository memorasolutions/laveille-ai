<?php

declare(strict_types=1);

namespace Modules\Authors\Services;

final class ArticlePromptBuilderService
{
    private const VALID_TONES = ['pedagogue', 'direct', 'conversationnel', 'formel', 'opinion'];

    private const VALID_ANGLES = ['guide_pratique', 'etude_de_cas', 'comparatif', 'opinion', 'actualite_commentee', 'tutoriel', 'faq'];

    private const VALID_LENGTHS = ['court_800', 'moyen_1500', 'long_2500'];

    private const VALID_TECH_LEVELS = ['debutant', 'intermediaire', 'expert'];

    private const VALID_AIS = ['claude', 'chatgpt', 'gemini', 'perplexity', 'mistral'];

    public function build(array $params): array
    {
        $this->validate($params);

        $lengthMap = [
            'court_800' => '800 mots',
            'moyen_1500' => '1500 mots',
            'long_2500' => '2500 mots',
        ];

        $toneMap = [
            'pedagogue' => 'pédagogue',
            'direct' => 'direct',
            'conversationnel' => 'conversationnel',
            'formel' => 'formel',
            'opinion' => "d'opinion",
        ];

        $angleMap = [
            'guide_pratique' => 'guide pratique',
            'etude_de_cas' => 'étude de cas',
            'comparatif' => 'comparatif',
            'opinion' => 'opinion',
            'actualite_commentee' => 'actualité commentée',
            'tutoriel' => 'tutoriel',
            'faq' => 'FAQ',
        ];

        $prompt = "AGIS en tant que rédacteur senior spécialisé en contenu SEO/AEO/GEO 2026 pour le marché francophone canadien (FR-CA).\n\n";

        $prompt .= "CONTEXTE :\n";
        $prompt .= "- Sujet : {$params['subject']}\n";
        $prompt .= "- Public cible : {$params['audience']}\n";
        $prompt .= "- Objectif : Informer, guider ou convaincre selon le ton et l'angle demandés.\n\n";

        $prompt .= "PARAMÈTRES DE RÉDACTION :\n";
        $prompt .= "- Ton : {$toneMap[$params['tone']]}\n";
        $prompt .= "- Angle éditorial : {$angleMap[$params['angle']]}\n";
        $prompt .= "- Longueur cible : {$lengthMap[$params['length']]}\n";
        $prompt .= "- Niveau technique : {$params['tech_level']}\n";
        $prompt .= "- Langue : Français canadien (FR-CA), vocabulaire local adapté.\n";
        $prompt .= "- Mots-clés SEO à intégrer naturellement : ".implode(', ', $params['keywords'])."\n\n";

        if (! empty($params['sources'])) {
            $prompt .= "SOURCES À CONSULTER (à citer ou paraphraser si pertinent) :\n";
            foreach ($params['sources'] as $source) {
                $prompt .= "- {$source}\n";
            }
            $prompt .= "\n";
        }

        if (! empty($params['author_voice'])) {
            $prompt .= "VOIX DE L'AUTEUR (à respecter) :\n{$params['author_voice']}\n\n";
        }

        $prompt .= "STRUCTURE OBLIGATOIRE :\n";
        $prompt .= "1. Bloc answer-first : 2-3 phrases directes répondant à la question principale.\n";
        $prompt .= "2. Introduction courte (2-4 phrases) : contexte + promesse.\n";
        $prompt .= "3. H2 par intention de recherche (mots-clés ciblés).\n";
        $prompt .= "4. H3 sous-questions sous chaque H2 si nécessaire.\n";
        $prompt .= "5. Section FAQ : 5-10 questions/réponses (schema.org/FAQPage compatible).\n";
        $prompt .= "6. Si processus : section HowTo avec étapes numérotées (schema.org/HowTo).\n";
        $prompt .= "7. Conclusion brève + CTA.\n\n";

        $prompt .= "CONTRAINTES :\n";
        $prompt .= "- Zéro fluff, chaque phrase apporte valeur.\n";
        $prompt .= "- Exemples concrets adaptés au Québec/Canada francophone.\n";
        $prompt .= "- NE JAMAIS inventer de statistiques, dates ou citations.\n";
        $prompt .= "- Respecter la voix de l'auteur si fournie.\n";
        $prompt .= "- Optimiser AEO (réponse directe questions implicites).\n\n";

        $prompt .= "TERMINE PAR :\n";
        $prompt .= "« Vérifie les statistiques, sources et affirmations factuelles avant publication. Personnalise avec ton expérience. »\n\n";

        $prompt .= 'Génère uniquement le texte de l\'article, sans introduction ni explication.';

        $warning = "⚠️ Ce prompt est destiné à une IA générative. Tu dois IMPÉRATIVEMENT relire, vérifier les faits, adapter le ton et ajouter ton expertise avant publication. Un article 100% IA non vérifié = mauvaise qualité + risque Google 'scaled content abuse'.";

        $encodedPrompt = urlencode($prompt);
        $openUrl = match ($params['target_ai']) {
            'claude' => "https://claude.ai/new?q={$encodedPrompt}",
            'chatgpt' => "https://chat.openai.com/?q={$encodedPrompt}",
            'gemini' => "https://gemini.google.com/app?prompt={$encodedPrompt}",
            'perplexity' => "https://www.perplexity.ai/search?q={$encodedPrompt}",
            'mistral' => "https://chat.mistral.ai/?q={$encodedPrompt}",
            default => '',
        };

        return [
            'prompt' => $prompt,
            'warning' => $warning,
            'open_urls' => [$params['target_ai'] => $openUrl],
        ];
    }

    private function validate(array $params): void
    {
        $required = ['subject', 'audience', 'tone', 'angle', 'length', 'tech_level', 'target_ai', 'keywords'];
        foreach ($required as $key) {
            if (! isset($params[$key]) || $params[$key] === '' || $params[$key] === []) {
                throw new \InvalidArgumentException("Missing required parameter: {$key}");
            }
        }

        if (! in_array($params['tone'], self::VALID_TONES, true)) {
            throw new \InvalidArgumentException('Invalid tone');
        }
        if (! in_array($params['angle'], self::VALID_ANGLES, true)) {
            throw new \InvalidArgumentException('Invalid angle');
        }
        if (! in_array($params['length'], self::VALID_LENGTHS, true)) {
            throw new \InvalidArgumentException('Invalid length');
        }
        if (! in_array($params['tech_level'], self::VALID_TECH_LEVELS, true)) {
            throw new \InvalidArgumentException('Invalid tech_level');
        }
        if (! in_array($params['target_ai'], self::VALID_AIS, true)) {
            throw new \InvalidArgumentException('Invalid target_ai');
        }
    }
}
