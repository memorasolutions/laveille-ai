<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class ContactMessageController extends Controller
{
    public function index(): View
    {
        // ACTION: Délégation de la pagination et des filtres au composant Livewire ContactMessagesTable
        // MCP: SELF (< 5 lignes)
        // RAISON: Filtres, compteurs et suppression sont gérés par ContactMessagesTable (scroll infini).
        return view('backoffice::themes.backend.contact-messages.index');
    }

    public function show(ContactMessage $contactMessage): View
    {
        $contactMessage->markAsRead();

        return view('backoffice::themes.backend.contact-messages.show', compact('contactMessage'));
    }

    public function destroy(ContactMessage $contactMessage): RedirectResponse
    {
        $contactMessage->delete();

        return redirect()->route('admin.contact-messages.index')
            ->with('success', 'Message supprimé.');
    }

    /**
     * Réhabilite un message marqué spam (faux positif) : status 'spam' -> 'new'.
     * On vide la raison et on le ramène dans la boîte légitime.
     */
    public function markLegit(ContactMessage $contactMessage): RedirectResponse
    {
        if ($contactMessage->isSpam()) {
            $contactMessage->update(['status' => 'new', 'spam_reason' => null]);
        }

        return redirect()->route('admin.contact-messages.index', ['status' => 'spam'])
            ->with('success', 'Message marqué comme légitime.');
    }
}
