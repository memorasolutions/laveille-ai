<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project la-veille-de-stef-v2
 *
 * Constructeur front-end du journal personnel (blocs empilés réordonnables).
 *
 * MODÈLE DE SÉCURITÉ (OWASP A01, autorisation SERVEUR - NON NÉGOCIABLE) :
 *  - L'identifiant du journal est figé au montage (#[Locked]).
 *  - À CHAQUE mutation, le journal est RE-RÉSOLU depuis la BD et ré-autorisé
 *    (policy update() = propriétaire uniquement).
 *  - Le réordonnancement vérifie que l'ensemble d'IDs reçu du client est une
 *    permutation exacte de l'ensemble attendu côté serveur avant d'écrire.
 */

declare(strict_types=1);

namespace Modules\Journal\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Journal\Models\Journal;
use Modules\Journal\Models\JournalBlock;
use Modules\Journal\Services\JournalBlockService;

class JournalBuilder extends Component
{
    use WithFileUploads;

    /**
     * @var array<string, string>
     */
    public const TEMPLATES = [
        'classique' => 'Classique — liste verticale simple',
        'magazine' => 'Magazine — mise en grille',
        'carnet-photo' => 'Carnet photo — accent visuel',
        'chronologique' => 'Chronologique — ligne du temps',
    ];

    #[Locked]
    public int $journalId;

    public string $title = '';

    public string $template = 'classique';

    public bool $isPublished = false;

    /** Panneau d'ajout/édition actif : '', 'text', 'image' ou 'video'. */
    public string $activePanel = '';

    public string $textBlockHtml = '';

    public ?int $editingBlockId = null;

    public ?int $confirmingRemoveBlockId = null;

    public $imageFile = null;

    public bool $imageRightsConfirmed = false;

    public string $videoUrl = '';

    public function mount(Journal $journal): void
    {
        $this->authorize('update', $journal);

        $this->journalId = $journal->id;
        $this->title = $journal->title;
        $this->template = $journal->template;
        $this->isPublished = $journal->isPublished();
    }

    /** Re-résout le journal depuis la BD et ré-autorise (appelé avant chaque mutation). */
    private function journal(): Journal
    {
        $journal = Journal::findOrFail($this->journalId);
        $this->authorize('update', $journal);

        return $journal;
    }

    public function updateSettings(): void
    {
        $journal = $this->journal();

        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'template' => ['required', Rule::in(array_keys(self::TEMPLATES))],
        ]);

        $journal->update($validated);
    }

    public function togglePublished(): void
    {
        $journal = $this->journal();
        $journal->update(['is_published' => ! $journal->isPublished()]);
        $this->isPublished = $journal->fresh()->isPublished();
    }

    public function openPanel(string $panel): void
    {
        $this->reset(['textBlockHtml', 'imageFile', 'imageRightsConfirmed', 'videoUrl', 'editingBlockId']);
        $this->activePanel = $panel;
    }

    public function closePanel(): void
    {
        $this->activePanel = '';
        $this->reset(['textBlockHtml', 'imageFile', 'imageRightsConfirmed', 'videoUrl', 'editingBlockId']);
    }

    /** Reçoit le HTML édité, poussé par le pont Alpine/Tiptap (voir vue). */
    public function receiveTiptapContent(string $value): void
    {
        $this->textBlockHtml = $value;
    }

    public function saveTextBlock(): void
    {
        $journal = $this->journal();

        $this->validate(['textBlockHtml' => ['required', 'string']]);

        if ($this->editingBlockId) {
            $journal->blocks()->where('id', $this->editingBlockId)->update([
                'payload' => ['html' => $this->textBlockHtml],
            ]);
        } else {
            app(JournalBlockService::class)->addTextBlock($journal, $this->textBlockHtml);
        }

        $this->closePanel();
    }

    public function editTextBlock(int $blockId): void
    {
        $journal = $this->journal();
        $block = $journal->blocks()->findOrFail($blockId);

        $this->reset(['imageFile', 'imageRightsConfirmed', 'videoUrl']);
        $this->editingBlockId = $blockId;
        $this->textBlockHtml = (string) ($block->payload['html'] ?? '');
        $this->activePanel = 'text';
    }

    public function saveImageBlock(): void
    {
        $journal = $this->journal();

        $this->validate([
            'imageFile' => ['required', 'image', 'max:8192'],
            'imageRightsConfirmed' => ['accepted'],
        ]);

        app(JournalBlockService::class)->addImageBlock($journal, $this->imageFile, $this->imageRightsConfirmed);

        $this->closePanel();
    }

    public function saveVideoBlock(): void
    {
        $journal = $this->journal();

        $this->validate(['videoUrl' => ['required', 'url']]);

        app(JournalBlockService::class)->addVideoBlock($journal, $this->videoUrl);

        $this->closePanel();
    }

    /** Confirmation inline à 2 temps (jamais de popup native confirm()). */
    public function confirmRemoveBlock(int $blockId): void
    {
        $this->confirmingRemoveBlockId = $blockId;
    }

    public function cancelRemoveBlock(): void
    {
        $this->confirmingRemoveBlockId = null;
    }

    public function removeBlock(int $blockId): void
    {
        $journal = $this->journal();
        $journal->blocks()->where('id', $blockId)->delete();
        $this->confirmingRemoveBlockId = null;
    }

    public function moveBlockUp(int $blockId): void
    {
        $this->swapBlock($blockId, 'up');
    }

    public function moveBlockDown(int $blockId): void
    {
        $this->swapBlock($blockId, 'down');
    }

    private function swapBlock(int $blockId, string $direction): void
    {
        $journal = $this->journal();
        $block = $journal->blocks()->findOrFail($blockId);

        $neighbor = $direction === 'up'
            ? $journal->blocks()->where('sort_order', '<', $block->sort_order)->orderByDesc('sort_order')->first()
            : $journal->blocks()->where('sort_order', '>', $block->sort_order)->orderBy('sort_order')->first();

        if (! $neighbor) {
            return;
        }

        DB::transaction(function () use ($block, $neighbor) {
            $blockOrder = $block->sort_order;
            $neighborOrder = $neighbor->sort_order;

            $block->update(['sort_order' => $neighborOrder]);
            $neighbor->update(['sort_order' => $blockOrder]);
        });
    }

    /**
     * @param  array<int, int|string>  $orderedIds
     */
    public function reorderBlocks(array $orderedIds): void
    {
        $journal = $this->journal();

        $expected = $journal->blocks()->pluck('id')->sort()->values()->all();
        $received = collect($orderedIds)->map(fn ($id) => (int) $id)->sort()->values()->all();

        if ($expected !== $received) {
            return;
        }

        DB::transaction(function () use ($journal, $orderedIds) {
            foreach ($orderedIds as $index => $blockId) {
                JournalBlock::where('id', (int) $blockId)
                    ->where('journal_id', $journal->id)
                    ->update(['sort_order' => $index]);
            }
        });
    }

    public function render(): \Illuminate\View\View
    {
        $journal = $this->journal();

        return view('journal::livewire.journal-builder', [
            'journal' => $journal,
            'blocks' => $journal->blocks()->get(),
            'templates' => self::TEMPLATES,
        ]);
    }
}
