<?php

declare(strict_types=1);

namespace Modules\Directory\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Support\Honeypot;
use Modules\Directory\Models\TakedownRequest;
use Modules\Directory\Models\Tool;

class TakedownController extends Controller
{
    public function create(Request $request, ?string $slug = null)
    {
        $tool = null;
        if ($slug) {
            $tool = Tool::published()
                ->where('slug->'.app()->getLocale(), $slug)
                ->first();
        }

        // Pré-remplissage générique (ex. Journal personnel) : ?url=... — validé comme URL
        // avant affichage, jamais injecté tel quel dans le formulaire.
        $prefillUrl = $request->query('url');
        if ($prefillUrl && ! filter_var($prefillUrl, FILTER_VALIDATE_URL)) {
            $prefillUrl = null;
        }

        return view('directory::public.takedown', ['tool' => $tool, 'prefillUrl' => $prefillUrl]);
    }

    public function store(Request $request)
    {
        // Honeypot anti-bot : champ « website » invisible, rempli seulement par les robots.
        if (Honeypot::isBot($request)) {
            return redirect()->route('directory.takedown.create')
                ->with('success', 'Votre demande a été transmise.');
        }

        $validated = $request->validate([
            'directory_tool_id' => ['nullable', 'integer'],
            'target_url' => ['required', 'url', 'max:2048'],
            'requester_name' => ['required', 'string', 'max:255'],
            'requester_email' => ['required', 'email', 'max:255'],
            'requester_organization' => ['nullable', 'string', 'max:255'],
            'requester_role' => ['required', 'in:titulaire,mandataire,avocat,autre'],
            'right_type' => ['required', 'in:droit_auteur,marque,vie_privee,autre'],
            'right_details' => ['required', 'string', 'max:5000'],
            'description' => ['required', 'string', 'max:5000'],
            'declaration_accepted' => ['accepted'],
        ]);

        $takedown = TakedownRequest::create($validated + [
            'status' => 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        try {
            Mail::to(config('app.superadmin_email'))
                ->send(new \Modules\Directory\Mail\ToolTakedownRequestMail($takedown));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('directory.takedown.create')
            ->with('success', 'Votre demande de retrait a été reçue. Vous recevrez une réponse par courriel sous quelques jours ouvrables.');
    }

    public function policy()
    {
        return view('directory::public.takedown-policy');
    }
}
