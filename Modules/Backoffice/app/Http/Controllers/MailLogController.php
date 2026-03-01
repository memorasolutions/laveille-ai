<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\View\View;
use Modules\Notifications\Models\SentEmail;

class MailLogController
{
    public function index(): View
    {
        $emails = SentEmail::latest('sent_at')->paginate(25);

        return view('backoffice::mail-log.index', [
            'title' => 'Journal des emails',
            'subtitle' => 'Communications',
            'emails' => $emails,
        ]);
    }
}
