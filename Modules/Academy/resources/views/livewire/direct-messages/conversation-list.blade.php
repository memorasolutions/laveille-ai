{{--
    Author: MEMORA solutions, https://memora.solutions

    Messagerie directe (DM) - liste des conversations de l'utilisateur courant,
    triée par activité récente. Charte : token #064E5A, cartes cohérentes avec
    le reste de l'Académie (voir dashboard.blade.php).
--}}
<div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size: 1.25rem; margin: 0;">
            Mes messages
        </h2>
        <a href="{{ route('academy.messages.new') }}"
           class="btn btn-sm"
           style="background-color: #064E5A; color: #fff; border-radius: 6px; padding: 8px 16px;"
           wire:navigate>
            + Nouvelle conversation
        </a>
    </div>

    @if($this->conversations->isEmpty())
        <div style="padding: 24px; text-align: center; color: var(--sys-text-muted, #6B7280); border: 1px dashed #D1D5DB; border-radius: 8px;">
            Aucune conversation pour le moment. Démarrez-en une avec un formateur ou un apprenant de vos cours.
        </div>
    @else
        <ul class="list-unstyled" role="list" style="display: flex; flex-direction: column; gap: 8px;">
            @foreach($this->conversations as $row)
                @php($other = $row['other'])
                <li role="listitem">
                    <a href="{{ route('academy.messages.show', $row['conversation']->id) }}"
                       wire:navigate
                       class="d-flex align-items-center justify-content-between"
                       style="padding: 14px 16px; border: 1px solid #E5E7EB; border-radius: 8px; text-decoration: none; color: inherit; background-color: {{ $row['unread'] > 0 ? '#F0FDFA' : '#fff' }};">
                        <span>
                            <strong style="color: var(--sys-text-default, #1A1D23);">{{ $other?->name ?? 'Utilisateur retiré' }}</strong>
                            <br>
                            <span style="color: var(--sys-text-muted, #6B7280); font-size: 0.9rem;">
                                {{ $row['conversation']->course?->title }}
                            </span>
                            @if($row['lastMessage'])
                                <br>
                                <span style="color: var(--sys-text-muted, #6B7280); font-size: 0.85rem;">
                                    {{ \Illuminate\Support\Str::limit($row['lastMessage']->body, 60) }}
                                </span>
                            @endif
                        </span>
                        @if($row['unread'] > 0)
                            <span aria-label="{{ $row['unread'] }} message(s) non lu(s)"
                                  style="background-color: #064E5A; color: #fff; border-radius: 999px; min-width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem; padding: 0 6px;">
                                {{ $row['unread'] }}
                            </span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
