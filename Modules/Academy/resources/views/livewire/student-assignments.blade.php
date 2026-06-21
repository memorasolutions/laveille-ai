<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    @if ($this->items->isNotEmpty())
        <section aria-labelledby="student-assignments" style="margin-top: 8px;">
            <h2 id="student-assignments" style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); margin: 0 0 12px; font-size: 1.25rem;">
                Mes devoirs
            </h2>

            @if (session('academy_student_assignments_status'))
                <div role="status" aria-live="polite"
                     style="border: 1px solid #BBF7D0; background: #F0FDF4; color: #166534; border-radius: var(--sys-radius-md, 0.75rem); padding: 12px 16px; font-weight: 600; margin-bottom: 14px;">
                    {{ session('academy_student_assignments_status') }}
                </div>
            @endif

            <ul class="list-unstyled d-flex flex-column gap-3" style="margin: 0;">
                @foreach ($this->items as $item)
                    @php($assignment = $item['assignment'])
                    @php($submission = $item['submission'])
                    <li wire:key="student-assignment-{{ $assignment->id }}"
                        style="border: 1px solid #E5E7EB; border-radius: var(--sys-radius-md, 0.75rem); padding: 16px 18px;">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div style="flex: 1 1 280px; min-width: 200px;">
                                <h3 style="font-family: var(--f-heading); font-size: 1.05rem; color: var(--sys-text-default, #1A1D23); margin: 0 0 4px;">
                                    {{ $assignment->title }}
                                </h3>
                                <div style="font-size: 0.78rem; color: var(--sys-text-muted, #6B7280);">
                                    {{ $assignment->course?->title }} · sur {{ $assignment->max_points }} pts
                                    @if ($assignment->due_at)
                                        · échéance {{ $assignment->due_at->timezone('America/Toronto')->format('Y-m-d H\hi') }}
                                    @endif
                                </div>
                            </div>
                            <div>
                                @if ($submission && $submission->isGraded())
                                    <span style="font-size: 0.8rem; font-weight: 700; color: #166534; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 999px; padding: 3px 12px;">
                                        Note : {{ $submission->score }} / {{ $assignment->max_points }}
                                    </span>
                                @elseif ($submission)
                                    <span style="font-size: 0.8rem; font-weight: 700; color: #92400E; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 999px; padding: 3px 12px;">Remis, en attente de correction</span>
                                @else
                                    <span style="font-size: 0.8rem; font-weight: 700; color: #6B7280; background: #F3F4F6; border: 1px solid #E5E7EB; border-radius: 999px; padding: 3px 12px;">Non remis</span>
                                @endif
                            </div>
                        </div>

                        @if ($assignment->instructions)
                            <div style="font-size: 0.9rem; color: var(--sys-text-default, #1A1D23); margin-top: 10px;">
                                {!! $assignment->renderedInstructions() !!}
                            </div>
                        @endif

                        {{-- Note + rétroaction (si corrigé) --}}
                        @if ($submission && $submission->isGraded() && $submission->feedback)
                            <div style="margin-top: 12px; border: 1px solid #BBF7D0; background: #F0FDF4; border-radius: var(--sys-radius-md, 0.5rem); padding: 12px 14px;">
                                <strong style="font-size: 0.85rem; color: #166534;">Rétroaction de votre formateur</strong>
                                <div style="font-size: 0.88rem; color: var(--sys-text-default, #1A1D23); margin-top: 6px;">
                                    {!! $submission->renderedFeedback() !!}
                                </div>
                            </div>
                        @endif

                        {{-- Formulaire de remise (tant que non corrigé) --}}
                        @if ($submission && $submission->isGraded())
                            <p style="font-size: 0.85rem; color: var(--sys-text-muted, #6B7280); margin: 10px 0 0;">
                                Cette remise a été corrigée et ne peut plus être modifiée.
                            </p>
                        @elseif ($openAssignment === $assignment->id)
                            <form wire:submit="submit" style="margin-top: 14px; display: flex; flex-direction: column; gap: 10px;">
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <label for="student-body-{{ $assignment->id }}" style="font-weight: 600; font-size: 0.85rem;">Votre réponse (markdown)</label>
                                    <textarea id="student-body-{{ $assignment->id }}" wire:model="body" rows="5" maxlength="20000" placeholder="Rédigez votre réponse…"
                                              style="width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: var(--sys-radius-md, 0.5rem); font-family: inherit;"></textarea>
                                    @error('body') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <label for="student-attachment-{{ $assignment->id }}" style="font-weight: 600; font-size: 0.85rem;">Pièce jointe (optionnel - pdf, word, image)</label>
                                    <input id="student-attachment-{{ $assignment->id }}" type="file" wire:model="attachment"
                                           accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp"
                                           style="font-size: 0.85rem;">
                                    @error('attachment') <span style="color: var(--sys-action-danger, #DC2626); font-size: 0.85rem;">{{ $message }}</span> @enderror
                                    @if ($submission && $submission->attachmentUrl())
                                        <span style="font-size: 0.82rem;">
                                            <a href="{{ $submission->attachmentUrl() }}" target="_blank" rel="noopener" style="color: var(--sys-action-primary, #064E5A);">Pièce jointe actuelle</a>
                                            · <button type="button" wire:click="removeAttachment({{ $assignment->id }})" style="background: none; border: none; color: var(--sys-action-danger, #DC2626); cursor: pointer; padding: 0; font-size: 0.82rem;">retirer</button>
                                        </span>
                                    @endif
                                </div>
                                <div class="d-flex gap-2">
                                    <x-core::button type="submit" variant="primary" size="sm">Enregistrer ma remise</x-core::button>
                                    <x-core::button type="button" wire:click="closeSubmission" variant="ghost" size="sm">Fermer</x-core::button>
                                </div>
                            </form>
                        @else
                            <div style="margin-top: 12px;">
                                <x-core::button type="button" wire:click="openSubmission({{ $assignment->id }})" variant="secondary" size="sm">
                                    {{ $submission ? 'Modifier ma remise' : 'Soumettre ma remise' }}
                                </x-core::button>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
