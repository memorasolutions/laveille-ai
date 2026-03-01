<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class CrudService
{
    public function __construct(
        protected string $modelClass,
    ) {}

    public function query(): Builder
    {
        return $this->modelClass::query();
    }

    public function all(array $filters = [], array $with = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->query()->with($with);
        $this->applyFilters($query, $filters);

        return $query->get();
    }

    public function paginate(int $perPage = 15, array $filters = [], array $with = []): LengthAwarePaginator
    {
        $query = $this->query()->with($with);
        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function find(int|string $id, array $with = []): ?Model
    {
        return $this->query()->with($with)->find($id);
    }

    public function findOrFail(int|string $id, array $with = []): Model
    {
        return $this->query()->with($with)->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->modelClass::create($data);
    }

    public function update(int|string $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    public function delete(int|string $id): bool
    {
        return (bool) $this->findOrFail($id)->delete();
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();
        $this->applyFilters($query, $filters);

        return $query->count();
    }

    protected function applyFilters(Builder $query, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if ($value === null) {
                continue;
            }

            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
    }
}
