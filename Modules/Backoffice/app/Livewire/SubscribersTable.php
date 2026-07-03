<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Livewire;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Backoffice\Livewire\Concerns\WithInfiniteScroll;
use Modules\Core\Traits\HasBulkActions;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\WelcomeNewsletterNotification;
use Modules\Settings\Facades\Settings;
use Throwable;

class SubscribersTable extends Component
{
    use HasBulkActions, WithInfiniteScroll, WithPagination;

    protected string $paginationTheme = 'bootstrap';

    #[Url]
    public string $search = '';

    #[Url]
    public string $filterStatus = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->filterStatus = '';
        $this->resetPage();
        $this->resetInfiniteScroll();
    }

    /**
     * Surcharge de la méthode du trait HasBulkActions.
     * L'action 'resend' gère son propre toast récapitulatif ;
     * les autres actions gardent le toast générique du trait.
     * NOTE : la route DELETE newsletter/{subscriber} a été corrigée pour utiliser
     * 'delete_newsletter' (Pattern A CRUD réellement seedé, au lieu de 'manage_newsletter'
     * qui n'était jamais seedée) → on vérifie ici au cas par cas (delete_newsletter /
     * update_newsletter) dans handleBulkAction()/bulkResendConfirmation(), la garde générique
     * est donc déléguée plus bas selon l'action réelle.
     */
    public function executeBulkAction(): void
    {
        if (empty($this->bulkAction) || empty($this->selected)) {
            $this->dispatch('toast', type: 'error', message: __('Sélectionnez des éléments et une action.'));

            return;
        }

        if ($this->bulkAction === 'resend') {
            $this->bulkResendConfirmation($this->selected);
            $this->resetBulkSelection();

            return;
        }

        // Pour les autres actions : déléguer puis toast générique (comportement du trait).
        $this->handleBulkAction($this->bulkAction, $this->selected);
        $this->resetBulkSelection();
        $this->dispatch('toast', type: 'success', message: __('Action effectuée.'));
    }

    /**
     * Requiert delete_newsletter (permission Pattern A réellement seedée, cohérente avec la
     * route DELETE newsletter/{subscriber} désormais alignée sur 'delete_newsletter').
     */
    public function delete(int $id): void
    {
        abort_if(! auth()->user()?->can('delete_newsletter'), 403);

        Subscriber::find($id)?->delete();
    }

    /**
     * Requiert update_newsletter (mutation sans suppression ; aucune route HTTP dédiée au
     * renvoi n'existe, permission Pattern A la plus proche retenue pour ce module).
     */
    public function resendConfirmation(int $id): void
    {
        abort_if(! auth()->user()?->can('update_newsletter'), 403);

        $subscriber = Subscriber::find($id);

        if (! $subscriber || ! $subscriber->canResendConfirmation()) {
            $this->dispatch('toast', type: 'warning', message: __('Cet abonné n\'est pas éligible au renvoi (déjà confirmé, désabonné, limite atteinte ou délai non écoulé).'));

            return;
        }

        try {
            Notification::route('mail', $subscriber->email)
                ->notify(new WelcomeNewsletterNotification($subscriber));

            $subscriber->markReminded();

            $this->dispatch('toast', type: 'success', message: __('Courriel de confirmation renvoyé à ') . $subscriber->email . '.');
        } catch (Throwable $e) {
            Log::error('Échec du renvoi de confirmation à ' . $subscriber->email, ['exception' => $e]);
            $this->dispatch('toast', type: 'error', message: __('Échec de l\'envoi. Veuillez réessayer.'));
        }
    }

    protected function getBulkActions(): array
    {
        return [
            'resend' => __('Renvoyer la confirmation'),
            'delete' => __('Supprimer'),
        ];
    }

    /**
     * Requiert delete_newsletter (delete) ou update_newsletter (resend),
     * permissions Pattern A réellement seedées pour ce module.
     */
    protected function handleBulkAction(string $action, array $ids): void
    {
        if ($action === 'delete') {
            abort_if(! auth()->user()?->can('delete_newsletter'), 403);
        } else {
            abort_if(! auth()->user()?->can('update_newsletter'), 403);
        }

        match ($action) {
            'delete' => Subscriber::whereIn('id', $ids)->delete(),
            'resend' => $this->bulkResendConfirmation($ids),
            default => null,
        };
    }

    private function bulkResendConfirmation(array $ids): void
    {
        $sent = 0;
        $skipped = 0;

        foreach ($ids as $id) {
            $subscriber = Subscriber::find((int) $id);

            if (! $subscriber || ! $subscriber->canResendConfirmation()) {
                $skipped++;

                continue;
            }

            try {
                Notification::route('mail', $subscriber->email)
                    ->notify(new WelcomeNewsletterNotification($subscriber));

                $subscriber->markReminded();
                $sent++;
            } catch (Throwable $e) {
                Log::error('Échec du renvoi de confirmation (bulk) à ' . $subscriber->email, ['exception' => $e]);
                $skipped++;
            }
        }

        $this->dispatch(
            'toast',
            type: $sent > 0 ? 'success' : 'warning',
            message: __("{$sent} confirmation(s) renvoyée(s), {$skipped} ignorée(s) (non éligibles ou erreur).")
        );
    }

    protected function getBulkPageIds(): array
    {
        return Subscriber::query()
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('email', 'like', '%'.$this->search.'%')
                ->orWhere('name', 'like', '%'.$this->search.'%')
            ))
            ->when($this->filterStatus === 'active', fn ($q) => $q->whereNotNull('confirmed_at')->whereNull('unsubscribed_at'))
            ->when($this->filterStatus === 'pending', fn ($q) => $q->whereNull('confirmed_at')->whereNull('unsubscribed_at'))
            ->when($this->filterStatus === 'unsubscribed', fn ($q) => $q->whereNotNull('unsubscribed_at'))
            ->latest()
            ->paginate($this->perPage)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    public function render(): \Illuminate\View\View
    {
        $query = Subscriber::query()
            ->when($this->search, fn ($q) => $q->where(fn ($q2) => $q2
                ->where('email', 'like', '%'.$this->search.'%')
                ->orWhere('name', 'like', '%'.$this->search.'%')
            ))
            ->when($this->filterStatus === 'active', fn ($q) => $q->whereNotNull('confirmed_at')->whereNull('unsubscribed_at'))
            ->when($this->filterStatus === 'pending', fn ($q) => $q->whereNull('confirmed_at')->whereNull('unsubscribed_at'))
            ->when($this->filterStatus === 'unsubscribed', fn ($q) => $q->whereNotNull('unsubscribed_at'))
            ->latest();

        $subscribers = $query->paginate($this->perPage);
        $totalCount = Subscriber::count();
        $activeCount = Subscriber::whereNotNull('confirmed_at')->whereNull('unsubscribed_at')->count();
        $pendingCount = Subscriber::whereNull('confirmed_at')->whereNull('unsubscribed_at')->count();

        // Désabonnements réels : abonnés confirmes qui ont cliqué « se désabonner ».
        $realUnsubscribedCount = Subscriber::whereNotNull('unsubscribed_at')
            ->whereNotNull('confirmed_at')
            ->where(static fn ($q) => $q
                ->whereNull('bounce_reason')
                ->orWhere('bounce_reason', '!=', 'auto_purge_unconfirmed_j7'))
            ->count();

        // Purges d'hygiene J+7 : non-confirmes purges automatiquement — pas de vrais departs.
        $hygienePurgesCount = Subscriber::where('bounce_reason', 'auto_purge_unconfirmed_j7')->count();

        // Compatibilité aval : total pour le filtre « Désabonné » de la table (inchangé).
        $unsubscribedCount = $realUnsubscribedCount + $hygienePurgesCount;

        return view('backoffice::livewire.subscribers-table', compact(
            'subscribers',
            'totalCount',
            'activeCount',
            'pendingCount',
            'unsubscribedCount',
            'realUnsubscribedCount',
            'hygienePurgesCount'
        ));
    }
}
