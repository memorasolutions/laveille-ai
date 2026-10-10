<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\Core\Support;

/**
 * Détection d'adresses courriel jetables (fournisseurs d'adresses temporaires).
 *
 * Bloc réutilisable, exportable vers un autre projet Laravel : aucune dépendance, aucune
 * configuration requise. Pensé comme `Honeypot` - une source de vérité unique qu'un formulaire
 * appelle au lieu de réécrire une liste à la main.
 *
 * Volontairement CONSERVATEUR : un vrai visiteur utilise très rarement une adresse jetable pour
 * une demande sincère; et même dans ce cas, le formulaire met en QUARANTAINE (consultable), il ne
 * détruit jamais le message. On vise donc la précision (zéro faux positif sur un domaine courant),
 * pas l'exhaustivité (la liste des fournisseurs jetables est mouvante et sans fin).
 *
 * La liste peut être étendue sans toucher ce fichier via DISPOSABLE_EMAIL_DOMAINS (domaines
 * supplémentaires séparés par des virgules) - utile pour bloquer un nouveau fournisseur repéré
 * sans redéploiement de code.
 */
final class DisposableEmail
{
    /**
     * Domaines jetables connus. Le domaine réellement observé en production (notboxletters.com,
     * spam du 2026-10-09) ouvre la liste; le reste sont les fournisseurs les plus répandus.
     *
     * @var array<int, string>
     */
    public const KNOWN_DOMAINS = [
        'notboxletters.com',
        'mailinator.com', 'guerrillamail.com', 'guerrillamail.net', 'sharklasers.com',
        '10minutemail.com', '10minutemail.net', 'temp-mail.org', 'tempmail.com', 'tempmailo.com',
        'yopmail.com', 'yopmail.fr', 'getnada.com', 'nada.email', 'dispostable.com',
        'maildrop.cc', 'mailnesia.com', 'trashmail.com', 'trashmail.net', 'throwawaymail.com',
        'fakeinbox.com', 'mohmal.com', 'emailondeck.com', 'mailcatch.com', 'spam4.me',
        'moakt.com', 'tempr.email', 'luxusmail.org', 'inboxbear.com', 'tempmail.plus',
    ];

    /**
     * Vrai si le courriel appartient à un fournisseur d'adresses jetables connu.
     *
     * Compare le domaine exact ET ses sous-domaines (un fournisseur sert souvent *.son-domaine).
     * Un courriel vide, malformé ou sans domaine renvoie false (ce n'est pas le rôle de ce bloc
     * de valider la syntaxe - la validation `email` du formulaire s'en charge déjà).
     */
    public static function isDisposable(?string $email): bool
    {
        if ($email === null || ! str_contains($email, '@')) {
            return false;
        }

        $domain = mb_strtolower(trim(substr(strrchr($email, '@'), 1)));
        if ($domain === '') {
            return false;
        }

        foreach (self::domains() as $known) {
            if ($domain === $known || str_ends_with($domain, '.'.$known)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Liste effective : les domaines connus, plus ceux ajoutés par l'environnement.
     *
     * @return array<int, string>
     */
    public static function domains(): array
    {
        $extra = array_filter(array_map(
            static fn (string $d): string => mb_strtolower(trim($d)),
            explode(',', (string) env('DISPOSABLE_EMAIL_DOMAINS', ''))
        ));

        return array_values(array_unique(array_merge(self::KNOWN_DOMAINS, $extra)));
    }
}
