<?php

declare(strict_types=1);

namespace Modules\Api\Http\Requests;

use Modules\Core\Http\Requests\BaseFormRequest;

class StoreCommentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:1000'],
        ];
    }
}
