<?php

declare(strict_types=1);

namespace Modules\AI\Enums;

enum MessageRole: string
{
    case System = 'system';
    case User = 'user';
    case Assistant = 'assistant';
    case Agent = 'agent';
}
