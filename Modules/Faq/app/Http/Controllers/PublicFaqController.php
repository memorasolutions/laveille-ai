<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Faq\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Faq\Models\Faq;

class PublicFaqController extends Controller
{
    public function show(): View
    {
        $allFaqs = Faq::published()->ordered()->get();
        $faqs = $allFaqs->groupBy('category');
        $categories = $faqs->keys();

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [],
        ];

        foreach ($allFaqs as $faq) {
            $jsonLd['mainEntity'][] = [
                '@type' => 'Question',
                'name' => $faq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => strip_tags($faq->answer),
                ],
            ];
        }

        $jsonLdString = json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return view('faq::public.show', compact('faqs', 'categories', 'jsonLdString'));
    }
}
