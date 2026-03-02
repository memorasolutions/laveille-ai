<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Notifications\Models\EmailTemplate;

class MarketingTemplateController extends Controller
{
    public function index()
    {
        $templates = EmailTemplate::where('module', 'newsletter')
            ->orderBy('name')
            ->paginate(15);

        return view('newsletter::admin.templates.index', compact('templates'));
    }

    public function create()
    {
        return view('newsletter::admin.templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:email_templates,slug',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'category' => 'nullable|string|max:100',
        ]);

        EmailTemplate::create([
            ...$validated,
            'variables' => ['subscriber.name', 'subscriber.email', 'unsubscribe_url'],
            'module' => 'newsletter',
            'is_active' => true,
        ]);

        return redirect()->route('admin.newsletter.templates.index')
            ->with('success', 'Template marketing créé avec succès.');
    }

    public function edit(EmailTemplate $template)
    {
        return view('newsletter::admin.templates.edit', compact('template'));
    }

    public function update(Request $request, EmailTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $template->update([
            ...$validated,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.newsletter.templates.index')
            ->with('success', 'Template mis à jour avec succès.');
    }

    public function destroy(EmailTemplate $template)
    {
        $template->delete();

        return redirect()->route('admin.newsletter.templates.index')
            ->with('success', 'Template supprimé.');
    }

    public function preview(EmailTemplate $template)
    {
        $html = str_replace(
            ['{{subscriber.name}}', '{{subscriber.email}}', '{{unsubscribe_url}}'],
            ['Jean Dupont', 'jean@exemple.com', '#'],
            $template->body_html
        );

        return response($html);
    }
}
