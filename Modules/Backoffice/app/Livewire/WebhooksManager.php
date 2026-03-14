<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Livewire\Attributes\Validate;
use Livewire\Component;
use Modules\Webhooks\Models\WebhookEndpoint;

class WebhooksManager extends Component
{
    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|url|max:500')]
    public string $url = '';

    #[Validate('nullable|string|max:255')]
    public string $secret = '';

    public ?string $successMessage = null;

    public function store(): void
    {
        $this->validate();

        WebhookEndpoint::create([
            'name' => $this->name,
            'url' => $this->url,
            'secret' => $this->secret ?: null,
        ]);

        $this->name = '';
        $this->url = '';
        $this->secret = '';
        $this->successMessage = 'Webhook créé avec succès.';
    }

    public function delete(int $id): void
    {
        WebhookEndpoint::findOrFail($id)->delete();
        $this->successMessage = 'Webhook supprimé.';
    }

    public function render(): \Illuminate\View\View
    {
        $webhooks = WebhookEndpoint::latest()->get();

        return view('backoffice::livewire.webhooks-manager', compact('webhooks'));
    }
}
