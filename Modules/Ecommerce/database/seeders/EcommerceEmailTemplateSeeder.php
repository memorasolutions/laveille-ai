<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Notifications\Models\EmailTemplate;

class EcommerceEmailTemplateSeeder extends Seeder
{
    public static function defaults(): array
    {
        return [
            [
                'name' => 'Confirmation de commande',
                'slug' => 'ecommerce_order_confirmation',
                'subject' => 'Confirmation de commande #{{order.number}}',
                'module' => 'ecommerce',
                'variables' => ['user.name', 'user.email', 'app.name', 'app.url', 'order.number', 'order.total', 'order.date', 'order.url', 'currency'],
                'body_html' => '<p style="font-size:16px;color:#333;margin:0 0 16px;">Bonjour {{user.name}},</p><p style="font-size:16px;color:#333;margin:0 0 24px;">Votre commande <strong>#{{order.number}}</strong> a bien ete confirmee.</p><table width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border-collapse:collapse;"><tr><td style="padding:10px 0;border-bottom:1px solid #eee;color:#666;">Numero de commande</td><td style="padding:10px 0;border-bottom:1px solid #eee;font-weight:bold;text-align:right;">{{order.number}}</td></tr><tr><td style="padding:10px 0;border-bottom:1px solid #eee;color:#666;">Date</td><td style="padding:10px 0;border-bottom:1px solid #eee;font-weight:bold;text-align:right;">{{order.date}}</td></tr><tr><td style="padding:10px 0;color:#666;">Total</td><td style="padding:10px 0;font-weight:bold;text-align:right;font-size:18px;">{{order.total}} {{currency}}</td></tr></table><p style="text-align:center;margin:24px 0;"><a href="{{order.url}}" style="background:#487FFF;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:bold;">Voir ma commande</a></p><p style="font-size:14px;color:#666;margin:24px 0 0;">Merci de votre confiance!</p>',
            ],
            [
                'name' => 'Commande expediee',
                'slug' => 'ecommerce_order_shipped',
                'subject' => 'Votre commande #{{order.number}} a ete expediee',
                'module' => 'ecommerce',
                'variables' => ['user.name', 'user.email', 'app.name', 'app.url', 'order.number', 'order.tracking', 'order.url'],
                'body_html' => '<p style="font-size:16px;color:#333;margin:0 0 16px;">Bonjour {{user.name}},</p><p style="font-size:16px;color:#333;margin:0 0 16px;">Bonne nouvelle! Votre commande <strong>#{{order.number}}</strong> a ete expediee.</p><p style="font-size:16px;color:#333;margin:0 0 24px;">Numero de suivi : <strong>{{order.tracking}}</strong></p><p style="text-align:center;margin:24px 0;"><a href="{{order.url}}" style="background:#487FFF;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:bold;">Suivre ma commande</a></p><p style="font-size:14px;color:#666;margin:24px 0 0;">Merci de votre confiance!</p>',
            ],
            [
                'name' => 'Remboursement commande',
                'slug' => 'ecommerce_order_refunded',
                'subject' => 'Remboursement commande #{{order.number}}',
                'module' => 'ecommerce',
                'variables' => ['user.name', 'user.email', 'app.name', 'app.url', 'order.number', 'order.url', 'refund.amount', 'currency'],
                'body_html' => '<p style="font-size:16px;color:#333;margin:0 0 16px;">Bonjour {{user.name}},</p><p style="font-size:16px;color:#333;margin:0 0 16px;">Nous vous confirmons que votre remboursement pour la commande <strong>#{{order.number}}</strong> a ete traite.</p><p style="font-size:18px;color:#333;margin:0 0 16px;text-align:center;font-weight:bold;">{{refund.amount}} {{currency}}</p><p style="font-size:14px;color:#666;margin:0 0 24px;">Le montant sera credite sur votre compte dans un delai de 5 a 10 jours ouvrables.</p><p style="text-align:center;margin:24px 0;"><a href="{{order.url}}" style="background:#487FFF;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:bold;">Voir ma commande</a></p><p style="font-size:14px;color:#666;margin:24px 0 0;">Merci de votre patience.</p>',
            ],
            [
                'name' => 'Panier abandonne',
                'slug' => 'ecommerce_abandoned_cart',
                'subject' => 'Vous avez oublie quelque chose!',
                'module' => 'ecommerce',
                'variables' => ['user.name', 'user.email', 'app.name', 'app.url', 'cart.items', 'cart.item_count', 'cart.total', 'cart.url', 'currency'],
                'body_html' => '<p style="font-size:16px;color:#333;margin:0 0 16px;">Bonjour {{user.name}},</p><p style="font-size:16px;color:#333;margin:0 0 16px;">Vous avez laisse des articles dans votre panier!</p><p style="font-size:16px;color:#333;margin:0 0 8px;">Vos articles : <strong>{{cart.items}}</strong></p><p style="font-size:16px;color:#333;margin:0 0 24px;">Total : <strong>{{cart.total}} {{currency}}</strong> ({{cart.item_count}} articles)</p><p style="text-align:center;margin:24px 0;"><a href="{{cart.url}}" style="background:#28a745;color:#fff;padding:12px 24px;border-radius:6px;text-decoration:none;display:inline-block;font-weight:bold;">Reprendre mes achats</a></p><p style="font-size:14px;color:#666;margin:24px 0 0;">Nous vous attendons!</p>',
            ],
        ];
    }

    public function run(): void
    {
        if (! class_exists(EmailTemplate::class)) {
            return;
        }

        foreach (self::defaults() as $template) {
            EmailTemplate::updateOrCreate(
                ['slug' => $template['slug']],
                $template
            );
        }
    }
}
