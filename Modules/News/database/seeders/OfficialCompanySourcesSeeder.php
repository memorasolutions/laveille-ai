<?php

declare(strict_types=1);

namespace Modules\News\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\News\Models\NewsSource;

/**
 * 2026-08-29 : peuple les 12 flux officiels de compagnies d'IA déjà vérifiés par requête réelle
 * (le mandat listait les compagnies, pas les adresses de flux ; celles-ci ont été retrouvées ici
 * via mcp__perplexity-pro-playwright__pp_search - seul outil de recherche web autorisé sur ce
 * projet - jamais depuis la mémoire du modèle).
 *
 * `company` porte le nom EXACTEMENT tel que donné par le mandat, sans normalisation de mon cru
 * (donc 'Google DeepMind' et 'Google AI' restent deux compagnies distinctes plutôt que fusionnées
 * sous 'Google', et 'Microsoft Research' / 'AWS Machine Learning' / 'Apple Machine Learning' ne
 * sont pas raccourcis en 'Microsoft' / 'Amazon' / 'Apple' - un regroupement reste un choix
 * éditorial, pas le mien à faire en silence). Seule exception, explicitement indiquée par le
 * mandat lui-même : Qwen -> compagnie 'Alibaba'.
 *
 * TOUTES les adresses de cette liste ont été VÉRIFIÉES PAR REQUÊTE RÉELLE le 2026-08-29, pas
 * seulement trouvées par recherche : code HTTP, nombre d'entrées, et DATE de la plus récente.
 * Deux des douze candidats initiaux sont tombés à cette mesure - EleutherAI répondait 404, et le
 * flux Qwen retenu était vide. Une adresse plausible n'est pas une adresse vivante, et le seul
 * moyen de le savoir est de la demander.
 *
 * LE CRITÈRE N'EST PAS « répond 200 », C'EST « a publié récemment ». Les onze sources retenues
 * avaient toutes publié dans les 8 jours précédant la mesure. Qwen a été ÉCARTÉE pour cette
 * raison précise : son flux répond, mais son dernier billet datait de 340 jours (voir le
 * commentaire à l'emplacement où elle aurait figuré). Rebrancher un flux muet donnerait
 * l'illusion d'une couverture sans jamais rien remonter.
 *
 * Idempotent, sans jamais écraser une source déjà présente : chaque entrée est d'abord cherchée
 * par URL EXACTE (jamais par nom - deux sessions ont déjà pu nommer la même source différemment).
 * Trouvée -> seuls 'company'/'is_official' sont posés dessus (name/url/category/language/active
 * JAMAIS touchés, quelle que soit leur valeur actuelle - la ligne peut avoir été ajoutée avant ce
 * lot, potentiellement avec un statut actif/inactif déjà décidé pour une bonne raison). Absente ->
 * créée avec les valeurs ci-dessous. Rejouable à l'identique : la 2e exécution ne fait plus rien.
 *
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
class OfficialCompanySourcesSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            [
                'name' => 'OpenAI Blog',
                'url' => 'https://openai.com/blog/rss.xml',
                'company' => 'OpenAI',
                'category' => 'official',
                'language' => 'en',
                'active' => true,
                'is_official' => true,
            ],
            [
                'name' => 'Google DeepMind',
                'url' => 'https://deepmind.google/blog/rss.xml',
                'company' => 'Google DeepMind',
                'category' => 'official',
                'language' => 'en',
                'active' => true,
                'is_official' => true,
            ],
            [
                'name' => 'Google AI',
                'url' => 'https://blog.google/innovation-and-ai/technology/ai/rss/',
                'company' => 'Google AI',
                'category' => 'official',
                'language' => 'en',
                'active' => true,
                'is_official' => true,
            ],
            [
                'name' => 'Hugging Face',
                'url' => 'https://huggingface.co/blog/feed.xml',
                'company' => 'Hugging Face',
                'category' => 'official',
                'language' => 'en',
                'active' => true,
                'is_official' => true,
            ],
            [
                'name' => 'Microsoft Research',
                'url' => 'https://www.microsoft.com/en-us/research/feed/',
                'company' => 'Microsoft Research',
                'category' => 'official',
                'language' => 'en',
                'active' => true,
                'is_official' => true,
            ],
            // QWEN (Alibaba) : AUCUNE source retenue, et c'est une décision mesurée, pas un oubli.
            // Le 2026-08-29, les trois candidats ont été testés par requête réelle :
            //   - github.com/QwenLM/Qwen3/releases.atom -> 200 mais ZÉRO entrée (500 octets vides) ;
            //   - qwenlm.github.io/blog/index.xml -> 200 et 44 entrées, mais la plus récente date
            //     du 23 septembre 2025, soit 340 jours de silence : le flux existe sans être vivant ;
            //   - qwen.ai/blog/rss.xml -> 200 mais aucune entrée exploitable.
            // Un flux qui répond n'est pas un flux vivant. Brancher l'un des trois donnerait
            // l'ILLUSION d'une couverture Qwen sans jamais rien remonter, ce qui est pire que
            // l'absence assumée. Qwen rejoint donc Anthropic, DeepSeek, Cohere et Mila : compagnie
            // suivie éditorialement, sans flux officiel exploitable à ce jour. Si une adresse
            // vivante apparaît, l'ajouter ici - jamais un doublon.
            [
                'name' => 'Mistral AI',
                'url' => 'https://mistral.ai/rss.xml',
                'company' => 'Mistral',
                'category' => 'official',
                'language' => 'en',
                'active' => true,
                'is_official' => true,
            ],
            [
                'name' => 'NVIDIA Blog',
                'url' => 'https://blogs.nvidia.com/feed/',
                'company' => 'NVIDIA',
                'category' => 'official',
                'language' => 'en',
                'active' => true,
                'is_official' => true,
            ],
            [
                'name' => 'AWS Machine Learning',
                'url' => 'https://aws.amazon.com/blogs/machine-learning/feed/',
                'company' => 'AWS Machine Learning',
                'category' => 'official',
                'language' => 'en',
                'active' => true,
                'is_official' => true,
            ],
            [
                'name' => 'Apple Machine Learning Research',
                'url' => 'https://machinelearning.apple.com/rss.xml',
                'company' => 'Apple Machine Learning',
                'category' => 'official',
                'language' => 'en',
                'active' => true,
                'is_official' => true,
            ],
            [
                'name' => 'EleutherAI',
                // MESURÉ le 2026-08-29 : /rss/ répond 404. L'adresse Hugo canonique /index.xml
                // renvoie 52 entrées, dernière publication 3 jours avant la mesure.
                'url' => 'https://blog.eleuther.ai/index.xml',
                'company' => 'EleutherAI',
                'category' => 'official',
                'language' => 'en',
                'active' => true,
                'is_official' => true,
            ],
            [
                'name' => 'NIST News',
                // Flux général du NIST : aucun flux exclusivement IA n'existe (confirmé par
                // pp_search) - c'est néanmoins le canal officiel, la section IA y est incluse.
                'url' => 'https://www.nist.gov/news-events/news/rss.xml',
                'company' => 'NIST',
                'category' => 'official',
                'language' => 'en',
                'active' => true,
                'is_official' => true,
            ],
        ];

        foreach ($sources as $data) {
            $existante = NewsSource::where('url', $data['url'])->first();

            if ($existante === null) {
                NewsSource::create($data);

                continue;
            }

            // Ligne déjà présente (ajoutée avant ce lot, ou par un run précédent) : on ne pose
            // QUE le tag compagnie/officiel dessus, jamais son name/url/category/language/active.
            $existante->company = $data['company'];
            $existante->is_official = $data['is_official'];
            $existante->save();
        }
    }
}
