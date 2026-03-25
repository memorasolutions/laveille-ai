<?php

declare(strict_types=1);

namespace Modules\Directory\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Modules\Core\Notifications\TemplatedNotification;
use Modules\Directory\Models\ToolSuggestion;

class SuggestionRejectedNotification extends TemplatedNotification
{
    public function __construct(protected ToolSuggestion $suggestion) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    protected function getTemplateSlug(): string
    {
        return 'suggestion_rejected';
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
                'admin_note' => $this->suggestion->admin_note,
            ],
        ];
    }

    protected function getFallbackMail(object $notifiable): MailMessage
    {
        $itemName = $this->suggestion->getItemName();
        $fieldLabel = ToolSuggestion::fieldLabels()[$this->suggestion->field] ?? $this->suggestion->field;

        $mail = (new MailMessage)
            ->subject(__('Votre suggestion n\'a pas été retenue'))
            ->greeting(__('Bonjour :name !', ['name' => $notifiable->name]))
            ->line(__('Votre suggestion pour le champ « :field » de la fiche « :item » n\'a pas été retenue.', [
                'field' => $fieldLabel,
                'item' => $itemName,
            ]));

        if ($this->suggestion->admin_note) {
            $mail->line(__('Note de l\'équipe : :note', ['note' => $this->suggestion->admin_note]));
        }

        return $mail
            ->action(__('Voir mes contributions'), route('user.contributions'))
            ->line(__('N\'hésitez pas à soumettre d\'autres suggestions !'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'suggestion_rejected',
            'suggestion_id' => $this->suggestion->id,
            'item_name' => $this->suggestion->getItemName(),
            'field' => $this->suggestion->field,
            'message' => __('Votre suggestion pour « :item » n\'a pas été retenue.', [
                'item' => $this->suggestion->getItemName(),
            ]),
        ];
    }
}
