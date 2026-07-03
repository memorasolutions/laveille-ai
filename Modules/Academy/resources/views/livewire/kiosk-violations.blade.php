{{-- Author: MEMORA solutions, https://memora.solutions --}}
{{--
    MODE KIOSQUE - Journal des incidents (sortie plein écran, changement
    d'onglet, outils de développement suspectés, sortie volontaire) consignés
    pendant les tentatives de quiz surveillées de CE cours. Gâté
    manageEnrollments (mount + chaque lecture). Tentatives re-scopées au cours
    (anti-IDOR). PUREMENT DÉCLARATIF : aucune action de correction ici.
--}}
<div x-data="{ open: null }">
    @php($attempts = $this->attemptsWithViolations)

    @if ($attempts->isEmpty())
        <p class="text-muted" style="font-size: 0.92rem;">
            Aucun incident de mode kiosque consigné pour ce cours.
        </p>
    @else
        <p class="text-muted" style="font-size: 0.92rem;">
            {{ $attempts->count() }} {{ $attempts->count() > 1 ? 'tentatives comportent' : 'tentative comporte' }} au moins un incident consigné.
        </p>

        <ul style="list-style: none; padding: 0; margin: 0;">
            @foreach ($attempts as $attempt)
                @php($violations = $this->violationsFor($attempt->id))
                <li wire:key="kiosk-attempt-{{ $attempt->id }}"
                    class="mb-2 p-3 rounded"
                    style="background: #FFF7ED; border: 1px solid #FDBA74;">
                    <button type="button"
                            @click="open = (open === {{ $attempt->id }}) ? null : {{ $attempt->id }}"
                            class="d-flex flex-wrap align-items-center justify-content-between gap-2"
                            style="width: 100%; background: none; border: none; padding: 0; cursor: pointer; text-align: left;"
                            :aria-expanded="open === {{ $attempt->id }} ? 'true' : 'false'">
                        <span style="font-size: 0.92rem; color: #1A1D23;">
                            <strong>{{ $attempt->user?->name ?? 'Étudiant' }}</strong>
                            <span class="text-muted">·</span>
                            {{ $attempt->lessonItem?->title ?? 'Quiz' }}
                            <span class="text-muted">·</span>
                            remis le {{ optional($attempt->submitted_at)->timezone('America/Toronto')?->format('Y-m-d H:i') }}
                            <span class="text-muted">·</span>
                            {{ $violations->count() }} {{ $violations->count() > 1 ? 'incidents' : 'incident' }}
                        </span>
                        <span aria-hidden="true" x-text="open === {{ $attempt->id }} ? '−' : '+'" style="font-weight: 700; font-size: 1.1rem;"></span>
                    </button>

                    <div x-show="open === {{ $attempt->id }}" x-cloak class="mt-2">
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            @foreach ($violations as $violation)
                                <li wire:key="violation-{{ $violation->id }}"
                                    class="mb-1 p-2 rounded"
                                    style="background: #fff; border: 1px solid #E2E8F0; font-size: 0.85rem;">
                                    <strong>{{ $this->violationLabel($violation->type) }}</strong>
                                    <span class="text-muted">
                                        — {{ $violation->occurred_at?->timezone('America/Toronto')?->format('Y-m-d H:i:s') }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
