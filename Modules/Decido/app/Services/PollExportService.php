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

        fputcsv($handle, ['Option', 'Votant', 'Réponse'], ';', '"', '\\');

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
                    $option->label,
                    $vote->voter_pseudonym,
                    $response,
                ], ';', '"', '\\');
            }
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
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
        $dtstart = $finalOption->starts_at->utc()->format('Ymd\THis\Z');
        $dtend = $finalOption->ends_at->utc()->format('Ymd\THis\Z');
        $summary = $this->escapeIcsText($poll->title);
        $description = $this->escapeIcsText($poll->description ?? '');

        $ics = implode("\r\n", [
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
            '',
        ]);

        return $ics;
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
