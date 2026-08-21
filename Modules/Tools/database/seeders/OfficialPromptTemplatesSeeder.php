<?php

declare(strict_types=1);

namespace Modules\Tools\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Tools\Models\SavedPrompt;

/**
 * Bibliothèque de gabarits curés (2026-08-20, Brique 1, design approuvé docs/specs/
 * 2026-08-20-bibliotheque-pre-prompts-design.md). Un gabarit = un SavedPrompt ordinaire
 * (is_official=true + is_public=true, chargé par le visiteur via le chemin ?remix={public_id}
 * DÉJÀ existant et testé - voir PublicPromptController::remixData()). Aucun nouveau moteur,
 * aucun champ propre au gabarit : seul le contenu de `params` (état complet du wizard, structure
 * `wizardParams`/`_applyWizardParams` de constructeur-prompts-core.js) change d'un gabarit à
 * l'autre, exactement comme pour un SavedPrompt créé par un utilisateur.
 *
 * Espaces à remplir : RÈGLE D'OR du moteur (constructeur-prompts-core.js, ~L3023) - aucune
 * syntaxe visible (pas d'accolades ni de crochets). `spaces[].text` doit être une sous-chaîne
 * EXACTE, à frontières de mots, présente dans `taskObject` (voir _hasBoundedOccurrence côté JS) -
 * jamais un texte entre crochets. Les phrases ci-dessous sont rédigées pour que chaque « champ à
 * remplir » soit une chaîne exacte réutilisée telle quelle dans `spaces`.
 *
 * Catégorie : portée par le tableau `tags` DÉJÀ existant sur SavedPrompt (un seul tag = la
 * catégorie), plutôt que par une nouvelle colonne - choix le plus DRY, et conforme à la frontière
 * « pas de champ propre au gabarit » (tags est un champ générique, pas un moteur de templating).
 * L'icône de carte est dérivée de la catégorie côté Blade (petit dictionnaire catégorie→emoji,
 * même pattern que $defaultTaskCards) - jamais stockée non plus.
 */
class OfficialPromptTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $systemUser = $this->systemUser();

        foreach ($this->templates() as $template) {
            SavedPrompt::updateOrCreate(
                // Clé stable d'idempotence (spec : "un slug/name stable") : SavedPrompt n'a pas de
                // colonne slug, le couple (user_id système, name) suffit puisque seul ce seeder
                // écrit sous cet utilisateur. Un relancement ne crée donc jamais de doublon et
                // préserve le public_id déjà attribué (généré une seule fois, au premier passage,
                // par SavedPrompt::booted()).
                [
                    'user_id' => $systemUser->id,
                    'name' => $template['name'],
                ],
                [
                    'prompt_text' => $template['prompt_text'],
                    'params' => $template['params'],
                    'is_public' => true,
                    'is_official' => true,
                    'is_favorite' => false,
                    'tags' => [$template['category']],
                ]
            );
        }
    }

    /**
     * Compte système propriétaire des gabarits officiels. N'a pas besoin d'être connectable
     * (mot de passe aléatoire jamais communiqué, is_active=false - email non vérifié par défaut,
     * 'email_verified_at' n'étant de toute façon pas fillable sur User) - il ne sert qu'à porter
     * des SavedPrompt, jamais à se connecter. Pattern updateOrCreate/firstOrCreate calqué sur
     * database/seeders/DatabaseSeeder.php (superAdmin/moderator).
     */
    private function systemUser(): User
    {
        return User::firstOrCreate(
            ['email' => 'templates@laveille.ai'],
            [
                'name' => 'Gabarits laveille.ai',
                'password' => Hash::make(Str::random(40)),
                'is_active' => false,
            ]
        );
    }

    /**
     * @return array<int, array{name: string, category: string, prompt_text: string, params: array}>
     */
    private function templates(): array
    {
        return [
            [
                'name' => 'Courriel professionnel à un client',
                'category' => 'Rédiger et communiquer',
                'prompt_text' => "Tu es un(e) Rédacteur web professionnel.\n\nTa tâche : Rédige un courriel professionnel destiné à Nom du client, au sujet de Sujet du courriel, avec un ton Ton souhaité.",
                'params' => [
                    'personaType' => 'preset',
                    'personaPreset' => 'redacteur_web',
                    'verbType' => 'preset',
                    'verb' => 'Rédige',
                    'taskObject' => 'un courriel professionnel destiné à Nom du client, au sujet de Sujet du courriel, avec un ton Ton souhaité.',
                    'spaces' => [
                        ['text' => 'Nom du client'],
                        ['text' => 'Sujet du courriel'],
                        ['text' => 'Ton souhaité'],
                    ],
                    'audienceType' => 'preset',
                    'audiencePresets' => ['clients'],
                ],
            ],
            [
                'name' => 'Résumé de réunion en points d\'action',
                'category' => 'Résumer et analyser',
                'prompt_text' => "Tu es un(e) Analyste de données.\n\nTa tâche : Résume ces notes de réunion en extrayant les décisions prises, les responsables assignés et les échéances à respecter : Notes de réunion.",
                'params' => [
                    'personaType' => 'preset',
                    'personaPreset' => 'analyste',
                    'verbType' => 'preset',
                    'verb' => 'Résume',
                    'taskObject' => 'ces notes de réunion en extrayant les décisions prises, les responsables assignés et les échéances à respecter : Notes de réunion.',
                    'spaces' => [
                        ['text' => 'Notes de réunion'],
                    ],
                    'formats' => ['Liste à puces'],
                ],
            ],
            [
                'name' => 'Publication pour les réseaux sociaux',
                'category' => 'Marketing et ventes',
                'prompt_text' => "Tu es un(e) Gestionnaire de médias sociaux.\n\nTa tâche : Rédige une publication Réseau social au sujet de Sujet de la publication, destinée à Public visé, avec un ton Ton souhaité.",
                'params' => [
                    'personaType' => 'preset',
                    'personaPreset' => 'community_manager',
                    'verbType' => 'preset',
                    'verb' => 'Rédige',
                    'taskObject' => 'une publication Réseau social au sujet de Sujet de la publication, destinée à Public visé, avec un ton Ton souhaité.',
                    'spaces' => [
                        ['text' => 'Réseau social'],
                        ['text' => 'Sujet de la publication'],
                        ['text' => 'Public visé'],
                        ['text' => 'Ton souhaité'],
                    ],
                ],
            ],
            [
                'name' => 'Traduire en adaptant au public',
                'category' => 'Rédiger et communiquer',
                'prompt_text' => "Tu es un(e) Rédacteur web professionnel.\n\nTa tâche : Traduis ce texte en Langue cible en l'adaptant à Public visé : Texte à traduire.",
                'params' => [
                    'personaType' => 'preset',
                    'personaPreset' => 'redacteur_web',
                    'verbType' => 'preset',
                    'verb' => 'Traduis',
                    'taskObject' => 'ce texte en Langue cible en l\'adaptant à Public visé : Texte à traduire.',
                    'spaces' => [
                        ['text' => 'Langue cible'],
                        ['text' => 'Public visé'],
                        ['text' => 'Texte à traduire'],
                    ],
                ],
            ],
            [
                'name' => 'Réécrire un texte pour le rendre plus clair',
                'category' => 'Rédiger et communiquer',
                'prompt_text' => "Tu es un(e) Rédacteur web professionnel.\n\nTa tâche : Réécris ce texte pour le rendre plus clair et plus concis, sans en changer le sens : Texte à réécrire.",
                'params' => [
                    'personaType' => 'preset',
                    'personaPreset' => 'redacteur_web',
                    'verbType' => 'custom',
                    'verbCustom' => 'Réécris',
                    'taskObject' => 'ce texte pour le rendre plus clair et plus concis, sans en changer le sens : Texte à réécrire.',
                    'spaces' => [
                        ['text' => 'Texte à réécrire'],
                    ],
                ],
            ],
            [
                'name' => 'Rédiger une offre d\'emploi',
                'category' => 'RH et opérations',
                'prompt_text' => "Tu es un(e) Spécialiste en ressources humaines.\n\nTa tâche : Rédige une offre d'emploi pour le poste de Titre du poste chez Nom de l'entreprise, en mettant en avant Atouts à mettre en valeur.",
                'params' => [
                    'personaType' => 'preset',
                    'personaPreset' => 'rh',
                    'verbType' => 'preset',
                    'verb' => 'Rédige',
                    'taskObject' => 'une offre d\'emploi pour le poste de Titre du poste chez Nom de l\'entreprise, en mettant en avant Atouts à mettre en valeur.',
                    'spaces' => [
                        ['text' => 'Titre du poste'],
                        ['text' => 'Nom de l\'entreprise'],
                        ['text' => 'Atouts à mettre en valeur'],
                    ],
                ],
            ],
        ];
    }
}
