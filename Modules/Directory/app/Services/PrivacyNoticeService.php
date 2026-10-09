<?php

declare(strict_types=1);

namespace Modules\Directory\Services;

use Illuminate\Support\Carbon;
use Modules\Directory\Models\Tool;
use Modules\Settings\Facades\Settings;
use Throwable;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * Module « précaution données personnelles » : note de minimisation adressée à l'UTILISATEUR
 * (jamais un jugement sur l'outil). Deux couches : (a) note générale par catégorie, pilotée par
 * config('directory.privacy_notice.categories'); (b) fait vérifié par outil (3 champs optionnels).
 * Interrupteur public RUNTIME (réglage directory.privacy_notice_enabled, OFF par défaut) :
 * activable sans redéploiement. Toute défaillance du réglage = module éteint, jamais une page cassée.
 */
class PrivacyNoticeService
{
    public const SETTING_KEY = 'directory.privacy_notice_enabled';

    public function enabled(): bool
    {
        try {
            return (bool) Settings::get(self::SETTING_KEY, (bool) config('directory.privacy_notice.default_enabled', false));
        } catch (Throwable) {
            return false;
        }
    }

    /** Variante de la note générale pour cet outil ('cv', 'documents'...) ou null. */
    public function variantFor(Tool $tool): ?string
    {
        $slugs = $tool->categories->map(function ($category): array {
            return array_values(array_filter(array_unique([
                $category->getTranslation('slug', app()->getLocale(), false),
                $category->getTranslation('slug', 'fr_CA', false),
                $category->getTranslation('slug', 'fr', false),
            ])));
        })->flatten()->all();

        foreach ((array) config('directory.privacy_notice.categories', []) as $variant => $configured) {
            if (array_intersect($slugs, (array) $configured) !== []) {
                return (string) $variant;
            }
        }

        return null;
    }

    public function generalNote(Tool $tool): ?string
    {
        $variant = $this->variantFor($tool);
        if ($variant === null || ! is_array($parts = trans("directory::privacy_notice.variants.{$variant}"))) {
            return null;
        }

        return trans('directory::privacy_notice.general', $parts);
    }

    /** Ligne « Selon sa politique... » seulement si la note ET la date sont remplies. */
    public function verifiedLine(Tool $tool): ?string
    {
        $note = trim((string) $tool->third_party_ai_note);
        if ($note === '' || $tool->privacy_checked_at === null) {
            return null;
        }

        return trans('directory::privacy_notice.verified', [
            'date' => $tool->privacy_checked_at->locale(app()->getLocale())->isoFormat('D MMMM YYYY'),
            'note' => rtrim($note, " \t.\u{00A0}"),
        ]);
    }

    public function policyUrl(Tool $tool): ?string
    {
        $url = trim((string) $tool->privacy_policy_url);

        return filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url) ? $url : null;
    }

    /** Données du partial, ou null quand rien à afficher (module éteint ou aucune couche remplie). */
    public function forTool(Tool $tool): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $general = $this->generalNote($tool);
        $verified = $this->verifiedLine($tool);

        if ($general === null && $verified === null) {
            return null;
        }

        return ['general' => $general, 'verified' => $verified, 'policyUrl' => $this->policyUrl($tool)];
    }

    /** Date d'échéance de re-vérification de la politique (radar), ou null si jamais vérifiée. */
    public function recheckDueAt(Tool $tool): ?Carbon
    {
        return $tool->privacy_checked_at?->copy()->addMonths((int) config('directory.privacy_notice.recheck_months', 6));
    }
}
