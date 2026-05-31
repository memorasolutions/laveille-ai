<?php

declare(strict_types=1);

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FindReplaceTextCommand extends Command
{
    protected $signature = 'core:find-text {needle} {--replace=} {--apply} {--table=}';

    protected $description = 'Recherche (et remplace en option) une chaîne de texte dans toutes les colonnes texte de la base. Sûr : backup JSON avant tout remplacement, jamais de TRUNCATE/DELETE.';

    public function handle(): int
    {
        $needle = (string) $this->argument('needle');
        $replace = $this->option('replace');
        $apply = (bool) $this->option('apply');
        $tableName = $this->option('table');

        if ($replace !== null && ! $apply) {
            $this->line('DRY-RUN : aucune modification ne sera appliquée (ajouter --apply pour exécuter).');
        }

        $excludedTables = [
            'telescope_%', 'jobs', 'job_batches', 'failed_jobs', 'sessions',
            'cache', 'cache_locks', 'migrations', 'password_reset_tokens', 'personal_access_tokens',
        ];

        $tables = $this->getTables($tableName ? (string) $tableName : null, $excludedTables);
        $totalFound = 0;
        $totalReplaced = 0;
        $backups = [];

        foreach ($tables as $table) {
            try {
                $columns = $this->getTextColumns($table);
                if ($columns === []) {
                    continue;
                }
                $pk = $this->getPrimaryKey($table);

                foreach ($columns as $column) {
                    $res = $this->processColumn($table, $column, $pk, $needle, $replace, $apply);
                    $totalFound += $res['found'];
                    $totalReplaced += $res['replaced'];
                    $backups = array_merge($backups, $res['backups']);
                }
            } catch (\Throwable $e) {
                $this->error("Table {$table} : " . $e->getMessage());
            }
        }

        if ($apply && $backups !== []) {
            $dir = storage_path('app/text-replace-backups');
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $file = $dir . '/backup-' . date('Ymd-His') . '.json';
            file_put_contents($file, json_encode($backups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info("Backup : {$file} (" . count($backups) . ' ligne(s))');
        }

        $this->info("Occurrences trouvées : {$totalFound}");
        if ($replace !== null) {
            $this->info(($apply ? 'Lignes modifiées : ' : 'Lignes qui seraient modifiées : ') . $totalReplaced);
        }

        return self::SUCCESS;
    }

    /** @param array<int,string> $excludedPatterns @return array<int,string> */
    protected function getTables(?string $tableName, array $excludedPatterns): array
    {
        $query = DB::table('information_schema.tables')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_type', 'BASE TABLE');

        if ($tableName !== null) {
            $query->where('table_name', $tableName);
        } else {
            foreach ($excludedPatterns as $pattern) {
                str_ends_with($pattern, '%')
                    ? $query->where('table_name', 'not like', $pattern)
                    : $query->where('table_name', '!=', $pattern);
            }
        }

        return array_map('strval', $query->pluck('table_name')->all());
    }

    /** @return array<int,string> */
    protected function getTextColumns(string $table): array
    {
        return array_map('strval', DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->whereIn('data_type', ['varchar', 'char', 'text', 'mediumtext', 'longtext', 'json'])
            ->pluck('column_name')->all());
    }

    protected function getPrimaryKey(string $table): string
    {
        $pk = DB::table('information_schema.key_column_usage')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', 'PRIMARY')
            ->value('column_name');

        return $pk ? (string) $pk : 'id';
    }

    /** @return array{found:int,replaced:int,backups:array<int,array<string,mixed>>} */
    protected function processColumn(string $table, string $column, string $pk, string $needle, ?string $replace, bool $apply): array
    {
        $found = 0;
        $replaced = 0;
        $backups = [];

        // échappement manuel des wildcards LIKE (Laravel n'a pas DB::escapeLike)
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $needle) . '%';

        $rows = DB::table($table)->select($pk, $column)->where($column, 'like', $like)->get();

        foreach ($rows as $row) {
            $value = (string) ($row->{$column} ?? '');
            $pkValue = $row->{$pk};
            $count = substr_count($value, $needle);
            if ($count === 0) {
                continue;
            }
            $found += $count;
            $this->line("{$table}.{$column} | {$pk}={$pkValue} | " . $this->excerpt($value, $needle));

            if ($replace !== null) {
                $newValue = str_replace($needle, (string) $replace, $value);
                if ($apply) {
                    $backups[] = ['table' => $table, 'pk' => $pk, 'pk_value' => $pkValue, 'column' => $column, 'old_value' => $value];
                    DB::table($table)->where($pk, $pkValue)->update([$column => $newValue]);
                    $replaced += $count;
                } else {
                    $this->line('  -> deviendrait : ' . $this->excerpt($newValue, (string) $replace));
                }
            }
        }

        return ['found' => $found, 'replaced' => $replaced, 'backups' => $backups];
    }

    protected function excerpt(string $text, string $needle): string
    {
        $pos = mb_stripos($text, $needle);
        if ($pos === false) {
            return Str::limit($text, 80);
        }
        $start = max(0, $pos - 40);
        $len = mb_strlen($needle) + 80;
        $excerpt = mb_substr($text, $start, $len);

        return preg_replace('/\s+/', ' ', ($start > 0 ? '…' : '') . $excerpt . '…') ?? $excerpt;
    }
}
