<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\SaaS\Models\Plan;

class InlineEditController
{
    private array $allowedEntities = [
        'users' => ['model' => User::class, 'fields' => ['name', 'email', 'is_active']],
        'plans' => ['model' => Plan::class, 'fields' => ['name', 'is_active', 'price']],
    ];

    public function update(Request $request, string $entity, int $id): JsonResponse
    {
        if (! isset($this->allowedEntities[$entity])) {
            abort(404);
        }

        $field = $request->input('field');
        $value = $request->input('value');

        if (! in_array($field, $this->allowedEntities[$entity]['fields'], true)) {
            abort(422, 'Champ non autorisé');
        }

        $modelClass = $this->allowedEntities[$entity]['model'];
        $model = $modelClass::findOrFail($id);

        if ($field === 'is_active') {
            $value = (bool) $value;
        }

        $model->update([$field => $value]);

        return response()->json([
            'success' => true,
            'id' => $id,
            'field' => $field,
            'value' => $model->$field,
        ]);
    }
}
