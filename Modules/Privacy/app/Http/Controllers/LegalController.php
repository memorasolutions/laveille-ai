<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Privacy\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Modules\Privacy\Models\LegalPage;
use Modules\Privacy\Models\RightsRequest;

class LegalController extends Controller
{
    public function privacyPolicy(): View
    {
        return $this->renderLegalPage('privacy-policy', 'privacy::legal.privacy-policy');
    }

    public function termsOfUse(): View
    {
        return $this->renderLegalPage('terms-of-use', 'privacy::legal.terms-of-use');
    }

    public function cookiePolicy(): View
    {
        return $this->renderLegalPage('cookie-policy', 'privacy::legal.cookie-policy');
    }

    public function salesConditions(): View
    {
        return $this->renderLegalPage('conditions-de-vente', 'privacy::legal.sales-conditions');
    }

    private function renderLegalPage(string $slug, string $fallbackView): View
    {
        $page = LegalPage::findBySlug($slug);

        if ($page) {
            return view('privacy::legal.dynamic', [
                'page' => $page,
                'config' => config('privacy'),
            ]);
        }

        return view($fallbackView, [
            'config' => config('privacy'),
        ]);
    }

    public function rightsRequest(): View
    {
        return view('privacy::legal.rights-request', [
            'company' => config('privacy.company'),
            'response_delay_days' => config('privacy.rights.response_delay_days', 30),
            'request_types' => [
                'access' => __('Droit d\'accès'),
                'rectification' => __('Droit de rectification'),
                'erasure' => __('Droit à l\'effacement'),
                'portability' => __('Droit à la portabilité'),
                'opposition' => __('Droit d\'opposition'),
                'limitation' => __('Droit à la limitation'),
                'withdrawal' => __('Retrait du consentement'),
            ],
        ]);
    }

    public function rightsRequestStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'request_type' => 'required|in:access,rectification,erasure,portability,opposition,limitation,withdrawal',
            'description' => 'required|string|max:5000',
            'file' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('rights-requests', 'local');
        }

        $rightsRequest = RightsRequest::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'request_type' => $validated['request_type'],
            'description' => $validated['description'],
            'file_path' => $filePath,
            'jurisdiction' => session('privacy_jurisdiction', 'pipeda'),
            'deadline_at' => now()->addDays(config('privacy.rights.response_delay_days', 30)),
        ]);

        $dpoEmail = config('privacy.rights.notification_email');
        if ($dpoEmail) {
            try {
                Mail::raw(
                    "Nouvelle demande d'exercice de droits\n\n"
                    ."Reference : {$rightsRequest->reference}\n"
                    ."Type : {$rightsRequest->request_type}\n"
                    ."De : {$rightsRequest->name} ({$rightsRequest->email})\n"
                    ."Date limite : {$rightsRequest->deadline_at->format('Y-m-d')}\n\n"
                    ."Description :\n{$rightsRequest->description}",
                    function ($message) use ($dpoEmail, $rightsRequest) {
                        $message->to($dpoEmail)
                            ->subject("[Droits] {$rightsRequest->reference} - {$rightsRequest->request_type}");
                    }
                );
            } catch (\Exception $e) {
                Log::error('Rights request notification failed: '.$e->getMessage());
            }
        }

        return redirect()->route('legal.rights')->with(
            'success',
            __('Votre demande a été enregistrée. Référence :').$rightsRequest->reference
        );
    }
}
