<?php

declare(strict_types=1);

namespace Modules\Decido\Services;

use Illuminate\Support\Str;
use Modules\Decido\Models\Poll;
use RuntimeException;

class PollExportService
{
    public function exportCsv(Poll $poll): string
    {
        $handle = fopen('php://memory', 'r+');

        // Round 22 (skill /100) : le 4e argument de fputcsv() ('\\', backslash) active le
        // mécanisme d'échappement PROPRIÉTAIRE de PHP (non-RFC4180) - il fait échapper le
        // caractère SUIVANT le backslash au lieu de doubler les guillemets internes. Un
        // voter_pseudonym texte libre (contrôlé par un votant anonyme) se terminant par un
        // backslash juste avant la fermeture du champ (ex. "Jean\") fait alors échapper le
        // guillemet fermant LUI-MÊME au lieu de clore le champ : le parseur continue de
        // consommer la ligne suivante comme faisant partie du même champ, fusionnant deux
        // lignes en une, décalant les colonnes et faisant disparaître des votants du fichier
        // exporté. Preuve réelle (isolée hors framework, fputcsv/fgetcsv) : "Jean\" produisait
        // une ligne de 4 colonnes contenant la ligne suivante au lieu de 2 lignes de 3
        // colonnes. Passer une chaîne vide comme 5e argument désactive ce mécanisme et revient
        // au pur doublage de guillemets RFC4180 ("" pour un guillemet interne), qui gère
        // correctement TOUS les cas testés : backslash seul/terminal, backslash+guillemet,
        // virgule+guillemets, point-virgule (le vrai délimiteur ici), saut de ligne interne.
        fputcsv($handle, ['Option', 'Votant', 'Réponse'], ';', '"', '');

        foreach ($poll->options as $option) {
            foreach ($option->votes as $vote) {
                $response = match ($vote->value) {
                    'yes' => 'Oui',
                    'maybe' => 'Peut-être',
                    'no' => 'Non',
                    'selected' => 'Sélectionné',
                    default => $vote->value,
                };

                fputcsv($handle, [
                    $this->sanitizeCsvCell($option->label),
                    $this->sanitizeCsvCell($vote->voter_pseudonym),
                    $response,
                ], ';', '"', '');
            }
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Neutralise l'injection de formule CSV (OWASP CSV Injection) : un votant anonyme
     * contrôle voter_pseudonym, texte libre injecté tel quel dans les cellules avant ce
     * fix - une valeur commençant par =/+/-/@ (ou tabulation/retour chariot) est interprétée
     * comme une formule active par Excel/Google Sheets à l'ouverture par l'organisateur.
     * Trouvé par une passe adversariale indépendante (skill /100, round 5).
     */
    private function sanitizeCsvCell(?string $value): string
    {
        $value = $value ?? '';

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }

    public function exportIcs(Poll $poll): string
    {
        if ($poll->status->value !== 'closed' || $poll->final_option_id === null) {
            throw new RuntimeException("L'export ICS n'est disponible qu'après clôture avec un créneau final choisi.");
        }

        $finalOption = $poll->finalOption;

        if (! $finalOption || ! $finalOption->starts_at || ! $finalOption->ends_at) {
            throw new RuntimeException("L'option finale doit avoir des dates de début et de fin définies.");
        }

        $uid = Str::uuid()->toString().'@decido.laveille.ai';
        $dtstamp = now()->utc()->format('Ymd\THis\Z');
        // starts_at/ends_at sont stockés en UTC brut, mais config('app.timezone') = America/Toronto
        // fait que le cast Eloquent datetime réinterprète à tort la valeur comme déjà en heure de
        // Québec sans conversion : reparser explicitement la valeur brute comme UTC est requis
        // (même cause racine que le fix results.blade.php v1.107.0).
        $dtstart = \Carbon\Carbon::parse($finalOption->starts_at->format('Y-m-d H:i:s'), 'UTC')->format('Ymd\THis\Z');
        $dtend = \Carbon\Carbon::parse($finalOption->ends_at->format('Y-m-d H:i:s'), 'UTC')->format('Ymd\THis\Z');
        $summary = $this->escapeIcsText($poll->title);
        $description = $this->escapeIcsText($poll->description ?? '');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Decido//laveille.ai//FR',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$dtstamp}",
            "DTSTART:{$dtstart}",
            "DTEND:{$dtend}",
            "SUMMARY:{$summary}",
            "DESCRIPTION:{$description}",
            'STATUS:CONFIRMED',
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        // Round 9 (skill /100) : aucun pliage de ligne n'existait - un titre long/unicode produit
        // une ligne SUMMARY dépassant largement la limite RFC 5545 §3.1 (75 octets), risquant une
        // troncature par des lecteurs de calendrier stricts (ex. Outlook/Exchange).
        $ics = implode("\r\n", array_map($this->foldIcsLine(...), $lines))."\r\n";

        return $ics;
    }

    /**
     * Plie une ligne de contenu ICS conformément à RFC 5545 §3.1 : une ligne physique ne doit
     * pas dépasser 75 octets (hors saut de ligne) ; le pliage insère un CRLF suivi d'une espace
     * unique, et ne doit jamais couper au milieu d'une séquence UTF-8 multi-octets.
     */
    private function foldIcsLine(string $line): string
    {
        $totalBytes = strlen($line);

        if ($totalBytes <= 75) {
            return $line;
        }

        $folded = [];
        $offset = 0;
        $limit = 75;

        while ($offset < $totalBytes) {
            $end = min($offset + $limit, $totalBytes);

            while ($end < $totalBytes && $end > $offset && (ord($line[$end]) & 0xC0) === 0x80) {
                $end--;
            }

            $folded[] = substr($line, $offset, $end - $offset);
            $offset = $end;
            $limit = 74; // les lignes de continuation commencent par une espace (1 octet du budget de 75).
        }

        return implode("\r\n ", $folded);
    }

    private function escapeIcsText(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace("\r\n", '\\n', $text);
        $text = str_replace("\n", '\\n', $text);
        $text = str_replace("\r", '\\n', $text);

        return $text;
    }
}
