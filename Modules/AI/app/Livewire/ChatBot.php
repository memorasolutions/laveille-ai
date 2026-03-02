<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Livewire;

use Livewire\Component;
use Modules\AI\Enums\ConversationStatus;
use Modules\AI\Enums\MessageRole;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\AiMessage;
use Modules\AI\Events\HumanTakeoverRequested;
use Modules\AI\Services\AiService;
use Modules\Settings\Models\Setting;

class ChatBot extends Component
{
    public bool $isOpen = false;

    public string $message = '';

    /** @var array<int, array{role: string, content: string}> */
    public array $messages = [];

    public bool $isLoading = false;

    public ?int $conversationId = null;

    public string $error = '';

    public string $streamedResponse = '';

    public function mount(): void
    {
        if (! Setting::get('ai.chatbot_enabled', false)) {
            return;
        }

        $this->loadConversation();
    }

    public function toggleOpen(): void
    {
        $this->isOpen = ! $this->isOpen;
    }

    public function sendMessage(): void
    {
        $this->validate([
            'message' => 'required|string|max:1000',
        ]);

        $this->error = '';
        $userMessage = trim($this->message);
        $this->message = '';

        $this->messages[] = ['role' => 'user', 'content' => $userMessage];
        $this->persistMessage('user', $userMessage);

        $this->isLoading = true;

        try {
            /** @var AiService $aiService */
            $aiService = app(AiService::class);

            $apiMessages = [];
            $systemPrompt = Setting::get('ai.system_prompt', 'You are a helpful assistant.');
            $apiMessages[] = ['role' => 'system', 'content' => $systemPrompt];

            foreach ($this->messages as $msg) {
                $apiMessages[] = ['role' => $msg['role'], 'content' => $msg['content']];
            }

            $response = $aiService->chatWithHistory($apiMessages);

            if ($response === '' || $response === '0') {
                $this->error = __('Une erreur est survenue. Veuillez réessayer.');
                $this->isLoading = false;

                return;
            }

            $this->streamedResponse = '';
            $words = explode(' ', $response);
            foreach ($words as $i => $word) {
                $this->streamedResponse .= ($i > 0 ? ' ' : '').$word;
                $this->stream('chatbot-response', $this->streamedResponse);
            }

            $this->messages[] = ['role' => 'assistant', 'content' => $response];
            $this->streamedResponse = '';

            $this->persistMessage('assistant', $response);
        } catch (\Exception $e) {
            $this->error = __('Une erreur est survenue. Veuillez réessayer.');
        }

        $this->isLoading = false;
    }

    public function requestHuman(): void
    {
        if (! auth()->check() || ! $this->conversationId) {
            $this->error = __('Vous devez être connecté pour contacter un agent.');

            return;
        }

        $conversation = AiConversation::find($this->conversationId);
        if ($conversation && $conversation->status === ConversationStatus::AiActive) {
            $conversation->update(['status' => ConversationStatus::WaitingHuman]);
            event(new HumanTakeoverRequested($conversation));
            $this->messages[] = ['role' => 'assistant', 'content' => __('Un agent humain va vous répondre sous peu.')];
        }
    }

    public function clearConversation(): void
    {
        if (auth()->check() && $this->conversationId) {
            $conversation = AiConversation::find($this->conversationId);
            if ($conversation) {
                $conversation->update([
                    'status' => ConversationStatus::Closed,
                    'closed_at' => now(),
                ]);
            }
            $this->conversationId = null;
        } else {
            session()->forget('ai_chat_messages');
        }

        $this->messages = [];
        $this->error = '';
        $this->streamedResponse = '';
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $enabled = (bool) Setting::get('ai.chatbot_enabled', false);

        return view('ai::livewire.chatbot', ['enabled' => $enabled]);
    }

    private function loadConversation(): void
    {
        if (auth()->check()) {
            $conversation = AiConversation::where('user_id', auth()->id())
                ->active()
                ->latest()
                ->first();

            if ($conversation) {
                $this->conversationId = $conversation->id;
                $dbMessages = $conversation->messages()
                    ->whereIn('role', [MessageRole::User, MessageRole::Assistant])
                    ->orderBy('created_at')
                    ->get();

                $this->messages = [];
                foreach ($dbMessages as $m) {
                    /** @var AiMessage $m */
                    $this->messages[] = ['role' => $m->role->value, 'content' => $m->content];
                }
            }
        } else {
            $this->messages = session('ai_chat_messages', []);
        }
    }

    private function persistMessage(string $role, string $content): void
    {
        if (auth()->check()) {
            if (! $this->conversationId) {
                $conversation = AiConversation::create([
                    'user_id' => auth()->id(),
                    'title' => mb_substr($content, 0, 50),
                    'status' => ConversationStatus::AiActive,
                    'model' => app(AiService::class)->getModelForTask('chatbot'),
                ]);
                $this->conversationId = $conversation->id;
            }

            AiMessage::create([
                'conversation_id' => $this->conversationId,
                'role' => $role,
                'content' => $content,
            ]);
        } else {
            session(['ai_chat_messages' => $this->messages]);
        }
    }
}
