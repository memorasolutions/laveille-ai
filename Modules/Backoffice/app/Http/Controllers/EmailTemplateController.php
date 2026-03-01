<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notifications\Database\Seeders\EmailTemplateSeeder;
use Modules\Notifications\Models\EmailTemplate;
use Modules\Notifications\Services\EmailTemplateService;

class EmailTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::orderBy('name')->get();

        return view('backoffice::email-templates.index', compact('templates'));
    }

    public function edit(EmailTemplate $emailTemplate)
    {
        $variables = app(EmailTemplateService::class)->getDefaultVariables();

        return view('backoffice::email-templates.edit', compact('emailTemplate', 'variables'));
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $emailTemplate->update([
            'subject' => $validated['subject'],
            'body_html' => $validated['body_html'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->back()->with('success', 'Template mis à jour avec succès');
    }

    public function preview(EmailTemplate $emailTemplate)
    {
        $service = app(EmailTemplateService::class);

        $dummyData = [
            'user' => ['name' => 'Jean Dupont', 'email' => 'jean@exemple.com'],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'reset_link' => config('app.url').'/reset-password/example-token',
            'verification_link' => config('app.url').'/verify-email/example-token',
            'changed_at' => now()->format('Y-m-d H:i'),
            'alert_message' => 'Ceci est un message d\'alerte de test.',
            'token' => '123456',
            'expire_minutes' => '15',
        ];

        $rendered = $service->renderTemplate($emailTemplate, $dummyData);

        return response($rendered['body_html']);
    }

    public function resetToDefault(EmailTemplate $emailTemplate)
    {
        $defaults = collect(EmailTemplateSeeder::defaults())
            ->firstWhere('slug', $emailTemplate->slug);

        if ($defaults) {
            $emailTemplate->update($defaults);

            return redirect()->back()->with('success', 'Template restauré aux valeurs par défaut');
        }

        return redirect()->back()->with('error', 'Aucun défaut trouvé pour ce template');
    }
}
