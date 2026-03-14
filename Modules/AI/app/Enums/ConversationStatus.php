<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Enums;

enum ConversationStatus: string
{
    case AiActive = 'ai_active';
    case WaitingHuman = 'waiting_human';
    case HumanActive = 'human_active';
    case Closed = 'closed';
}
