<?php
declare(strict_types=1);
/**
 * @author MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * ACTION: seed des réglages de la cascade / auto-escalade de modèles IA (Modules\AI\Services\AiService)
 * RAISON: activable/désactivable sans toucher au code — formateur/admin ajuste modèles et mots-clés
 *         depuis le panneau Réglages > IA existant (aucun nouveau système de réglages créé)
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = [
            [
                'group' => 'ai',
                'key' => 'ai.escalation_enabled',
                'value' => 'false',
                'type' => 'boolean',
                'description' => 'Active la cascade / auto-escalade de modèles (modèle léger par défaut, escalade vers un modèle puissant si signal ESCALATE ou règle heuristique). Défaut désactivé tant que non testé en production.',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'ai',
                'key' => 'ai.model_primary',
                'value' => '',
                'type' => 'string',
                'description' => 'Modèle IA primaire (léger/économique) de la cascade. Laisser vide pour utiliser le modèle du chatbot (ai.chatbot_model) comme aujourd\'hui.',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'ai',
                'key' => 'ai.model_escalation',
                'value' => 'deepseek/deepseek-v3.2-20251201',
                'type' => 'string',
                'description' => 'Modèle IA d\'escalade (plus puissant), utilisé quand le modèle primaire signale une question trop complexe ou qu\'une règle heuristique se déclenche.',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'ai',
                'key' => 'ai.escalation_keywords',
                'value' => 'analyse complète,compare en détail,rédige un plan,explique en profondeur,audit complet,stratégie détaillée,démontre étape par étape,rédige un rapport détaillé',
                'type' => 'string',
                'description' => 'Mots-clés (séparés par des virgules) signalant une tâche complexe : déclenchent une escalade directe vers le modèle puissant, sans passer par le modèle primaire.',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'ai',
                'key' => 'ai.escalation_length_threshold',
                'value' => '2000',
                'type' => 'integer',
                'description' => 'Longueur (en caractères) du dernier message utilisateur au-delà de laquelle l\'escalade directe se déclenche (filet de sécurité). 0 = désactive ce critère.',
                'is_public' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('settings')->insertOrIgnore($settings);
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', [
                'ai.escalation_enabled',
                'ai.model_primary',
                'ai.model_escalation',
                'ai.escalation_keywords',
                'ai.escalation_length_threshold',
            ])
            ->delete();
    }
};
