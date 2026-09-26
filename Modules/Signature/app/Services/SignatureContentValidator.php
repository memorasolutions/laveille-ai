<?php

declare(strict_types=1);

namespace Modules\Signature\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Signature\Models\Signature;

/**
 * Contrat de validation du JSON `signatures.content` (section 11.2 du plan : "structure décrite
 * par un contrat de validation, pas un schéma libre"). Réutilisé par tous les points d'écriture
 * (brouillon anonyme, mise à jour via lien secret, « Mes signatures ») - une seule source de vérité
 * pour ne jamais diverger entre deux formulaires.
 */
final class SignatureContentValidator
{
    public const FONT_FAMILIES = ['Arial', 'Helvetica', 'Verdana', 'Georgia', 'Tahoma'];

    public const SOCIAL_PLATFORMS = ['linkedin', 'facebook', 'instagram', 'x', 'youtube', 'website'];

    /**
     * LOT 3 (2026-09-25) - forme du portrait (n'affecte JAMAIS le logo, voir SignatureRenderer).
     * Clés ASCII volontairement sans accent (valeur stockée en base, patron déjà établi par les
     * clés de gabarit comme `banniere` - le libellé accentué vit côté vue, jamais dans la valeur).
     */
    public const PORTRAIT_SHAPES = ['carre', 'rond'];

    /** LOT 3 - facteur d'échelle appliqué aux tailles de police de base (voir SignatureRenderer). */
    public const FONT_SCALES = ['petite', 'moyenne', 'grande'];

    /** @return array<string, mixed> */
    public static function validated(array $data): array
    {
        return Validator::make($data, self::rules())->validate();
    }

    /** @return array<string, mixed> */
    public static function rules(): array
    {
        $safeUrl = function (string $attribute, mixed $value, \Closure $fail): void {
            if (! SignatureRenderer::isSafeUrl(is_string($value) ? $value : null)) {
                $fail('Seuls les liens http:// ou https:// sont acceptés.');
            }
        };

        return [
            'template' => ['required', 'string', Rule::in(Signature::templates())],
            'content' => ['required', 'array'],
            'content.first_name' => ['required', 'string', 'max:80'],
            'content.last_name' => ['required', 'string', 'max:80'],
            'content.job_title' => ['nullable', 'string', 'max:120'],
            'content.organization' => ['nullable', 'string', 'max:120'],
            'content.email' => ['required', 'string', 'email:rfc', 'max:190'],
            'content.phone' => ['nullable', 'string', 'max:40'],
            'content.mobile' => ['nullable', 'string', 'max:40'],
            'content.website' => ['nullable', 'string', 'max:255', $safeUrl],
            'content.address' => ['nullable', 'string', 'max:255'],
            'content.tagline' => ['nullable', 'string', 'max:160'],
            'content.cta_text' => ['nullable', 'string', 'max:60'],
            'content.cta_url' => ['nullable', 'string', 'max:255', $safeUrl],
            'content.accent_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'content.font_family' => ['nullable', 'string', Rule::in(self::FONT_FAMILIES)],
            'content.social_links' => ['nullable', 'array', 'max:6'],
            'content.social_links.*.platform' => ['required', 'string', Rule::in(self::SOCIAL_PLATFORMS)],
            'content.social_links.*.url' => ['required', 'string', 'max:255', $safeUrl],
            'content.mention_lines' => ['nullable', 'array', 'max:6'],
            'content.mention_lines.*' => ['string', 'max:160'],
            'content.qr_enabled' => ['nullable', 'boolean'],
            // Mention laveille.ai en commentaire HTML - activée par défaut, la case peut la retirer.
            'content.show_attribution' => ['nullable', 'boolean'],
            // LOT 3 (2026-09-25) - pronoms, forme du portrait, taille de police. Enum FERMÉ pour
            // les deux derniers (Rule::in), jamais une valeur libre.
            'content.pronouns' => ['nullable', 'string', 'max:30'],
            'content.portrait_shape' => ['nullable', 'string', Rule::in(self::PORTRAIT_SHAPES)],
            'content.font_scale' => ['nullable', 'string', Rule::in(self::FONT_SCALES)],
            // Rappel opt-in (section 6.7) - jamais requis, jamais transformé en compte.
            'reminder_email' => ['nullable', 'string', 'email:rfc', 'max:190'],
        ];
    }
}
