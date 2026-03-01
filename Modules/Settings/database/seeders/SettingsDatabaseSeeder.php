<?php

declare(strict_types=1);

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Models\Setting;

class SettingsDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['group' => 'general', 'key' => 'site_name', 'value' => 'Laravel Core', 'type' => 'string', 'description' => 'Nom du site'],
            ['group' => 'general', 'key' => 'site_description', 'value' => 'Application Laravel Core', 'type' => 'string', 'description' => 'Description du site'],
            ['group' => 'general', 'key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'description' => 'Mode maintenance'],
            ['group' => 'mail', 'key' => 'mail_from_name', 'value' => 'Laravel Core', 'type' => 'string', 'description' => 'Nom expéditeur'],
            ['group' => 'mail', 'key' => 'mail_from_address', 'value' => 'noreply@example.com', 'type' => 'string', 'description' => 'Adresse expéditeur'],
            ['group' => 'mail', 'key' => 'mail_host', 'value' => '', 'type' => 'string', 'description' => 'Serveur SMTP (ex: smtp.gmail.com)'],
            ['group' => 'mail', 'key' => 'mail_port', 'value' => '587', 'type' => 'number', 'description' => 'Port SMTP (587 pour TLS, 465 pour SSL)'],
            ['group' => 'mail', 'key' => 'mail_username', 'value' => '', 'type' => 'string', 'description' => 'Nom d\'utilisateur SMTP'],
            ['group' => 'mail', 'key' => 'mail_password', 'value' => '', 'type' => 'string', 'description' => 'Mot de passe SMTP'],
            ['group' => 'mail', 'key' => 'mail_encryption', 'value' => 'tls', 'type' => 'string', 'description' => 'Chiffrement (tls ou ssl)'],
            ['group' => 'seo', 'key' => 'meta_title', 'value' => 'Laravel Core', 'type' => 'string', 'description' => 'Titre meta par défaut', 'is_public' => true],
            ['group' => 'seo', 'key' => 'meta_description', 'value' => '', 'type' => 'string', 'description' => 'Description meta par défaut', 'is_public' => true],
            ['group' => 'seo', 'key' => 'looker_studio_url', 'value' => '', 'type' => 'string', 'description' => 'URL du rapport Google Looker Studio (embed)'],

            // Branding
            ['group' => 'branding', 'key' => 'backoffice.theme', 'value' => 'backend', 'type' => 'string', 'description' => 'Thème du panneau administration', 'is_public' => false],
            ['group' => 'branding', 'key' => 'branding.logo_light', 'value' => '', 'type' => 'string', 'description' => 'Logo pour le mode clair (chemin relatif)'],
            ['group' => 'branding', 'key' => 'branding.logo_dark', 'value' => '', 'type' => 'string', 'description' => 'Logo pour le mode sombre (chemin relatif)'],
            ['group' => 'branding', 'key' => 'branding.logo_icon', 'value' => '', 'type' => 'string', 'description' => 'Logo icône pour la sidebar réduite'],
            ['group' => 'branding', 'key' => 'branding.favicon', 'value' => '', 'type' => 'string', 'description' => 'Favicon du site (chemin relatif)'],
            ['group' => 'branding', 'key' => 'branding.primary_color', 'value' => '#487FFF', 'type' => 'string', 'description' => 'Couleur primaire du thème'],
            ['group' => 'branding', 'key' => 'branding.font_family', 'value' => 'Inter', 'type' => 'string', 'description' => 'Police principale'],
            ['group' => 'branding', 'key' => 'branding.font_url', 'value' => '', 'type' => 'string', 'description' => 'URL @import de la police Google Fonts'],
            ['group' => 'branding', 'key' => 'branding.footer_text', 'value' => '', 'type' => 'string', 'description' => 'Texte du footer gauche'],
            ['group' => 'branding', 'key' => 'branding.footer_right', 'value' => '', 'type' => 'string', 'description' => 'Texte du footer droit'],
            ['group' => 'branding', 'key' => 'branding.login_title', 'value' => 'Connexion', 'type' => 'string', 'description' => 'Titre de la page de connexion'],
            ['group' => 'branding', 'key' => 'branding.login_subtitle', 'value' => '', 'type' => 'string', 'description' => 'Sous-titre de la page de connexion'],

            // Homepage
            ['group' => 'homepage', 'key' => 'homepage.type', 'value' => 'landing', 'type' => 'string', 'description' => 'Type de page d\'accueil (landing = page par défaut, page = page statique)'],
            ['group' => 'homepage', 'key' => 'homepage.page_id', 'value' => '', 'type' => 'string', 'description' => 'ID de la page statique à utiliser comme accueil (si type = page)'],

            // SMS (voip.ms)
            // Sécurité
            ['group' => 'security', 'key' => 'security.max_login_attempts', 'value' => '5', 'type' => 'number', 'description' => 'Nombre max de tentatives de connexion avant verrouillage'],
            ['group' => 'security', 'key' => 'security.lockout_duration', 'value' => '30', 'type' => 'number', 'description' => 'Durée du verrouillage en minutes'],
            ['group' => 'security', 'key' => 'security.password_min_length', 'value' => '8', 'type' => 'number', 'description' => 'Longueur minimale du mot de passe'],
            ['group' => 'security', 'key' => 'security.password_require_uppercase', 'value' => 'true', 'type' => 'boolean', 'description' => 'Exiger une majuscule dans le mot de passe'],
            ['group' => 'security', 'key' => 'security.password_require_number', 'value' => 'true', 'type' => 'boolean', 'description' => 'Exiger un chiffre dans le mot de passe'],
            ['group' => 'security', 'key' => 'security.password_require_special', 'value' => 'false', 'type' => 'boolean', 'description' => 'Exiger un caractère spécial dans le mot de passe'],
            ['group' => 'security', 'key' => 'security.captcha_enabled', 'value' => 'false', 'type' => 'boolean', 'description' => 'Activer reCAPTCHA v3 sur les formulaires'],
            ['group' => 'security', 'key' => 'security.recaptcha_site_key', 'value' => '', 'type' => 'string', 'description' => 'Clé de site reCAPTCHA v3'],
            ['group' => 'security', 'key' => 'security.recaptcha_secret_key', 'value' => '', 'type' => 'string', 'description' => 'Clé secrète reCAPTCHA v3'],

            // Push notifications
            ['group' => 'push', 'key' => 'push.web_push_enabled', 'value' => 'false', 'type' => 'boolean', 'description' => 'Activer les notifications push navigateur'],
            ['group' => 'push', 'key' => 'push.vapid_public_key', 'value' => '', 'type' => 'string', 'description' => 'Clé publique VAPID pour les notifications push'],
            ['group' => 'push', 'key' => 'push.vapid_private_key', 'value' => '', 'type' => 'string', 'description' => 'Clé privée VAPID pour les notifications push'],

            ['group' => 'sms', 'key' => 'sms_enabled', 'value' => 'false', 'type' => 'boolean', 'description' => 'Activer l\'envoi de SMS via VoIP.ms'],
            ['group' => 'sms', 'key' => 'voipms_api_username', 'value' => '', 'type' => 'string', 'description' => 'Courriel du compte VoIP.ms'],
            ['group' => 'sms', 'key' => 'voipms_api_password', 'value' => '', 'type' => 'string', 'description' => 'Mot de passe API VoIP.ms (pas le mot de passe du compte)'],
            ['group' => 'sms', 'key' => 'voipms_did_number', 'value' => '', 'type' => 'string', 'description' => 'Numéro DID VoIP.ms (expéditeur)'],
            ['group' => 'sms', 'key' => 'sms_button_delay_seconds', 'value' => '10', 'type' => 'number', 'description' => 'Délai en secondes avant d\'afficher le bouton SMS'],
            ['group' => 'sms', 'key' => 'magic_link_expiry_minutes', 'value' => '15', 'type' => 'number', 'description' => 'Durée de validité du code OTP (minutes)'],

            // Mentions légales
            ['group' => 'legal', 'key' => 'legal.company_address', 'value' => '', 'type' => 'string', 'description' => 'Adresse de l\'entreprise (affichée sur la page mentions légales)'],
            ['group' => 'legal', 'key' => 'legal.director_name', 'value' => '', 'type' => 'string', 'description' => 'Nom du directeur de la publication'],
            ['group' => 'legal', 'key' => 'legal.hosting_name', 'value' => '', 'type' => 'string', 'description' => 'Nom de l\'hébergeur'],
            ['group' => 'legal', 'key' => 'legal.hosting_address', 'value' => '', 'type' => 'string', 'description' => 'Adresse de l\'hébergeur'],
            ['group' => 'legal', 'key' => 'legal.hosting_phone', 'value' => '', 'type' => 'string', 'description' => 'Numéro de téléphone de l\'hébergeur'],

            // Blog - Révisions
            ['group' => 'blog', 'key' => 'blog.revision_max_count', 'value' => '50', 'type' => 'number', 'description' => 'Nombre maximum de révisions conservées par article'],
            ['group' => 'blog', 'key' => 'blog.revision_auto_cleanup', 'value' => 'true', 'type' => 'boolean', 'description' => 'Nettoyage automatique des anciennes révisions'],

            // Rétention des données
            ['group' => 'retention', 'key' => 'retention.login_attempts_days', 'value' => '90', 'type' => 'number', 'description' => 'Durée de conservation des tentatives de connexion (jours)'],
            ['group' => 'retention', 'key' => 'retention.sent_emails_days', 'value' => '90', 'type' => 'number', 'description' => 'Durée de conservation des emails envoyés (jours)'],
            ['group' => 'retention', 'key' => 'retention.activity_log_days', 'value' => '180', 'type' => 'number', 'description' => 'Durée de conservation des logs d\'activité (jours)'],
            ['group' => 'retention', 'key' => 'retention.blocked_ips_days', 'value' => '365', 'type' => 'number', 'description' => 'Durée de conservation des IPs bloquées expirées (jours)'],

            // Intelligence artificielle (OpenRouter)
            ['group' => 'ai', 'key' => 'ai.openrouter_api_key', 'value' => '', 'type' => 'string', 'description' => 'Clé API OpenRouter'],
            ['group' => 'ai', 'key' => 'ai.default_model', 'value' => 'meta-llama/llama-3.3-70b-instruct:free', 'type' => 'string', 'description' => 'Modèle IA par défaut'],
            ['group' => 'ai', 'key' => 'ai.chatbot_model', 'value' => 'meta-llama/llama-3.3-70b-instruct:free', 'type' => 'string', 'description' => 'Modèle pour le chatbot client'],
            ['group' => 'ai', 'key' => 'ai.content_model', 'value' => 'qwen/qwen3-coder:free', 'type' => 'string', 'description' => 'Modèle pour la génération de contenu'],
            ['group' => 'ai', 'key' => 'ai.moderation_model', 'value' => 'meta-llama/llama-3.3-70b-instruct:free', 'type' => 'string', 'description' => 'Modèle pour la modération'],
            ['group' => 'ai', 'key' => 'ai.seo_model', 'value' => 'meta-llama/llama-3.3-70b-instruct:free', 'type' => 'string', 'description' => 'Modèle pour les suggestions SEO'],
            ['group' => 'ai', 'key' => 'ai.temperature', 'value' => '0.7', 'type' => 'number', 'description' => 'Température par défaut (0-2)'],
            ['group' => 'ai', 'key' => 'ai.max_tokens', 'value' => '2048', 'type' => 'number', 'description' => 'Tokens max par réponse'],
            ['group' => 'ai', 'key' => 'ai.chatbot_enabled', 'value' => 'false', 'type' => 'boolean', 'description' => 'Activer le chatbot sur le site'],
            ['group' => 'ai', 'key' => 'ai.translation_model', 'value' => 'meta-llama/llama-3.3-70b-instruct:free', 'type' => 'string', 'description' => 'Modèle OpenRouter utilisé pour la traduction automatique'],
            ['group' => 'ai', 'key' => 'ai.chatbot_system_prompt', 'value' => 'Tu es un assistant utile et professionnel.', 'type' => 'string', 'description' => 'Prompt système du chatbot'],
        ];

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
