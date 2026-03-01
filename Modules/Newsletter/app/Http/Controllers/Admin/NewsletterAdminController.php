<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\Newsletter\Models\Subscriber;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterAdminController extends Controller
{
    public function index(): View
    {
        return view('newsletter::admin.index');
    }

    public function destroy(Subscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return back()->with('success', 'Abonné supprimé avec succès.');
    }

    public function export(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="newsletter_'.now()->format('Y-m-d').'.csv"',
        ];

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['email', 'name', 'status', 'confirmed_at', 'created_at']);

            Subscriber::chunk(500, function ($subscribers) use ($handle) {
                foreach ($subscribers as $subscriber) {
                    if ($subscriber->isActive()) {
                        $status = 'actif';
                    } elseif ($subscriber->unsubscribed_at) {
                        $status = 'désabonné';
                    } else {
                        $status = 'en attente';
                    }

                    fputcsv($handle, [
                        $subscriber->email,
                        $subscriber->name ?? '',
                        $status,
                        $subscriber->confirmed_at?->format('Y-m-d H:i:s') ?? '',
                        $subscriber->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        }, 'newsletter_'.now()->format('Y-m-d').'.csv', $headers);
    }
}
