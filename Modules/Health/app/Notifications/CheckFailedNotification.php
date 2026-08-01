<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Health\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Spatie\Health\Enums\Status;

class CheckFailedNotification extends \Spatie\Health\Notifications\CheckFailedNotification
{
    private const LIBELLES = [
        'sapi' => 'Interface PHP mesurée',
        'keys_percent' => 'Table des clés occupée',
        'memory_percent' => 'Mémoire partagée occupée',
        'interned_percent' => 'Chaînes internées occupées',
        'refusals' => 'Scripts refusés depuis le démarrage',
        'previous_refusals' => 'Scripts refusés au passage précédent',
        'refusals_delta' => 'Nouveaux refus depuis le passage précédent',
        'cache_full' => 'Cache déclaré plein',
        'erreur' => 'Erreur rencontrée',
    ];

    public function toMail(): MailMessage
    {
        $site = (string) (config('app.name') ?: config('app.url'));
        $urgent = collect($this->results)->contains(fn ($result): bool => $result->status->equals(Status::failed()) || $result->status->equals(Status::crashed()));
        $gravity = $urgent ? 'URGENT' : 'AVERTISSEMENT';

        $mail = (new MailMessage)
            ->mailer('workspace')
            ->from(
                config('health.notifications.mail.from.address', config('mail.from.address')),
                config('health.notifications.mail.from.name', config('mail.from.name'))
            )
            ->subject("[{$gravity}] Santé de {$site}")
            ->line($urgent
                ? 'Un contrôle de santé du site a échoué et demande une intervention rapide.'
                : 'Un contrôle de santé du site approche d’une limite et doit être surveillé.')
            ->line($urgent
                ? 'Les visiteurs peuvent subir des ralentissements, des erreurs ou une indisponibilité si la situation persiste.'
                : 'Les visiteurs ne sont pas nécessairement touchés maintenant, mais les performances pourraient se dégrader sans intervention.')
            ->line('---')
            ->line('Détails techniques et marche à suivre :');

        foreach ($this->results as $result) {
            $mail->line($result->check->getLabel().' : '.$result->getNotificationMessage());
            $mail->line('Résumé : '.$result->getShortSummary());

            foreach ($this->mesuresLisibles($result->meta) as $ligne) {
                $mail->line($ligne);
            }

            // La marche a suivre ne s'affiche que pour le controle qui va REELLEMENT mal.
            // Sans ce garde, un courriel declenche par un autre controle affichait quand meme
            // « augmentez la directive saturee » sous un OPcache annonce « aucune action
            // requise » : une consigne contradictoire, donc une consigne qu'on apprend a ignorer.
            if (strtolower($result->check->getLabel()) === 'opcache' && ! $result->status->equals(Status::ok())) {
                foreach ($this->marcheASuivreOpcache() as $ligne) {
                    $mail->line($ligne);
                }
            }
        }

        return $mail;
    }

    /**
     * Transforme un tableau associatif de mesures techniques en lignes lisibles :
     * - les clés sont traduites via self::LIBELLES (ou conservées telles quelles si absentes),
     * - les pourcentages (_percent) sont arrondis a 1 decimale et suffixes par ' %',
     * - les entiers recoivent un separateur de milliers,
     * - les booleens deviennent 'oui'/'non'.
     *
     * On n'utilise plus json_encode : il rend les flottants avec toute leur precision
     * machine (29.39999999999999857891452847979962825775146484375 pour 29,4) et produit
     * un pave illisible dans un courriel destine a un humain. Constate le 2026-08-01 sur
     * la premiere alerte reellement recue.
     *
     * @param  array<string, mixed>  $meta
     * @return array<int, string>
     */
    private function mesuresLisibles(array $meta): array
    {
        $lignes = [];

        foreach ($meta as $cle => $valeur) {
            if (! is_scalar($valeur)) {
                continue;
            }

            $libelle = self::LIBELLES[$cle] ?? $cle;

            if (is_float($valeur) || is_int($valeur)) {
                if (str_ends_with((string) $cle, '_percent')) {
                    $valeur = number_format((float) $valeur, 1, ',', '').' %';
                } elseif (is_int($valeur)) {
                    $valeur = number_format($valeur, 0, '', "\u{202F}");
                } else {
                    $valeur = number_format($valeur, 1, ',', '');
                }
            } elseif (is_bool($valeur)) {
                $valeur = $valeur ? 'oui' : 'non';
            }

            $lignes[] = "{$libelle} : {$valeur}";
        }

        return $lignes;
    }

    /**
     * Marche a suivre concrete, propre a OPcache : sans elle, l'alerte dit qu'il y a un
     * probleme sans dire ou intervenir ni avec quelle commande.
     *
     * @return array<int, string>
     */
    private function marcheASuivreOpcache(): array
    {
        return [
            'Marche à suivre (accès root WHM requis) :',
            '1. Ouvrir /opt/cpanel/ea-php84/root/etc/php.d/11-opcache-memora.ini',
            '2. Sauvegarder le fichier AVANT toute modification : cp fichier fichier.backup-AAAAMMJJ-HHMMSS',
            '3. Augmenter la directive saturée : opcache.max_accelerated_files pour les clés, opcache.memory_consumption pour la mémoire, opcache.interned_strings_buffer pour les chaînes.',
            '4. Appliquer : /scripts/restartsrv_apache_php_fpm --restart (un simple reload ne redimensionne PAS la mémoire partagée).',
            '5. Attention : ce redémarrage touche TOUS les sites PHP du serveur, pas seulement celui-ci.',
        ];
    }
}
