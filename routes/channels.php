<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('ai.agents', function ($user) {
    return $user->can('manage_ai') ? ['id' => $user->id, 'name' => $user->name] : false;
});

Broadcast::channel('ai.conversation.{conversationId}', function ($user, $conversationId) {
    /** @var \Modules\AI\Models\AiConversation|null $conversation */
    $conversation = \Modules\AI\Models\AiConversation::query()->find($conversationId);

    return $conversation && ((int) $conversation->user_id === (int) $user->id || (int) $conversation->agent_id === (int) $user->id);
});
