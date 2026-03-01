<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Traits;

trait HasMeta
{
    public function getMeta(string $key, $default = null)
    {
        $meta = $this->meta ?? [];

        return $meta[$key] ?? $default;
    }

    public function setMeta(string $key, $value): self
    {
        $meta = $this->meta ?? [];
        $meta[$key] = $value;
        $this->meta = $meta;

        return $this;
    }

    public function removeMeta(string $key): self
    {
        $meta = $this->meta ?? [];
        unset($meta[$key]);
        $this->meta = $meta;

        return $this;
    }

    protected function initializeHasMeta(): void
    {
        $this->mergeCasts(['meta' => 'array']);
    }
}
