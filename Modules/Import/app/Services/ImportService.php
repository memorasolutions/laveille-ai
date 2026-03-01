<?php

declare(strict_types=1);

namespace Modules\Import\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Blog\Models\Article;
use Modules\Pages\Models\StaticPage;
use OpenSpout\Reader\CSV\Options as CSVOptions;
use OpenSpout\Reader\CSV\Reader as CSVReader;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;

class ImportService
{
    public const MODEL_TYPES = [
        'article' => 'Articles',
        'page' => 'Pages',
        'user' => 'Utilisateurs',
    ];

    public const FIELD_MAPS = [
        'article' => ['title', 'content', 'excerpt', 'category', 'featured_image'],
        'page' => ['title', 'content', 'excerpt', 'meta_title', 'meta_description', 'template'],
        'user' => ['name', 'email', 'password'],
    ];

    /**
     * @return array{headers: array<int, mixed>, rows: array<int, array<int, mixed>>, total_previewed: int}
     */
    public function preview(string $filePath, string $format = 'csv', int $rows = 5): array
    {
        $reader = $this->createReader($format);
        $reader->open($filePath);

        $previewRows = [];
        $headers = [];
        $rowCount = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rowArray = $row->toArray();

                if ($rowCount === 0) {
                    $headers = $rowArray;
                } else {
                    $previewRows[] = $rowArray;
                }

                $rowCount++;

                if ($rowCount > $rows) {
                    break 2;
                }
            }
        }

        $reader->close();

        return [
            'headers' => $headers,
            'rows' => $previewRows,
            'total_previewed' => count($previewRows),
        ];
    }

    /**
     * @param  array<int, string>  $columnMapping  [file_column_index => model_field_name]
     */
    public function import(
        string $filePath,
        string $format,
        string $modelType,
        array $columnMapping,
        ?callable $validator = null
    ): ImportResult {
        $result = new ImportResult();

        if (! file_exists($filePath)) {
            return $result;
        }

        $reader = $this->createReader($format);
        $reader->open($filePath);

        $modelClass = $this->resolveModelClass($modelType);
        $lineNumber = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $lineNumber++;

                if ($lineNumber === 1) {
                    continue;
                }

                $result->total++;

                try {
                    $rowData = $row->toArray();
                    $mappedData = $this->mapRowData($rowData, $columnMapping);

                    $mappedData = $this->applyModelTransformations($mappedData, $modelType);

                    if ($validator && ! $validator($mappedData)) {
                        throw new \Exception('Validation échouée');
                    }

                    $modelClass::create($mappedData);
                    $result->imported++;
                } catch (\Exception $e) {
                    $result->skipped++;
                    $result->errors[$lineNumber] = $e->getMessage();
                }
            }
        }

        $reader->close();

        return $result;
    }

    private function createReader(string $format): CSVReader|XLSXReader
    {
        return match (strtolower($format)) {
            'csv' => $this->createCsvReader(),
            'xlsx' => new XLSXReader(),
            default => throw new \InvalidArgumentException("Format non supporté : {$format}"),
        };
    }

    private function createCsvReader(): CSVReader
    {
        return new CSVReader(new CSVOptions(
            FIELD_DELIMITER: ',',
            FIELD_ENCLOSURE: '"',
        ));
    }

    private function resolveModelClass(string $modelType): string
    {
        return match (strtolower($modelType)) {
            'article' => Article::class,
            'page' => StaticPage::class,
            'user' => User::class,
            default => throw new \InvalidArgumentException("Type de modèle inconnu : {$modelType}"),
        };
    }

    /**
     * @param  array<int, mixed>  $rowData
     * @param  array<int, string>  $columnMapping
     * @return array<string, mixed>
     */
    private function mapRowData(array $rowData, array $columnMapping): array
    {
        $mapped = [];

        foreach ($columnMapping as $fileIndex => $modelField) {
            if ($modelField !== '' && isset($rowData[$fileIndex])) {
                $mapped[$modelField] = $rowData[$fileIndex];
            }
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function applyModelTransformations(array $data, string $modelType): array
    {
        return match (strtolower($modelType)) {
            'article', 'page' => array_merge($data, [
                'user_id' => Auth::id(),
                'status' => 'draft',
            ]),
            'user' => array_merge($data, [
                'password' => Hash::make($data['password'] ?? 'changeme'),
            ]),
            default => $data,
        };
    }
}
