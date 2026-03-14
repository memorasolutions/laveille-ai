<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Shared\Traits;

trait ParsesTags
{
    protected function parseTagsInput(string $input): array
    {
        $trimmed = trim($input);

        if ($trimmed === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $trimmed))));
    }
}
