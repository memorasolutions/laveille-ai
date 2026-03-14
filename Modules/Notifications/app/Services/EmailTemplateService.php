<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Notifications\Services;

use Illuminate\Support\Arr;
use Modules\Notifications\Models\EmailTemplate;

class EmailTemplateService
{
    public function render(string $slug, array $data): ?array
    {
        $template = EmailTemplate::findBySlug($slug);

        return $template ? $this->renderTemplate($template, $data) : null;
    }

    public function renderTemplate(EmailTemplate $template, array $data): array
    {
        $flattened = Arr::dot($data);

        $subject = $template->subject;
        $bodyHtml = $template->body_html;

        foreach ($flattened as $key => $value) {
            $placeholder = '{{'.$key.'}}';
            $subject = str_replace($placeholder, (string) $value, $subject);
            $bodyHtml = str_replace($placeholder, (string) $value, $bodyHtml);
        }

        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
        ];
    }

    public function getDefaultVariables(): array
    {
        return [
            'user.name' => 'Nom de l\'utilisateur',
            'user.email' => 'Adresse email',
            'app.name' => 'Nom de l\'application',
            'app.url' => 'URL de l\'application',
            'reset_link' => 'Lien de réinitialisation',
            'verification_link' => 'Lien de vérification',
            'changed_at' => 'Date de modification',
            'alert_message' => 'Message d\'alerte',
            'token' => 'Code OTP',
            'expire_minutes' => 'Minutes avant expiration',
        ];
    }
}
