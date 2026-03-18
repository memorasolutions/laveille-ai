<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notifications\Models\EmailTemplate;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (! class_exists(EmailTemplate::class)) {
            return;
        }

        $btn = 'display:inline-block;padding:12px 24px;background:#487FFF;color:#fff;text-decoration:none;border-radius:6px;font-weight:bold;';

        foreach (self::templates($btn) as $t) {
            EmailTemplate::updateOrCreate(['slug' => $t['slug']], $t);
        }
    }

    /** @return list<array<string, mixed>> */
    public static function templates(string $btn = ''): array
    {
        return [
            ['slug' => 'saas_payment_succeeded', 'name' => 'Paiement confirmé', 'subject' => 'Confirmation de votre paiement', 'module' => 'saas', 'variables' => ['user.name', 'invoice.id', 'amount', 'app.name'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><p style="color:#333;">Nous avons bien reçu votre paiement de <strong>{{amount}}</strong> pour la facture #{{invoice.id}}.</p><p style="color:#333;">Merci de votre confiance.</p><p style="text-align:center;margin:24px 0;"><a href="{{app.url}}/user/subscription" style="'.$btn.'">Voir la facture</a></p>'],
            ['slug' => 'saas_payment_failed', 'name' => 'Échec de paiement', 'subject' => 'Action requise : échec du paiement', 'module' => 'saas', 'variables' => ['user.name', 'invoice.id', 'app.name'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><p style="color:#333;">Le paiement pour la facture #{{invoice.id}} a échoué. Veuillez mettre à jour vos informations de paiement.</p><p style="text-align:center;margin:24px 0;"><a href="{{app.url}}/user/subscription" style="'.$btn.'">Mettre à jour</a></p>'],
            ['slug' => 'saas_subscription_cancelled', 'name' => 'Abonnement annulé', 'subject' => 'Confirmation d\'annulation', 'module' => 'saas', 'variables' => ['user.name', 'subscription.ends_at', 'app.name'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><p style="color:#333;">Votre abonnement a bien été annulé. Vous continuerez à avoir accès jusqu\'au <strong>{{subscription.ends_at}}</strong>.</p><p style="color:#666;">Nous espérons vous revoir bientôt.</p>'],
            ['slug' => 'saas_trial_ending', 'name' => 'Fin période d\'essai', 'subject' => 'Votre période d\'essai se termine bientôt', 'module' => 'saas', 'variables' => ['user.name', 'trial.ends_at', 'app.name'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><p style="color:#333;">Votre période d\'essai prend fin le <strong>{{trial.ends_at}}</strong>. Passez à la version complète pour ne rien perdre.</p><p style="text-align:center;margin:24px 0;"><a href="{{app.url}}/pricing" style="'.$btn.'">Choisir un plan</a></p>'],
            ['slug' => 'ai_chat_lead', 'name' => 'Nouveau prospect chatbot', 'subject' => 'Nouveau lead détecté par l\'IA', 'module' => 'ai', 'variables' => ['user.name', 'lead.email', 'lead.message', 'app.name'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><p style="color:#333;">Le chatbot a capturé un nouveau prospect : <strong>{{lead.email}}</strong></p><p style="color:#666;font-style:italic;">«{{lead.message}}»</p>'],
            ['slug' => 'ai_human_takeover', 'name' => 'Prise en charge humaine', 'subject' => 'Demande d\'intervention humaine', 'module' => 'ai', 'variables' => ['user.name', 'conversation.id', 'app.name'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><p style="color:#333;">La conversation #{{conversation.id}} nécessite une intervention humaine.</p><p style="text-align:center;margin:24px 0;"><a href="{{app.url}}/admin/ai/conversations/{{conversation.id}}" style="'.$btn.'">Rejoindre le chat</a></p>'],
            ['slug' => 'ai_ticket_assigned', 'name' => 'Ticket assigné', 'subject' => 'Un ticket vous a été assigné', 'module' => 'ai', 'variables' => ['user.name', 'ticket.subject', 'ticket.id', 'app.name'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><p style="color:#333;">Le ticket #{{ticket.id}} : <strong>{{ticket.subject}}</strong> vous a été assigné.</p><p style="text-align:center;margin:24px 0;"><a href="{{app.url}}/admin/ai/tickets/{{ticket.id}}" style="'.$btn.'">Voir le ticket</a></p>'],
            ['slug' => 'ai_ticket_sla_warning', 'name' => 'Alerte SLA ticket', 'subject' => 'Attention : SLA bientôt dépassé', 'module' => 'ai', 'variables' => ['user.name', 'ticket.subject', 'ticket.id', 'app.name'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><p style="color:#d00;">Le ticket #{{ticket.id}} ({{ticket.subject}}) approche de sa limite SLA.</p><p style="text-align:center;margin:24px 0;"><a href="{{app.url}}/admin/ai/tickets/{{ticket.id}}" style="'.$btn.'">Traiter maintenant</a></p>'],
            ['slug' => 'booking_status', 'name' => 'Mise à jour rendez-vous', 'subject' => 'Mise à jour de votre rendez-vous', 'module' => 'booking', 'variables' => ['user.name', 'booking.service_name', 'booking.date', 'booking.status', 'app.name'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><p style="color:#333;">Votre rendez-vous pour <strong>{{booking.service_name}}</strong> le {{booking.date}} est maintenant : <strong>{{booking.status}}</strong>.</p>'],
            ['slug' => 'booking_new_admin', 'name' => 'Nouveau rendez-vous admin', 'subject' => 'Nouvelle réservation reçue', 'module' => 'booking', 'variables' => ['user.name', 'booking.service_name', 'booking.customer_name', 'booking.date', 'app.name'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><p style="color:#333;">Nouvelle réservation de <strong>{{booking.customer_name}}</strong> pour {{booking.service_name}} le {{booking.date}}.</p>'],
            ['slug' => 'newsletter_campaign', 'name' => 'Campagne newsletter', 'subject' => 'Actualités de {{app.name}}', 'module' => 'newsletter', 'variables' => ['user.name', 'app.name', 'content'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><div>{{content}}</div>'],
            ['slug' => 'newsletter_digest', 'name' => 'Résumé hebdomadaire', 'subject' => 'Votre résumé de la semaine', 'module' => 'newsletter', 'variables' => ['user.name', 'app.name'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><p style="color:#333;">Voici les points forts de la semaine sur {{app.name}}.</p>'],
            ['slug' => 'newsletter_welcome', 'name' => 'Bienvenue newsletter', 'subject' => 'Bienvenue dans notre newsletter', 'module' => 'newsletter', 'variables' => ['user.name', 'app.name'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><p style="color:#333;">Merci de vous être inscrit à notre newsletter. Vous recevrez nos dernières nouvelles et offres exclusives.</p>'],
            ['slug' => 'auth_account_locked', 'name' => 'Compte verrouillé', 'subject' => 'Alerte de sécurité : compte verrouillé', 'module' => 'auth', 'variables' => ['user.name', 'app.name'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><p style="color:#d00;">Suite à plusieurs tentatives de connexion échouées, votre compte a été temporairement verrouillé.</p><p style="text-align:center;margin:24px 0;"><a href="{{app.url}}/forgot-password" style="'.$btn.'">Réinitialiser mon mot de passe</a></p>'],
            ['slug' => 'roadmap_idea_status', 'name' => 'Mise à jour idée', 'subject' => 'Du nouveau sur votre suggestion', 'module' => 'roadmap', 'variables' => ['user.name', 'idea.title', 'idea.status', 'app.name'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><p style="color:#333;">L\'idée «<strong>{{idea.title}}</strong>» a changé de statut : <strong>{{idea.status}}</strong>.</p>'],
            ['slug' => 'team_invite_member', 'name' => 'Invitation équipe', 'subject' => 'Invitation à rejoindre {{team.name}}', 'module' => 'team', 'variables' => ['user.name', 'team.name', 'invite.url', 'app.name'], 'body_html' => '<p style="font-size:16px;color:#333;">Bonjour {{user.name}},</p><p style="color:#333;">Vous êtes invité à rejoindre l\'équipe <strong>{{team.name}}</strong>.</p><p style="text-align:center;margin:24px 0;"><a href="{{invite.url}}" style="'.$btn.'">Accepter l\'invitation</a></p>'],
        ];
    }
}
