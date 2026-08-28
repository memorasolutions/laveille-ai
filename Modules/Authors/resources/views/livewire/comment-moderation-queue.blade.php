<div class="space-y-6">
    <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filtres de statut">
        @foreach(['pending' => 'En attente', 'approved' => 'Approuvés', 'spam' => 'Spam', 'all' => 'Tous'] as $key => $label)
            <button type="button"
                    wire:click="$set('status', '{{ $key }}')"
                    aria-pressed="{{ $status === $key ? 'true' : 'false' }}"
                    style="min-height:44px; padding:8px 16px; border-radius:999px; font-size:14px; font-weight:600; cursor:pointer; border:2px solid #064E5A; {{ $status === $key ? 'background:#064E5A; color:white;' : 'background:transparent; color:#064E5A;' }}"
                    onfocus="this.style.outline='3px solid #9A2A06'; this.style.outlineOffset='2px';"
                    onblur="this.style.outline='none';">
                {{ $label }} ({{ $counts[$key] }})
            </button>
        @endforeach
    </div>

    <div>
        <label for="cmq-search" class="sr-only">Rechercher dans les commentaires</label>
        <input id="cmq-search"
               type="search"
               wire:model.live.debounce.300ms="search"
               placeholder="Rechercher dans le corps du commentaire..."
               aria-label="Rechercher dans les commentaires"
               style="width:100%; min-height:44px; padding:10px 14px; border:2px solid rgba(6,78,90,0.2); border-radius:8px; font-size:14px;">
    </div>

    @if(count($selected) > 0)
        <div class="flex gap-3" role="group" aria-label="Actions groupées">
            <button type="button"
                    wire:click="bulkApprove"
                    wire:confirm="Approuver {{ count($selected) }} commentaire(s) ?"
                    style="min-height:44px; padding:8px 16px; background:#0B7A4B; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600;">
                ✅ Approuver ({{ count($selected) }})
            </button>
            <button type="button"
                    wire:click="bulkSpam"
                    wire:confirm="Marquer {{ count($selected) }} commentaire(s) comme spam ?"
                    style="min-height:44px; padding:8px 16px; background:#9A2A06; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:600;">
                🚫 Spam ({{ count($selected) }})
            </button>
        </div>
    @endif

    @if($comments->isEmpty())
        <div style="text-align:center; padding:48px 0; color:#5A6270;">Aucun commentaire dans cette catégorie.</div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full" style="border-collapse:collapse;">
                <thead>
                    <tr style="background:#F1F5F9; text-align:left;">
                        <th scope="col" style="padding:12px;"><span class="sr-only">Sélection</span></th>
                        <th scope="col" style="padding:12px; font-size:12px; text-transform:uppercase; color:#5A6270;">Auteur</th>
                        <th scope="col" style="padding:12px; font-size:12px; text-transform:uppercase; color:#5A6270;">Commentaire</th>
                        <th scope="col" style="padding:12px; font-size:12px; text-transform:uppercase; color:#5A6270;">Article</th>
                        <th scope="col" style="padding:12px; font-size:12px; text-transform:uppercase; color:#5A6270;">Spam</th>
                        <th scope="col" style="padding:12px; font-size:12px; text-transform:uppercase; color:#5A6270;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comments as $comment)
                        <tr style="border-bottom:1px solid #E2E8F0;">
                            <td style="padding:12px;">
                                <input type="checkbox"
                                       wire:model.live="selected"
                                       value="{{ $comment->id }}"
                                       aria-label="Sélectionner le commentaire {{ $comment->id }}"
                                       style="width:20px; height:20px; cursor:pointer;">
                            </td>
                            <td style="padding:12px;">
                                <strong style="color:#064E5A;">{{ $comment->author_name ?? 'Anonyme' }}</strong><br>
                                <small style="color:#5A6270;">{{ $comment->author_email ?? '–' }}</small>
                            </td>
                            <td style="padding:12px; max-width:320px; font-size:14px; color:#1F2937;">
                                {{ \Illuminate\Support\Str::limit($comment->body, 100) }}
                            </td>
                            <td style="padding:12px; font-size:13px;">
                                @if($comment->commentable && isset($comment->commentable->title))
                                    {{ \Illuminate\Support\Str::limit($comment->commentable->title, 40) }}
                                @else
                                    <span style="color:#5A6270;">–</span>
                                @endif
                            </td>
                            <td style="padding:12px;">
                                @php $score = (int) ($comment->spam_score ?? 0); @endphp
                                <span style="padding:2px 10px; border-radius:999px; font-size:12px; font-weight:600; {{ $score >= 70 ? 'background:#FEE2E2; color:#991B1B;' : ($score > 30 ? 'background:#FEF3C7; color:#92400E;' : 'background:#DCFCE7; color:#166534;') }}">
                                    {{ $score }}
                                </span>
                            </td>
                            <td style="padding:12px; white-space:nowrap;">
                                @if(! $comment->approved_at)
                                    <button type="button" wire:click="approve({{ $comment->id }})" aria-label="Approuver le commentaire {{ $comment->id }}"
                                            style="min-height:44px; min-width:44px; padding:6px 10px; background:#0B7A4B; color:white; border:none; border-radius:6px; cursor:pointer; margin-right:4px;">✅</button>
                                @endif
                                <button type="button" wire:click="markSpam({{ $comment->id }})" aria-label="Marquer le commentaire {{ $comment->id }} comme spam"
                                        style="min-height:44px; min-width:44px; padding:6px 10px; background:#9A2A06; color:white; border:none; border-radius:6px; cursor:pointer; margin-right:4px;">🚫</button>
                                <button type="button" wire:click="deleteSoft({{ $comment->id }})" wire:confirm="Supprimer ce commentaire ?" aria-label="Supprimer le commentaire {{ $comment->id }}"
                                        style="min-height:44px; min-width:44px; padding:6px 10px; background:#475569; color:white; border:none; border-radius:6px; cursor:pointer;">🗑️</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            @include('backoffice::partials.infinite-scroll', ['paginator' => $comments])
        </div>
    @endif
</div>
