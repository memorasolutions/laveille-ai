<?php

namespace App\Helpers;

use DOMDocument;
use Illuminate\Support\Str;

class AeoHelper
{
    /**
     * Traite le contenu HTML pour AEO : IDs sur headings, sections wrappées, itemprop sur premier paragraphe.
     */
    public static function chunkContent(string $html): string
    {
        if (empty(trim($html))) {
            return '';
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $body = $dom->getElementsByTagName('div')->item(0);
        if (! $body) {
            return $html;
        }

        // Collecter les noeuds en sections (entre headings)
        $sections = [];
        $currentNodes = [];

        foreach ($body->childNodes as $node) {
            $isHeading = $node->nodeType === XML_ELEMENT_NODE
                && in_array($node->tagName, ['h2', 'h3']);

            if ($isHeading && ! empty($currentNodes)) {
                $sections[] = $currentNodes;
                $currentNodes = [];
            }

            $currentNodes[] = $node;
        }

        if (! empty($currentNodes)) {
            $sections[] = $currentNodes;
        }

        // Reconstruire avec les sections wrappées
        $output = '';

        foreach ($sections as $nodes) {
            $hasHeading = false;

            foreach ($nodes as $node) {
                if ($node->nodeType === XML_ELEMENT_NODE && in_array($node->tagName, ['h2', 'h3'])) {
                    $hasHeading = true;
                    // Ajouter ID si absent
                    if (! $node->getAttribute('id')) {
                        $node->setAttribute('id', Str::slug($node->textContent));
                    }
                }
            }

            if ($hasHeading) {
                // Ajouter itemprop="text" sur le premier <p>
                $firstPDone = false;
                foreach ($nodes as $node) {
                    if (! $firstPDone && $node->nodeType === XML_ELEMENT_NODE && $node->tagName === 'p') {
                        $node->setAttribute('itemprop', 'text');
                        $firstPDone = true;
                    }
                }

                $section = $dom->createElement('section');
                $section->setAttribute('class', 'aeo-section');

                foreach ($nodes as $node) {
                    $section->appendChild($node->cloneNode(true));
                }

                $output .= $dom->saveHTML($section);
            } else {
                // Contenu avant le premier heading — garder tel quel
                foreach ($nodes as $node) {
                    $output .= $dom->saveHTML($node);
                }
            }
        }

        return $output;
    }

    /**
     * Génère le JSON-LD FAQPage depuis un array de Q/A.
     */
    public static function generateFaqSchema(array $faqs): string
    {
        $items = [];

        foreach ($faqs as $faq) {
            if (! empty($faq['question']) && ! empty($faq['answer'])) {
                $items[] = [
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $faq['answer'],
                    ],
                ];
            }
        }

        if (empty($items)) {
            return '';
        }

        return json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $items,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Extrait les H2/H3 pour générer une table des matières.
     */
    public static function generateToc(string $html): array
    {
        if (empty(trim($html))) {
            return [];
        }

        $toc = [];

        // Regex simple pour extraire les headings (plus fiable que DOMDocument pour le tri)
        preg_match_all('/<(h[23])[^>]*(?:id=["\']([^"\']*)["\'])?[^>]*>(.*?)<\/\1>/si', $html, $matches, PREG_SET_ORDER);

        $usedIds = [];

        foreach ($matches as $match) {
            $level = (int) substr($match[1], 1);
            $text = trim(strip_tags($match[3]));
            $id = ! empty($match[2]) ? $match[2] : Str::slug($text);

            // Éviter les doublons d'ID
            $originalId = $id;
            $counter = 1;
            while (in_array($id, $usedIds)) {
                $id = $originalId . '-' . $counter++;
            }
            $usedIds[] = $id;

            $toc[] = [
                'id' => $id,
                'text' => $text,
                'level' => $level,
            ];
        }

        return $toc;
    }
}
