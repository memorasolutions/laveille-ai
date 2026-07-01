<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Academy\Services;

use App\Models\User;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\UserBadge;

/**
 * Génère des assertions OpenBadge 3.0 / Verifiable Credentials (1EdTech).
 *
 * Pseudonymat (Loi 25 QC / RGPD) : l'email de l'apprenant n'apparaît JAMAIS
 * dans l'assertion publique. Il est remplacé par un HMAC-SHA256 irréversible.
 *
 * Activable via config('academy.open_badges_enabled') ou ACADEMY_OPEN_BADGES_ENABLED=true.
 *
 * // ACTION: ajout OpenBadge 3.0 D09 — service d'assertion JSON-LD
 * // SELF: nouveau fichier < 5 lignes correctives après génération MCP
 * // RAISON: conformité 1EdTech OB 3.0, pseudonymat Loi 25, dette D09
 */
final class OpenBadgeService
{
    /**
     * Génère une assertion OpenBadge 3.0 pour un certificat de cours délivré.
     *
     * @param  CertificateIssued  $cert  Certificat (relations user + course chargées)
     * @return array<string, mixed>  Tableau PHP conforme OB 3.0 / VC Data Model
     *
     * @throws \RuntimeException Si l'apprenant est introuvable (cas théorique)
     */
    public function assertionForCertificate(CertificateIssued $cert): array
    {
        if (! $cert->user instanceof User) {
            throw new \RuntimeException('Learner not found');
        }

        $learnerHash = hash_hmac('sha256', $cert->user->email, config('app.key', ''));
        $issuerUrl   = rtrim((string) config('app.url'), '/');
        $issuerName  = (string) config('app.name');
        $contactEmail = (string) config('app.superadmin_email', config('mail.from.address', ''));

        $course = $cert->course;
        $courseUrl = url('/academie/courses/' . $course->slug);

        $issuedAt = $cert->issued_at
            ? $cert->issued_at->toIso8601String()
            : now()->toIso8601String();

        return [
            '@context' => [
                'https://www.w3.org/2018/credentials/v1',
                'https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.3.json',
            ],
            'id'   => route('academy.badges.assertion', ['serial' => $cert->serial]),
            'type' => ['VerifiableCredential', 'OpenBadgeCredential'],
            'issuer' => [
                'id'    => $issuerUrl . '#issuer',
                'type'  => ['Profile'],
                'name'  => $issuerName,
                'url'   => $issuerUrl,
                'email' => $contactEmail,
            ],
            'issuanceDate'      => $issuedAt,
            'credentialSubject' => [
                'id'   => 'urn:memora:learner:' . $learnerHash,
                'type' => ['AchievementSubject'],
                'achievement' => [
                    'id'          => $courseUrl,
                    'type'        => ['Achievement'],
                    'name'        => $course->title,
                    'description' => "Certificat de complétion du cours « {$course->title} » délivré par {$issuerName}",
                    'criteria'    => [
                        'type'      => 'Criteria',
                        'narrative' => "L'apprenant a complété l'intégralité du cours avec un score de {$cert->final_score} %.",
                    ],
                ],
            ],
        ];
    }

    /**
     * Génère une assertion OpenBadge 3.0 pour un badge utilisateur.
     *
     * @param  UserBadge  $ub  Badge gagné (relations user + badge chargées)
     * @return array<string, mixed>
     *
     * @throws \RuntimeException Si l'apprenant est introuvable
     */
    public function assertionForUserBadge(UserBadge $ub): array
    {
        if (! $ub->user instanceof User) {
            throw new \RuntimeException('Learner not found');
        }

        $learnerHash  = hash_hmac('sha256', $ub->user->email, config('app.key', ''));
        $issuerUrl    = rtrim((string) config('app.url'), '/');
        $issuerName   = (string) config('app.name');
        $contactEmail = (string) config('app.superadmin_email', config('mail.from.address', ''));

        $badge              = $ub->badge;
        $badgeDefinitionUrl = url('/academie/badges/definition/' . $badge->key);

        $awardedAt = $ub->awarded_at
            ? $ub->awarded_at->toIso8601String()
            : now()->toIso8601String();

        return [
            '@context' => [
                'https://www.w3.org/2018/credentials/v1',
                'https://purl.imsglobal.org/spec/ob/v3p0/context-3.0.3.json',
            ],
            'id'   => route('academy.badges.user-badge-assertion', ['id' => $ub->id]),
            'type' => ['VerifiableCredential', 'OpenBadgeCredential'],
            'issuer' => [
                'id'    => $issuerUrl . '#issuer',
                'type'  => ['Profile'],
                'name'  => $issuerName,
                'url'   => $issuerUrl,
                'email' => $contactEmail,
            ],
            'issuanceDate'      => $awardedAt,
            'credentialSubject' => [
                'id'   => 'urn:memora:learner:' . $learnerHash,
                'type' => ['AchievementSubject'],
                'achievement' => [
                    'id'          => $badgeDefinitionUrl,
                    'type'        => ['Achievement'],
                    'name'        => $badge->name,
                    'description' => $badge->description ?? '',
                    'criteria'    => [
                        'type'      => 'Criteria',
                        'narrative' => "Badge décerné pour : {$badge->name}",
                    ],
                ],
            ],
        ];
    }

    /**
     * Vérifie si la fonctionnalité Open Badges est activée.
     */
    public function isEnabled(): bool
    {
        return (bool) config('academy.open_badges_enabled', false);
    }
}
