<?php

declare(strict_types=1);

namespace Modules\Directory\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;
use Modules\Directory\Models\ToolSuggestion;

class SuggestionApprovedNotification extends TemplatedNotification
{
    public function __construct(protected ToolSuggestion $suggestion) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    protected function getTemplateSlug(): string
    {
        return 'suggestion_approved';
    }

    protected function getTemplateData(object $notifiable): array
    {
        return [
            'user' => ['name' => $notifiable->name, 'email' => $notifiable->email],
            'app' => ['name' => config('app.name'), 'url' => config('app.url')],
            'suggestion' => [
                'item_name' => $this->suggestion->getItemName(),
                'field' => ToolSuggestion::fieldLabels()[$this->suggestion->field] ?? $this->suggestion->field,
                'source' => $this->suggestion->getSourceLabel()['name'],
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        $itemName = $this->suggestion->getItemName();
        $fieldLabel = ToolSuggestion::fieldLabels()[$this->suggestion->field] ?? $this->suggestion->field;

        return (new MailMessage)
            ->subject(__('Votre suggestion a été approuvée'))
            ->greeting(__('Bonjour :name !', ['name' => $notifiable->name]))
            ->line(__('Votre suggestion pour le champ « :field » de la fiche « :item » a été approuvée et appliquée.', [
                'field' => $fieldLabel,
                'item' => $itemName,
            ]))
            ->line(__('Vous avez gagné 5 points de réputation.'))
            ->action(__('Voir mes contributions'), route('user.contributions'))
            ->line(__('Merci pour votre contribution !'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'suggestion_approved',
            'suggestion_id' => $this->suggestion->id,
            'item_name' => $this->suggestion->getItemName(),
            'field' => $this->suggestion->field,
            'message' => __('Votre suggestion pour « :item » a été approuvée.', [
                'item' => $this->suggestion->getItemName(),
            ]),
        ];
    }
}
