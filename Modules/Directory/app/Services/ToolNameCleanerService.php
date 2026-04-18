<?php

declare(strict_types=1);

namespace Modules\Directory\Services;

final class ToolNameCleanerService
{
    private const HN_PREFIX_PATTERN = '/^Show\s*HN\s*[:\-–—]?\s*/iu';

    private const SEPARATORS = [' – ', ' — ', ' : ', ' | ', '–', '—'];

    public static function clean(string $rawName): string
    {
        $trimmedOriginal = trim($rawName);
        $hadHnPrefix = self::isHnTitle($trimmedOriginal);

        $cleanedName = preg_replace(self::HN_PREFIX_PATTERN, '', $trimmedOriginal);
        $cleanedName = trim($cleanedName);

        if ($hadHnPrefix) {
            foreach (self::SEPARATORS as $sep) {
                if (mb_strpos($cleanedName, $sep) !== false) {
                    $cleanedName = trim(explode($sep, $cleanedName, 2)[0]);
                    break;
                }
            }
        }

        if ($cleanedName === '') {
            return $trimmedOriginal;
        }

        return $cleanedName;
    }

    public static function isHnTitle(string $rawName): bool
    {
        return (bool) preg_match(self::HN_PREFIX_PATTERN, $rawName);
    }
}
