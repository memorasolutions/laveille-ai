<?php

declare(strict_types=1);

namespace Modules\Tools\Services;

final class CrosswordCsvService
{
    private const HEADER_FR = ['Indice', 'Mot'];

    private const SEPARATOR = ';';

    public function generateCsv(array $pairs): string
    {
        $fp = fopen('php://temp', 'r+');
        fputs($fp, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
        fputcsv($fp, self::HEADER_FR, self::SEPARATOR);
        foreach ($pairs as $pair) {
            $clue = isset($pair['clue']) ? trim((string) $pair['clue']) : '';
            $answer = isset($pair['answer']) ? trim((string) $pair['answer']) : '';
            if ($clue !== '' && $answer !== '') {
                fputcsv($fp, [$clue, $answer], self::SEPARATOR);
            }
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv;
    }

    public function generateTemplate(): string
    {
        $sample = [
            ['clue' => 'Capitale du Quebec', 'answer' => 'QUEBEC'],
            ['clue' => 'Framework PHP majeur', 'answer' => 'LARAVEL'],
            ['clue' => 'Langue de programmation web', 'answer' => 'PHP'],
            ['clue' => 'Norme accessibilite web', 'answer' => 'WCAG'],
            ['clue' => 'Format de donnees structurees', 'answer' => 'JSON'],
        ];

        return $this->generateCsv($sample);
    }

    public function parseCsv(string $content): array
    {
        // Retirer BOM si present
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        // Detecter separateur a partir de la PREMIERE LIGNE uniquement (l'en-tete).
        // Bug fix 2026-05-04 : un CSV avec indices contenant des virgules legitimes
        // ("Capitale de la France, ville de la Tour Eiffel...") faisait basculer le
        // parser sur ',' alors que le vrai separateur est ';' indique par l'en-tete.
        $sep = self::SEPARATOR;
        $firstLine = strtok($content, "\r\n") ?: $content;
        if (str_contains($firstLine, ';')) {
            $sep = ';';
        } elseif (str_contains($firstLine, "\t")) {
            $sep = "\t";
        } elseif (str_contains($firstLine, ',')) {
            $sep = ',';
        }

        $pairs = [];
        $lines = preg_split("/\r\n|\n|\r/", trim($content));
        $isFirst = true;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $row = str_getcsv($line, $sep);
            if (count($row) < 2) {
                continue;
            }
            // Skip ligne d'en-tete
            if ($isFirst) {
                $isFirst = false;
                $first = strtolower(trim($row[0]));
                if (in_array($first, ['indice', 'clue', 'definition', 'définition', 'question'], true)) {
                    continue;
                }
            }
            $clue = trim($row[0]);
            $answer = trim($row[1]);
            if ($clue === '' || $answer === '') {
                continue;
            }
            $pairs[] = [
                'clue' => mb_substr($clue, 0, 250),
                'answer' => mb_substr($answer, 0, 30),
            ];
            if (count($pairs) >= 50) {
                break;
            }
        }

        return $pairs;
    }
}
