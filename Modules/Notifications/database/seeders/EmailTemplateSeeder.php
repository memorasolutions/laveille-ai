<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notifications\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    public static function defaults(): array
    {
        return [
            [
                'name' => 'Bienvenue',
                'slug' => 'welcome',
                'subject' => 'Bienvenue sur {{app.name}}',
                'module' => 'auth',
                'variables' => ['user.name', 'user.email', 'app.name', 'app.url'],
                'body_html' => '<p style="font-family:Arial,sans-serif;color:#333;">Bonjour {{user.name}},</p><p style="font-family:Arial,sans-serif;color:#333;">Bienvenue sur <strong>{{app.name}}</strong>. Votre compte a été créé avec succès.</p><p style="font-family:Arial,sans-serif;color:#333;">Accédez à votre tableau de bord : <a href="{{app.url}}" style="color:#487FFF;">{{app.url}}</a></p>',
            ],
            [
                'name' => 'Réinitialisation mot de passe',
                'slug' => 'password_reset',
                'subject' => 'Réinitialisation de votre mot de passe',
                'module' => 'auth',
                'variables' => ['user.name', 'reset_link', 'app.name', 'expire_minutes'],
                'body_html' => '<p style="font-family:Arial,sans-serif;color:#333;">Bonjour {{user.name}},</p><p style="font-family:Arial,sans-serif;color:#333;">Vous avez demandé la réinitialisation de votre mot de passe sur {{app.name}}.</p><p style="text-align:center;"><a href="{{reset_link}}" style="background:#487FFF;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;display:inline-block;">Réinitialiser mon mot de passe</a></p><p style="font-family:Arial,sans-serif;color:#666;font-size:13px;">Ce lien expire dans {{expire_minutes}} minutes.</p>',
            ],
            [
                'name' => 'Vérification email',
                'slug' => 'email_verification',
                'subject' => 'Vérifiez votre adresse email',
                'module' => 'auth',
                'variables' => ['user.name', 'verification_link', 'app.name'],
                'body_html' => '<p style="font-family:Arial,sans-serif;color:#333;">Bonjour {{user.name}},</p><p style="font-family:Arial,sans-serif;color:#333;">Veuillez confirmer votre adresse email pour {{app.name}}.</p><p style="text-align:center;"><a href="{{verification_link}}" style="background:#487FFF;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;display:inline-block;">Vérifier mon email</a></p>',
            ],
            [
                'name' => 'Mot de passe modifié',
                'slug' => 'password_changed',
                'subject' => 'Votre mot de passe a été modifié',
                'module' => 'notifications',
                'variables' => ['user.name', 'app.name', 'app.url', 'changed_at'],
                'body_html' => '<p style="font-family:Arial,sans-serif;color:#333;">Bonjour {{user.name}},</p><p style="font-family:Arial,sans-serif;color:#333;">Votre mot de passe sur <strong>{{app.name}}</strong> a été modifié le {{changed_at}}.</p><p style="font-family:Arial,sans-serif;color:#d00;"><strong>Si vous n\'êtes pas à l\'origine de cette modification, contactez immédiatement le support.</strong></p>',
            ],
            [
                'name' => 'Alerte système',
                'slug' => 'system_alert',
                'subject' => '{{app.name}} - Alerte système',
                'module' => 'notifications',
                'variables' => ['user.name', 'alert_message', 'app.name', 'app.url'],
                'body_html' => '<p style="font-family:Arial,sans-serif;color:#333;">Bonjour {{user.name}},</p><div style="background:#fff3cd;border-left:4px solid #ffc107;padding:12px;margin:16px 0;font-family:Arial,sans-serif;">{{alert_message}}</div><p style="font-family:Arial,sans-serif;color:#333;"><a href="{{app.url}}" style="color:#487FFF;">Accéder au tableau de bord</a></p>',
            ],
            [
                'name' => 'Lien magique',
                'slug' => 'magic_link',
                'subject' => 'Votre code de connexion',
                'module' => 'auth',
                'variables' => ['user.name', 'token', 'expire_minutes', 'app.name'],
                'body_html' => '<p style="font-family:Arial,sans-serif;color:#333;">Bonjour {{user.name}},</p><p style="font-family:Arial,sans-serif;color:#333;">Votre code de connexion pour {{app.name}} :</p><p style="text-align:center;font-size:32px;font-weight:bold;letter-spacing:8px;color:#487FFF;font-family:monospace;">{{token}}</p><p style="font-family:Arial,sans-serif;color:#666;font-size:13px;">Ce code expire dans {{expire_minutes}} minutes.</p>',
            ],
        ];
    }

    public function run(): void
    {
        foreach (self::defaults() as $template) {
            EmailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}
