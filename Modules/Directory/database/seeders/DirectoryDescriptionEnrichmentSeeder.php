<?php

declare(strict_types=1);

namespace Modules\Directory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Directory\Models\Tool;

class DirectoryDescriptionEnrichmentSeeder extends Seeder
{
    public function run(): void
    {
        $descriptions = [
            'chatgpt' => "ChatGPT, propulsé par le modèle GPT-4o, est sans conteste l'assistant IA le plus populaire au monde. Au-delà de la simple conversation, il peut naviguer sur le web en temps réel, générer des images via DALL-E 3, analyser des fichiers de données complexes et même écrire du code informatique. C'est un outil polyvalent qui offre une version gratuite performante, bien que l'abonnement Plus débloque tout son potentiel créatif et analytique.",
            'claude' => "Développé par Anthropic avec une approche axée sur la sécurité (IA constitutionnelle), Claude se distingue par sa capacité à traiter une quantité massive d'informations grâce à sa fenêtre contextuelle de 200 000 jetons. Il excelle particulièrement dans la rédaction nuancée et la programmation, où son modèle Claude Sonnet est souvent considéré comme le champion actuel. La fonctionnalité « Artifacts » permet de visualiser le code et les documents générés dans une fenêtre dédiée, rendant le travail collaboratif très fluide.",
            'midjourney' => "Midjourney est célèbre pour sa capacité à générer des images d'une qualité artistique époustouflante à partir de simples descriptions textuelles (prompts). Fonctionnant principalement via la plateforme Discord avec la commande /imagine, il offre une expérience communautaire unique, bien qu'une version web soit désormais disponible. Le réalisme et la créativité atteignent de nouveaux sommets, en faisant l'outil de prédilection des créatifs visuels.",
            'cursor' => "Cursor est un éditeur de code révolutionnaire, dérivé de VS Code, qui intègre l'intelligence artificielle directement au cœur de votre environnement de développement. Contrairement aux plugins classiques, il comprend l'intégralité de votre base de code, permettant des suggestions pertinentes par autocomplétion ou via la commande Cmd+K. Utilisant des modèles de pointe comme GPT-4o et Claude Sonnet, il transforme l'expérience de programmation en agissant comme un véritable binôme virtuel.",
            'perplexity' => "Perplexity se positionne comme un moteur de réponse conversationnel qui change la façon dont nous cherchons l'information en ligne. Au lieu d'une liste de liens bleus, il fournit des réponses directes, synthétisées et toujours accompagnées de citations de sources fiables pour vérification. Souvent qualifié de « tueur de Google » par la presse techno, il propose des modes de recherche ciblés (Focus) pour explorer spécifiquement des articles académiques, des vidéos YouTube ou des discussions Reddit.",
            'gemini' => "Gemini est la réponse multimodale de Google, capable de comprendre et de générer nativement du texte, des images, de l'audio et de la vidéo. Sa force réside dans son intégration profonde avec l'écosystème Google Workspace (Docs, Gmail, Drive) et sa fenêtre contextuelle immense permettant d'analyser de très longs documents. Accessible via une version gratuite généreuse, c'est un assistant puissant pour ceux qui vivent déjà dans l'univers Google.",
        ];

        foreach ($descriptions as $slug => $description) {
            $tool = Tool::where('slug->'.app()->getLocale(), $slug)->first()
                ?? Tool::where('slug->fr_CA', $slug)->first();

            if ($tool) {
                $tool->setTranslation('description', 'fr_CA', $description);
                $tool->save();
            }
        }
    }
}
