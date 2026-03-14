<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Services;

use App\Models\User;
use Modules\Notifications\Models\NotificationPreference;

class NotificationPreferenceService
{
    /**
     * Types de notification configurables avec labels et canaux supportés.
     *
     * @return array<string, array{label: string, channels: string[]}>
     */
    public static function configurableTypes(): array
    {
        return [
            'system_alert' => ['label' => __('Alertes système'), 'channels' => ['mail', 'database']],
            'password_changed' => ['label' => __('Changement de mot de passe'), 'channels' => ['mail', 'database']],
            'payment_failed' => ['label' => __('Échec de paiement'), 'channels' => ['mail', 'database']],
            'payment_succeeded' => ['label' => __('Paiement réussi'), 'channels' => ['mail', 'database']],
            'subscription_cancelled' => ['label' => __('Annulation d\'abonnement'), 'channels' => ['mail', 'database']],
            'trial_ending' => ['label' => __('Fin de période d\'essai'), 'channels' => ['mail', 'database']],
            'team_invitation' => ['label' => __('Invitation d\'équipe'), 'channels' => ['mail']],
            'chat_lead' => ['label' => __('Nouveau lead chatbot'), 'channels' => ['mail', 'database']],
            'newsletter_digest' => ['label' => __('Résumé newsletter'), 'channels' => ['mail']],
        ];
    }

    /**
     * Obtenir les préférences d'un utilisateur indexées par type.channel.
     *
     * @return array<string, bool>
     */
    public static function getPreferences(User $user): array
    {
        $prefs = NotificationPreference::where('user_id', $user->id)->get();
        $map = [];

        foreach ($prefs as $pref) {
            $map[$pref->notification_type.'.'.$pref->channel] = $pref->enabled;
        }

        return $map;
    }

    /**
     * Mettre à jour les préférences d'un utilisateur.
     *
     * @param  array<string, bool>  $preferences  Format: ['type.channel' => bool]
     */
    public static function updatePreferences(User $user, array $preferences): void
    {
        $types = self::configurableTypes();

        foreach ($types as $type => $config) {
            foreach ($config['channels'] as $channel) {
                $key = $type.'.'.$channel;
                $enabled = $preferences[$key] ?? true;

                NotificationPreference::updateOrCreate(
                    ['user_id' => $user->id, 'notification_type' => $type, 'channel' => $channel],
                    ['enabled' => $enabled]
                );
            }
        }
    }

    /**
     * Vérifier si un utilisateur a activé un type de notification pour un canal.
     */
    public static function isEnabled(User $user, string $type, string $channel = 'mail'): bool
    {
        $pref = NotificationPreference::where('user_id', $user->id)
            ->where('notification_type', $type)
            ->where('channel', $channel)
            ->first();

        return $pref ? $pref->enabled : true;
    }
}
