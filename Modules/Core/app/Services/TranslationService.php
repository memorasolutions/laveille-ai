<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslationService
{
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';

    private const FRENCH_COMMON_WORDS = [
        'le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'et', 'ou',
        'est', 'sont', 'pour', 'avec', 'dans', 'sur', 'par', 'au', 'aux',
        'ce', 'cette', 'qui', 'que', 'pas', 'plus', 'nous', 'vous', 'tout',
        'faire', 'avoir', 'peut', 'aussi', 'entre', 'comme', 'mais', 'ses',
    ];

    /**
     * Traduit un texte via OpenRouter GPT-5.
     * Retourne le texte original si deja en FR, si erreur, ou si cle API absente.
     */
    public static function translate(string $text, string $from = 'en', string $to = 'fr'): string
    {
        if (empty(trim($text)) || $from === $to) {
            return $text;
        }

        if ($to === 'fr' && self::looksLikeFrench($text)) {
            return $text;
        }

        $cacheKey = 'translation_'.md5($text.$from.$to);

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($text, $from, $to) {
            $apiKey = config('services.openrouter.api_key');
            if (! $apiKey) {
                return $text;
            }

            $systemPrompt = "Tu es un traducteur professionnel {$from} vers {$to}. "
                .'Traduis le texte suivant de maniere naturelle et fluide. '
                .'Retourne UNIQUEMENT la traduction, sans commentaire ni explication.';

            foreach (['openai/gpt-5', 'openai/gpt-5-mini'] as $model) {
                try {
                    $response = Http::timeout(30)
                        ->withoutVerifying()
                        ->withHeaders([
                            'Authorization' => 'Bearer '.$apiKey,
                            'Content-Type' => 'application/json',
                        ])
                        ->post(self::API_URL, OpenRouterPrivacy::applyTo([
                            'model' => $model,
                            'messages' => [
                                ['role' => 'system', 'content' => $systemPrompt],
                                ['role' => 'user', 'content' => $text],
                            ],
                            'temperature' => 0.3,
                        ]));

                    if ($response->successful()) {
                        $result = $response->json('choices.0.message.content', '');
                        $trimmed = trim($result);
                        if ($trimmed !== '') {
                            return $trimmed;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning("TranslationService: {$model} failed", [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $text;
        });
    }

    /**
     * Traduit PLUSIEURS textes courts en UN SEUL appel, avec alignement strict par index.
     *
     * Conçu pour des titres d'actualités affichés dans un écran d'administration : traduire un à
     * un coûterait autant d'appels que de titres, et l'écran attendrait. Un seul appel numéroté
     * suffit.
     *
     * TROIS GARANTIES, dans cet ordre d'importance :
     *
     * 1. **Jamais de correspondance mélangée.** Si le nombre de lignes rendues ne correspond pas
     *    EXACTEMENT au nombre de textes envoyés, on garde TOUS les originaux. Un titre traduit
     *    collé au mauvais article est bien pire qu'un titre resté en anglais : le premier est une
     *    erreur invisible, le second se voit.
     * 2. **L'échec est VISIBLE.** Le statut rendu vaut `indisponible` avec un motif, que l'appelant
     *    peut afficher. C'est la leçon du 2026-08-23 : l'enrichissement de l'annuaire est resté
     *    mort neuf jours parce qu'un refus de fournisseur se traduisait par un repli silencieux.
     *    Un journal dédié (canal `translation`, niveau `info` en dur) enregistre chaque échec,
     *    car `LOG_LEVEL=error` en production avale tout ce qui est en dessous.
     * 3. **Aucun texte n'est perdu.** En cas de refus, d'erreur réseau ou de clé absente, les
     *    originaux sont rendus tels quels : l'écran reste utilisable.
     *
     * ACTION : paramètre `$budgetSecondes` ajouté (2026-08-24, mesure en production) - le budget
     * de config (`services.openrouter.translation_budget_seconds`) protège l'ÉCRAN de
     * composition, dont l'appel est sur un chemin SYNCHRONE que Cloudflare coupe vers 100
     * secondes (incident du 23 août, voir le commentaire sur `$echeance` plus bas). Une commande
     * planifiée qui tourne en arrière-plan (Modules\News\Console\TranslateTitlesCommand) n'a
     * AUCUNE de ces deux contraintes, et le même budget de 15 s lui faisait rejeter 100 % de ses
     * lots : un lot RÉEL de 40 titres a mesuré 36,6 secondes pour une réponse au format
     * parfaitement conforme (compte de lignes concordant). `null` (défaut) laisse le comportement
     * actuel strictement inchangé - la config reste la seule source pour tout appelant qui ne
     * fournit pas ce paramètre. La clé de cache, la garantie de compte de lignes et la cascade de
     * modèles restent inchangées quel que soit le budget.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: mesure en production, 2026-08-24 - ne pas confondre le budget de l'écran avec celui
     * d'une commande planifiée.
     *
     * @param  array<int, string>  $textes
     * @return array{titres: array<int, string>, statut: string, motif: string|null}
     */
    public static function translateBatch(array $textes, string $from = 'en', string $to = 'fr', ?int $budgetSecondes = null): array
    {
        $textes = array_values($textes);
        if ($textes === []) {
            return ['titres' => [], 'statut' => 'ok', 'motif' => null];
        }

        $echec = static function (string $motif) use ($textes): array {
            Log::channel('translation')->warning('Traduction par lot indisponible, originaux conservés.', [
                'motif' => $motif,
                'nombre' => count($textes),
            ]);

            return ['titres' => $textes, 'statut' => 'indisponible', 'motif' => $motif];
        };

        $apiKey = config('services.openrouter.api_key');
        if (! $apiKey) {
            return $echec("Aucune clé d'API OpenRouter configurée.");
        }

        $cacheKey = 'translation_batch_'.md5(implode("\x1f", $textes).$from.$to);
        $enCache = Cache::get($cacheKey);
        if (is_array($enCache)) {
            return $enCache;
        }

        $numerotes = [];
        foreach ($textes as $i => $t) {
            // Les sauts de ligne casseraient le découpage ligne à ligne de la réponse.
            $numerotes[] = ($i + 1).'. '.trim(preg_replace('/\s+/u', ' ', $t) ?? $t);
        }

        $consigne = "Tu traduis des titres d'actualité de {$from} vers {$to}, pour un lectorat québécois. "
            .'Rends EXACTEMENT une ligne par titre reçu, dans le MÊME ORDRE, préfixée du même numéro suivi d\'un point. '
            .'Aucune ligne vide, aucun commentaire, aucune ligne supplémentaire. '
            .'Garde les noms propres, les noms de produits et les sigles tels quels. '
            .'Français impeccable avec tous les accents. N\'utilise JAMAIS le tiret cadratin.';

        // BUDGET TOTAL, jamais par modèle. Incident du 2026-08-23 : ce lot s'accordait 45
        // secondes PAR modèle et la cascade en essaie trois, soit 135 secondes au pire. Or
        // Cloudflare coupe une réponse d'origine vers 100 secondes, et cet appel est sur le
        // chemin SYNCHRONE de l'écran de composition. Résultat mesuré : l'écran ne répondait
        // plus du tout et affichait « 0 actualité » alors que 526 articles étaient collectés.
        // Une fonction cosmétique - traduire des titres - ne doit jamais pouvoir immobiliser
        // un écran. Même mécanisme que le budget de la cascade d'enrichissement.
        // $budgetSecondes (voir docblock ci-dessus) prime sur la config quand l'appelant le
        // fournit explicitement - seul cas où ce n'est PAS l'écran qui appelle.
        $echeance = microtime(true) + max(3, $budgetSecondes ?? (int) config('services.openrouter.translation_budget_seconds', 8));

        foreach (self::batchModels() as $model) {
            $restant = (int) floor($echeance - microtime(true));

            if ($restant < 2) {
                Log::channel('translation')->warning('Budget de traduction épuisé : titres laissés en version originale.', [
                    'dernier_modele_non_tente' => $model,
                    'nombre' => count($textes),
                ]);

                break;
            }

            try {
                $response = Http::timeout($restant)
                    ->withoutVerifying()
                    ->withHeaders([
                        'Authorization' => 'Bearer '.$apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post(self::API_URL, OpenRouterPrivacy::applyTo([
                        'model' => $model,
                        'messages' => [
                            ['role' => 'system', 'content' => $consigne],
                            ['role' => 'user', 'content' => implode("\n", $numerotes)],
                        ],
                        'temperature' => 0.2,
                    ]));

                if (! $response->successful()) {
                    Log::channel('translation')->warning('Refus du fournisseur pour la traduction par lot.', [
                        'model' => $model,
                        'code' => $response->status(),
                        'corps' => mb_substr($response->body(), 0, 300),
                    ]);

                    continue;
                }

                $lignes = preg_split('/\R/u', trim((string) $response->json('choices.0.message.content', '')));
                $lignes = array_values(array_filter(array_map('trim', $lignes ?: []), static fn ($l) => $l !== ''));

                // GARANTIE 1 : tout écart de compte invalide le lot entier.
                if (count($lignes) !== count($textes)) {
                    Log::channel('translation')->warning('Compte de lignes incohérent, lot entier rejeté.', [
                        'model' => $model,
                        'attendu' => count($textes),
                        'recu' => count($lignes),
                    ]);

                    continue;
                }

                $traduits = [];
                foreach ($lignes as $i => $ligne) {
                    $sansNumero = preg_replace('/^\s*\d+\s*[.)]\s*/u', '', $ligne) ?? $ligne;
                    $sansNumero = trim(str_replace('—', '-', $sansNumero));
                    $traduits[] = $sansNumero !== '' ? $sansNumero : $textes[$i];
                }

                $resultat = ['titres' => $traduits, 'statut' => 'ok', 'motif' => null];
                Cache::put($cacheKey, $resultat, now()->addHours(24));

                return $resultat;
            } catch (\Throwable $e) {
                Log::channel('translation')->warning('Erreur pendant la traduction par lot.', [
                    'model' => $model,
                    'erreur' => $e->getMessage(),
                ]);
            }
        }

        return $echec('Aucun modèle de la cascade n\'a rendu un lot exploitable.');
    }

    /**
     * Cascade de modèles pour la traduction par lot, pilotée par la configuration.
     *
     * Volontairement distincte de la cascade de translate() : celle-ci doit rendre un FORMAT
     * (une ligne numérotée par entrée), pas seulement une bonne traduction. Configurable sans
     * redéploiement, pour pouvoir changer de modèle si un fournisseur refuse la rétention nulle
     * imposée par OpenRouterPrivacy - le cas qui a immobilisé l'enrichissement de l'annuaire.
     *
     * @return array<int, string>
     */
    private static function batchModels(): array
    {
        $models = config('services.openrouter.translation_models');

        return is_array($models) && $models !== []
            ? $models
            : ['openai/gpt-5', 'deepseek/deepseek-v4-flash', 'openai/gpt-5-mini'];
    }

    /**
     * Detection heuristique simple : le texte est-il deja en francais ?
     */
    private static function looksLikeFrench(string $text): bool
    {
        $words = preg_split('/[\s\p{P}]+/u', mb_strtolower($text));
        $words = array_filter($words, fn ($w) => mb_strlen($w) > 1);

        if (count($words) < 5) {
            return false;
        }

        $frenchCount = 0;
        foreach ($words as $word) {
            if (in_array($word, self::FRENCH_COMMON_WORDS, true)) {
                $frenchCount++;
            }
        }

        return ($frenchCount / count($words)) > 0.3;
    }
}
