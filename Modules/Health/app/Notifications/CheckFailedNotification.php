<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Health\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Health\Checks\OpenRouterCreditCheck;
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
        'restant' => 'Crédit OpenRouter restant (US$)',
        'total' => 'Crédit OpenRouter acheté (US$)',
        'consomme' => 'Crédit OpenRouter consommé (US$)',
        'jours_estimes' => "Jours d'autonomie estimés au rythme actuel",
        'echecs_consecutifs' => 'Échecs consécutifs',
        'statut' => 'Code HTTP reçu',
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

            // La marche a suivre ne s'affiche que pour le controle qui va REELLEMENT mal, et se
            // choisit selon le TYPE d'echec : incident constate le 2026-08-01 21h11 Quebec, un
            // timeout cURL (mesure impossible, aucun pourcentage disponible) affichait quand
            // meme la procedure « augmentez la directive saturee », fausse pour ce cas - le
            // probleme n'etait pas une capacite pleine mais une surcharge PHP-FPM passagere.
            if (strtolower($result->check->getLabel()) === 'opcache' && ! $result->status->equals(Status::ok())) {
                $lignes = array_key_exists('keys_percent', $result->meta ?? [])
                    ? $this->marcheASuivreOpcache()
                    : $this->marcheASuivreMesureImpossible();

                foreach ($lignes as $ligne) {
                    $mail->line($ligne);
                }
            }

            if (strtolower($result->check->getLabel()) === 'schedule' && ! $result->status->equals(Status::ok())) {
                foreach ($this->marcheASuivreSchedule() as $ligne) {
                    $mail->line($ligne);
                }
            }

            // On compare la CLASSE, pas le libelle : Spatie derive le libelle en decoupant le
            // nom en mots (« OpenRouterCreditCheck » devient « Open Router Credit »), ce qui
            // rend toute comparaison de chaine silencieusement fausse. Les deux branches
            // ci-dessus n'y echappent que parce que « Opcache » et « Schedule » sont des mots
            // uniques. Mesure du 2026-08-23 : sans ce correctif, la marche a suivre
            // n'apparaissait dans AUCUN courriel, sans la moindre erreur.
            if ($result->check instanceof OpenRouterCreditCheck && ! $result->status->equals(Status::ok())) {
                // Deux situations opposees derriere le meme controle : soit on a MESURE un
                // solde qui descend (meta contient 'restant'), soit on n'a pas pu mesurer du
                // tout. Afficher « rechargez » sur un simple timeout serait la meme faute que
                // celle corrigee pour OPcache le 2026-08-01.
                $lignes = array_key_exists('restant', $result->meta ?? [])
                    ? $this->marcheASuivreOpenRouter()
                    : $this->marcheASuivreOpenRouterMesureImpossible();

                foreach ($lignes as $ligne) {
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

    /**
     * Marche a suivre quand OPcache n'a pas pu etre MESURE (timeout, HTTP non-2xx, JSON
     * incomplet) : distincte de marcheASuivreOpcache(), qui suppose une capacite saturee -
     * une hypothese fausse ici, puisqu'aucun pourcentage n'a pu etre calcule.
     *
     * @return array<int, string>
     */
    private function marcheASuivreMesureImpossible(): array
    {
        return [
            'Marche à suivre (accès WHM ou hébergeur requis) :',
            '1. Vérifier que https://laveille.ai répond normalement dans un navigateur.',
            "2. Si le site répond, il s'agit probablement d'une surcharge PONCTUELLE du serveur PHP-FPM partagé (plusieurs sites y exécutent des tâches chaque minute) : aucune action n'est requise si l'alerte ne se répète pas.",
            "3. Si l'alerte se répète, vérifier la charge du serveur (WHM > Server Status) au moment exact de l'alerte.",
            '4. En dernier recours seulement : redémarrer PHP-FPM via /scripts/restartsrv_apache_php_fpm --restart (touche TOUS les sites du serveur).',
        ];
    }

    /**
     * Marche a suivre pour un echec du controle Schedule (le battement de coeur du
     * planificateur Laravel n'a pas ete rafraichi a temps). Donnee de production (30 jours,
     * 43 631 passages, 2026-08-02) : 290 echecs, tous des blips de 1-2 minutes qui se
     * resolvent seuls des le passage suivant - la meme surcharge ponctuelle du pool PHP-FPM
     * partage par des dizaines de crons d'autres sites que celle deja identifiee pour OPcache.
     *
     * @return array<int, string>
     */
    private function marcheASuivreSchedule(): array
    {
        return [
            'Marche à suivre (accès WHM ou hébergeur requis) :',
            '1. Vérifier que https://laveille.ai répond normalement dans un navigateur.',
            "2. Si le site répond, il s'agit très probablement d'une surcharge PONCTUELLE du serveur PHP-FPM partagé (plusieurs sites y exécutent des tâches chaque minute) : le planificateur reprendra de lui-même dès le passage suivant, aucune action n'est requise si l'alerte ne se répète pas.",
            "3. Si l'alerte se répète PLUSIEURS FOIS DE SUITE (pas seulement isolée dans la journée), vérifier que la ligne de cron 'artisan schedule:run' existe toujours dans cPanel > Cron Jobs pour laveille.ai.",
            '4. En dernier recours seulement : vérifier la charge du serveur (WHM > Server Status) au moment exact de l\'alerte.',
        ];
    }

    /**
     * Marche a suivre quand le solde a bien ete MESURE et qu'il descend.
     *
     * Ce qui est en jeu : le credit OpenRouter finance l'enrichissement des fiches de
     * l'annuaire. Quand il s'epuise, l'API repond 402, la cascade echoue en silence et le job
     * se termine en SUCCES - la panne ne se voit nulle part. C'est exactement ce qui avait tue
     * l'enrichissement pendant neuf jours avant le 2026-08-23.
     *
     * @return array<int, string>
     */
    private function marcheASuivreOpenRouter(): array
    {
        return [
            'Marche à suivre :',
            '1. Ouvrir https://openrouter.ai/credits avec le compte MEMORA et vérifier le solde affiché.',
            '2. Recharger le compte si le montant restant est confirmé. Rien d\'autre n\'est à modifier : la clé et la cascade de modèles restent valides.',
            "3. Tant que le solde n'est pas rechargé, l'enrichissement de l'annuaire (tools:enrich-pending) s'arrête SANS erreur visible : les fiches restent incomplètes en silence.",
            "4. Pour ralentir la consommation en attendant, la cascade privilégie déjà les modèles gratuits ; le poste de dépense réel est le volume de fiches traitées, pas le choix des modèles.",
        ];
    }

    /**
     * Marche a suivre quand le solde n'a PAS pu etre mesure (reseau, cle refusee, JSON
     * inexploitable) : distincte de la precedente, qui suppose un solde bas - conseiller une
     * recharge sur un simple timeout serait faux et ferait perdre du temps.
     *
     * @return array<int, string>
     */
    private function marcheASuivreOpenRouterMesureImpossible(): array
    {
        return [
            'Marche à suivre (la mesure a échoué, le solde est peut-être intact) :',
            '1. Vérifier que https://openrouter.ai répond dans un navigateur : une indisponibilité de leur côté explique à elle seule cette alerte.',
            "2. Si un code HTTP 401 ou 403 est indiqué ci-dessus, la clé OPENROUTER_API_KEY du .env de production est refusée : la regénérer sur openrouter.ai puis la remplacer.",
            "3. Si l'alerte ne se répète pas au passage suivant, aucune action n'est requise : un échec isolé est déjà absorbé sans alerte, seule la répétition remonte.",
        ];
    }
}
