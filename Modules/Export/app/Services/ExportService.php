<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Export\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

class ExportService
{
    public function toCsv(iterable $data, string $filename, array $headers = []): string
    {
        $path = storage_path("app/exports/{$filename}");
        $this->ensureDirectory($path);

        $writer = new CsvWriter;
        $writer->openToFile($path);

        if (! empty($headers)) {
            $writer->addRow(Row::fromValues($headers));
        }

        foreach ($data as $row) {
            $writer->addRow(Row::fromValues(is_array($row) ? $row : $row->toArray()));
        }

        $writer->close();

        return $path;
    }

    public function toExcel(iterable $data, string $filename, array $headers = []): string
    {
        $path = storage_path("app/exports/{$filename}");
        $this->ensureDirectory($path);

        $writer = new XlsxWriter;
        $writer->openToFile($path);

        if (! empty($headers)) {
            $writer->addRow(Row::fromValues($headers));
        }

        foreach ($data as $row) {
            $writer->addRow(Row::fromValues(is_array($row) ? $row : $row->toArray()));
        }

        $writer->close();

        return $path;
    }

    public function toPdf(string $view, array $data, string $filename): string
    {
        $path = storage_path("app/exports/{$filename}");
        $this->ensureDirectory($path);

        $pdf = Pdf::loadView($view, $data);
        $pdf->save($path);

        return $path;
    }

    protected function ensureDirectory(string $path): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
