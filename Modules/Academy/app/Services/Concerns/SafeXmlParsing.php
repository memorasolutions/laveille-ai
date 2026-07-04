<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Parsing XML SÛR (anti-XXE) PARTAGÉ : chargement via LIBXML_NONET SEUL, SANS
 * LIBXML_DTDLOAD ni LIBXML_NOENT - aucune entité externe (fichier local, URL
 * réseau) n'est jamais résolue, quel que soit le contenu du XML (manifeste
 * SCORM, sauvegarde Moodle .mbz). DRY : extrait de ScormPackageService::parseManifest
 * (comportement IDENTIQUE) au moment d'ajouter l'import Moodle, qui a le même besoin.
 */

declare(strict_types=1);

namespace Modules\Academy\Services\Concerns;

trait SafeXmlParsing
{
    /**
     * Parse un XML de façon SÛRE. Retourne null (jamais de warning PHP, jamais
     * d'exception) si le contenu est vide ou n'est pas un XML valide - à
     * l'appelant de lever son propre message métier contextualisé.
     */
    private function parseXmlSafely(string $xml): ?\SimpleXMLElement
    {
        if (trim($xml) === '') {
            return null;
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $doc = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET);

            return $doc === false ? null : $doc;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
