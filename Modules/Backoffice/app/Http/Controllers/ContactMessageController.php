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
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Settings\Facades\Settings;

class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $query = ContactMessage::query()->latest();

        // Filtre de statut. Valeurs : 'new', 'read', 'spam' ou vide.
        // Par défaut (vide) on affiche la boîte légitime (new + read) et JAMAIS le spam :
        // le spam n'apparait que via l'onglet dédié « Spam ».
        $status = (string) $request->input('status', '');
        if ($status === 'spam') {
            $query->where('status', 'spam');
        } elseif (in_array($status, ['new', 'read'], true)) {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['new', 'read']);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $messages = $query->paginate((int) Settings::get('backoffice.contact_messages_per_page', 20));
        $unreadCount = ContactMessage::unread()->count();
        $spamCount = ContactMessage::spam()->count();

        return view('backoffice::themes.backend.contact-messages.index', compact('messages', 'unreadCount', 'spamCount'));
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
