<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Livewire;

use Livewire\Component;
use Modules\AI\Enums\ConversationStatus;
use Modules\AI\Enums\MessageRole;
use Modules\AI\Events\HumanTakeoverRequested;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\AiMessage;
use Modules\AI\Services\AiService;
use Modules\AI\Services\RagService;
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

    public string $conversationStatus = 'ai_active';

    public string $currentPage = '';

    /** @var array<int, string> */
    public array $suggestions = [];

    public bool $leadMode = false;

    public int $leadStep = 0;

    /** @var array{name: string, email: string, phone: string, message: string} */
    public array $leadData = ['name' => '', 'email' => '', 'phone' => '', 'message' => ''];

    public string $botName = 'Assistant IA';

    public string $botAvatar = '';

    public string $primaryColor = '#487FFF';

    public string $position = 'bottom-right';

    public function mount(?string $page = null): void
    {
        if (! Setting::get('ai.chatbot_enabled', false)) {
            return;
        }

        $this->currentPage = $page ?? '';
        $this->suggestions = $this->getContextualSuggestions();
        $this->loadConversation();

        $this->botName = Setting::get('ai.chatbot_name', 'Assistant IA');
        $this->botAvatar = Setting::get('ai.chatbot_avatar_url', '');
        $this->primaryColor = Setting::get('ai.chatbot_primary_color', '#487FFF');
        $this->position = Setting::get('ai.chatbot_position', 'bottom-right');
    }

    public function toggleOpen(): void
    {
        $this->isOpen = ! $this->isOpen;
    }

    public function sendSuggestion(string $suggestion): void
    {
        $this->message = $suggestion;
        $this->suggestions = [];
        $this->sendMessage();
    }

    public function sendMessage(): void
    {
        if ($this->leadMode) {
            $this->submitLeadField();

            return;
        }

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
            $basePrompt = Setting::get('ai.system_prompt', file_get_contents(module_path('AI', 'config/system_prompt.txt')) ?: 'You are a helpful assistant.');

            /** @var RagService $ragService */
            $ragService = app(RagService::class);
            $systemPrompt = $ragService->buildSystemPrompt($basePrompt, $userMessage);
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
            $this->checkAutoEscalation();
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
            $this->conversationStatus = ConversationStatus::WaitingHuman->value;
            event(new HumanTakeoverRequested($conversation));
            $this->messages[] = ['role' => 'assistant', 'content' => __('Un agent humain va vous répondre sous peu.')];
        }
    }

    public function checkAutoEscalation(): void
    {
        if (! $this->conversationId || ! Setting::get('ai.chatbot_agent_handoff_enabled', false)) {
            return;
        }

        $recentAiMessages = AiMessage::where('conversation_id', $this->conversationId)
            ->where('role', MessageRole::Assistant)
            ->orderByDesc('created_at')
            ->take(3)
            ->get();

        if ($recentAiMessages->count() < 3) {
            return;
        }

        $poorCount = $recentAiMessages->filter(function (AiMessage $msg) {
            $content = mb_strtolower($msg->content);

            return str_contains($content, 'je ne suis pas en mesure')
                || str_contains($content, 'erreur')
                || mb_strlen(trim($msg->content)) < 20;
        })->count();

        if ($poorCount >= 3) {
            $escalationMsg = __('Je vais vous transférer à un agent humain pour mieux vous aider.');
            $this->messages[] = ['role' => 'assistant', 'content' => $escalationMsg];
            $this->persistMessage('assistant', $escalationMsg);
            $this->requestHuman();
        }
    }

    public function pollAgentMessages(): void
    {
        if (! $this->conversationId) {
            return;
        }

        $conversation = AiConversation::find($this->conversationId);
        if (! $conversation) {
            return;
        }

        $this->conversationStatus = $conversation->status->value;

        if ($conversation->status === ConversationStatus::Closed) {
            $this->messages[] = ['role' => 'assistant', 'content' => __("L'agent a fermé la conversation. Merci !")];
            $this->conversationId = null;
            $this->conversationStatus = 'ai_active';

            return;
        }

        if (! in_array($conversation->status, [ConversationStatus::HumanActive, ConversationStatus::WaitingHuman])) {
            return;
        }

        $displayedAgentCount = collect($this->messages)->where('role', 'agent')->count();

        $newMessages = AiMessage::where('conversation_id', $this->conversationId)
            ->where('role', MessageRole::Agent)
            ->orderBy('created_at')
            ->skip($displayedAgentCount)
            ->limit(100)
            ->get();

        foreach ($newMessages as $message) {
            $this->messages[] = ['role' => 'agent', 'content' => $message->content];
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
        $this->dispatch('conversation-cleared');
    }

    public function startLeadCapture(): void
    {
        $this->leadMode = true;
        $this->leadStep = 1;
        $prompt = $this->getLeadPrompt();
        $this->messages[] = ['role' => 'assistant', 'content' => $prompt];
        $this->persistMessage('assistant', $prompt);
    }

    public function submitLeadField(): void
    {
        $userInput = trim($this->message);
        $this->message = '';

        if ($userInput === '') {
            return;
        }

        $this->messages[] = ['role' => 'user', 'content' => $userInput];
        $this->persistMessage('user', $userInput);

        match ($this->leadStep) {
            1 => $this->handleLeadName($userInput),
            2 => $this->handleLeadEmail($userInput),
            3 => $this->handleLeadPhone($userInput),
            4 => $this->handleLeadMessage($userInput),
            default => null,
        };
    }

    public function cancelLeadCapture(): void
    {
        $this->leadMode = false;
        $this->leadStep = 0;
        $this->leadData = ['name' => '', 'email' => '', 'phone' => '', 'message' => ''];
        $msg = 'Pas de souci ! Comment puis-je vous aider autrement ?';
        $this->messages[] = ['role' => 'assistant', 'content' => $msg];
        $this->persistMessage('assistant', $msg);
    }

    private function handleLeadName(string $input): void
    {
        $this->leadData['name'] = $input;
        $this->leadStep = 2;
        $this->addLeadPrompt();
    }

    private function handleLeadEmail(string $input): void
    {
        if (! filter_var($input, FILTER_VALIDATE_EMAIL)) {
            $msg = 'Ce format d\'adresse courriel ne semble pas valide. Pourriez-vous vérifier ?';
            $this->messages[] = ['role' => 'assistant', 'content' => $msg];
            $this->persistMessage('assistant', $msg);

            return;
        }

        $this->leadData['email'] = $input;
        $this->leadStep = 3;
        $this->addLeadPrompt();
    }

    private function handleLeadPhone(string $input): void
    {
        $skip = ['passer', 'skip', 'non', 'non merci', '-'];
        if (in_array(strtolower($input), $skip, true)) {
            $this->leadData['phone'] = '';
        } else {
            $this->leadData['phone'] = $input;
        }

        $this->leadStep = 4;
        $this->addLeadPrompt();
    }

    private function handleLeadMessage(string $input): void
    {
        $this->leadData['message'] = $input;
        $this->finalizeLeadCapture();
    }

    private function finalizeLeadCapture(): void
    {
        if (class_exists(\App\Models\ContactMessage::class)) {
            $contactMessage = \App\Models\ContactMessage::create([
                'name' => $this->leadData['name'],
                'email' => $this->leadData['email'],
                'subject' => 'Lead chatbot - '.($this->currentPage ?: 'accueil'),
                'message' => $this->leadData['message']
                    .($this->leadData['phone'] ? "\n\nTéléphone : ".$this->leadData['phone'] : ''),
                'status' => 'new',
                'ip_address' => request()->ip(),
            ]);

            \Illuminate\Support\Facades\Notification::route('mail', config('mail.from.address'))
                ->notify(new \Modules\AI\Notifications\ChatLeadNotification($contactMessage));
        } else {
            $adminEmail = config('mail.from.address');
            $lead = $this->leadData;
            $body = "Nouveau lead reçu depuis {$this->currentPage} :\n"
                ."Nom : {$lead['name']}\n"
                ."Courriel : {$lead['email']}\n"
                ."Téléphone : {$lead['phone']}\n"
                ."Message : {$lead['message']}";
            \Illuminate\Support\Facades\Mail::raw($body, function ($message) use ($adminEmail) {
                $message->to($adminEmail)->subject('Nouveau lead du chatbot');
            });
        }

        $msg = 'Merci beaucoup, '.$this->leadData['name'].' ! Vos informations ont bien été transmises à notre équipe. '
            .'Nous vous contacterons sous peu à '.$this->leadData['email'].'. '
            .'En attendant, puis-je vous aider avec autre chose ?';
        $this->messages[] = ['role' => 'assistant', 'content' => $msg];
        $this->persistMessage('assistant', $msg);

        $this->leadMode = false;
        $this->leadStep = 0;
        $this->leadData = ['name' => '', 'email' => '', 'phone' => '', 'message' => ''];
    }

    private function addLeadPrompt(): void
    {
        $prompt = $this->getLeadPrompt();
        $this->messages[] = ['role' => 'assistant', 'content' => $prompt];
        $this->persistMessage('assistant', $prompt);
    }

    private function getLeadPrompt(): string
    {
        return match ($this->leadStep) {
            1 => 'Parfait ! Pour qu\'un membre de notre équipe puisse vous contacter, j\'aurais besoin de quelques informations. Commençons par votre nom complet :',
            2 => 'Merci ! Maintenant, votre adresse courriel pour vous recontacter :',
            3 => 'Votre numéro de téléphone (optionnel - vous pouvez taper « passer ») :',
            4 => 'Dernière étape : décrivez brièvement votre besoin ou votre projet :',
            default => '',
        };
    }

    /** @param array<int, array<string, mixed>> $storedMessages */
    public function restoreMessages(array $storedMessages): void
    {
        if (auth()->check() || ! empty($this->messages)) {
            return;
        }

        $clean = [];
        foreach ($storedMessages as $msg) {
            if (isset($msg['role'], $msg['content'])
                && in_array($msg['role'], ['user', 'assistant'], true)
                && is_string($msg['content'])
                && mb_strlen($msg['content']) <= 2000
            ) {
                $clean[] = ['role' => $msg['role'], 'content' => $msg['content']];
            }
        }

        $this->messages = array_slice($clean, -50);
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        $enabled = (bool) Setting::get('ai.chatbot_enabled', false);
        $welcomeMessage = $this->getWelcomeMessage();
        $isGuest = ! auth()->check();

        return view('ai::livewire.chatbot', [
            'enabled' => $enabled,
            'welcomeMessage' => $welcomeMessage,
            'isGuest' => $isGuest,
            'leadCaptureEnabled' => (bool) Setting::get('ai.chatbot_lead_capture_enabled', true),
            'agentHandoffEnabled' => (bool) Setting::get('ai.chatbot_agent_handoff_enabled', false),
        ]);
    }

    private function getWelcomeMessage(): string
    {
        $messages = [
            'services' => 'Bonjour ! Vous consultez nos services. Comment puis-je vous aider à trouver la solution idéale ?',
            'pricing' => 'Bonjour ! Vous avez des questions sur nos tarifs ? Je peux vous donner des fourchettes de prix.',
            'contact' => 'Bonjour ! Vous souhaitez nous contacter ? Je peux vous aider ou planifier un appel.',
            'blog' => 'Bonjour ! Vous lisez notre blogue. Avez-vous des questions sur le sujet ?',
        ];

        foreach ($messages as $keyword => $message) {
            if (str_contains(strtolower($this->currentPage), $keyword)) {
                return $message;
            }
        }

        return Setting::get('ai.chatbot_welcome_message', 'Bonjour ! Comment puis-je vous aider ?');
    }

    /** @return array<int, string> */
    private function getContextualSuggestions(): array
    {
        $page = strtolower($this->currentPage);

        if (str_contains($page, 'services')) {
            return ['Quels services offrez-vous ?', 'Combien coûte un site web ?', 'Prendre rendez-vous'];
        }

        if (str_contains($page, 'pricing') || str_contains($page, 'tarif')) {
            return ['Quels sont vos tarifs ?', 'Offrez-vous des forfaits ?', 'Consultation gratuite'];
        }

        if (str_contains($page, 'contact')) {
            return ['Planifier un appel', 'Demander un devis', 'Vos délais de réponse ?'];
        }

        return ['Quels services offrez-vous ?', 'Combien ça coûte ?', 'Prendre rendez-vous'];
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
                $this->conversationStatus = $conversation->status->value;
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
