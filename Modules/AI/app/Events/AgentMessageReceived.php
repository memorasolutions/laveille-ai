<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\AI\Models\AiConversation;

class AgentMessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AiConversation $conversation,
        public string $message,
        public string $agentName,
    ) {}

    /** @return array<int, \Illuminate\Broadcasting\Channel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('ai.conversation.'.$this->conversation->id)];
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'agent_name' => $this->agentName,
            'sent_at' => now()->toISOString(),
        ];
    }
}
