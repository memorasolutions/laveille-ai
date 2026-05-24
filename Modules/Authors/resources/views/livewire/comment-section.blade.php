<div>
<section aria-label="Commentaires" class="lv-comments">
    <h2>💬 Commentaires ({{ $comments->total() }})</h2>

    <form wire:submit="submit" class="lv-comment-form" @if($replyingTo) hidden @endif>
        @auth
            <p>Vous commentez en tant que <strong>{{ auth()->user()->name }}</strong></p>
        @else
            <div>
                <label for="guestName">Votre nom</label>
                <input id="guestName" wire:model="guestName" type="text" maxlength="80" required>
                @error('guestName') <span role="alert">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="guestEmail">Votre courriel (jamais affiché)</label>
                <input id="guestEmail" wire:model="guestEmail" type="email" maxlength="255" required>
                @error('guestEmail') <span role="alert">{{ $message }}</span> @enderror
            </div>
        @endauth
        <div>
            <label for="body">Commentaire</label>
            <textarea id="body" wire:model="body" rows="4" required minlength="3" maxlength="5000"></textarea>
            @error('body') <span role="alert">{{ $message }}</span> @enderror
        </div>
        <div style="position:absolute; left:-9999px;" aria-hidden="true">
            <label for="website">Site web</label>
            <input id="website" wire:model="website" type="text" tabindex="-1" autocomplete="off">
        </div>
        <div>
            <input id="consent" wire:model="consent" type="checkbox" required>
            <label for="consent">J'accepte la <a href="/confidentialite" target="_blank">politique de confidentialité</a> (Loi 25)</label>
            @error('consent') <span role="alert">{{ $message }}</span> @enderror
        </div>
        <button type="submit" class="lv-btn-primary">Publier mon commentaire</button>
    </form>

    @forelse($comments as $comment)
        <article id="comment-{{ $comment->id }}" class="lv-comment lv-comment--level-0" wire:key="comment-{{ $comment->id }}">
            <header>
                <span class="lv-comment-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($comment->author_name ?? '?', 0, 2)) }}</span>
                <strong>{{ $comment->author_name ?? 'Anonyme' }}</strong>
                <time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->diffForHumans() }}</time>
            </header>
            <div class="lv-comment-body">{{ $comment->body }}</div>
            <div class="lv-reactions">
                @foreach(\Modules\Authors\Models\AuthorComment::ALLOWED_REACTIONS as $emoji)
                    @php $count = count($comment->reactions[$emoji] ?? []); @endphp
                    <button type="button" wire:click="toggleReaction({{ $comment->id }}, '{{ $emoji }}')" class="lv-reaction-btn" aria-label="Réagir avec {{ $emoji }}">
                        {{ $emoji }} @if($count > 0) <span>{{ $count }}</span> @endif
                    </button>
                @endforeach
            </div>
            <button type="button" wire:click="startReply({{ $comment->id }})" class="lv-comment-reply-btn" aria-label="Répondre à {{ $comment->author_name }}">↩️ Répondre</button>

            @if($replyingTo === $comment->id)
                <form wire:submit="submit" class="lv-comment-form lv-comment-form--reply">
                    <p>En réponse à <strong>{{ $comment->author_name }}</strong></p>
                    @guest
                        <input wire:model="guestName" type="text" placeholder="Votre nom" maxlength="80" required>
                        <input wire:model="guestEmail" type="email" placeholder="Votre courriel" maxlength="255" required>
                    @endguest
                    <textarea wire:model="body" rows="3" placeholder="Votre réponse..." required></textarea>
                    <input wire:model="consent" type="checkbox" id="consent-reply-{{ $comment->id }}" required>
                    <label for="consent-reply-{{ $comment->id }}">J'accepte la politique de confidentialité</label>
                    <button type="submit" class="lv-btn-primary">Envoyer</button>
                    <button type="button" wire:click="cancelReply" class="lv-btn-secondary">Annuler</button>
                </form>
            @endif

            @foreach($comment->children as $child)
                <article id="comment-{{ $child->id }}" class="lv-comment lv-comment--level-1" wire:key="child-{{ $child->id }}">
                    <header>
                        <span class="lv-comment-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($child->author_name ?? '?', 0, 2)) }}</span>
                        <strong>{{ $child->author_name ?? 'Anonyme' }}</strong>
                        <time datetime="{{ $child->created_at->toIso8601String() }}">{{ $child->created_at->diffForHumans() }}</time>
                    </header>
                    <div class="lv-comment-body">{{ $child->body }}</div>
                    <div class="lv-reactions">
                        @foreach(\Modules\Authors\Models\AuthorComment::ALLOWED_REACTIONS as $emoji)
                            @php $count = count($child->reactions[$emoji] ?? []); @endphp
                            <button type="button" wire:click="toggleReaction({{ $child->id }}, '{{ $emoji }}')" class="lv-reaction-btn" aria-label="Réagir avec {{ $emoji }}">
                                {{ $emoji }} @if($count > 0) <span>{{ $count }}</span> @endif
                            </button>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </article>
    @empty
        <p class="lv-empty">Sois le premier à commenter !</p>
    @endforelse

    <div class="lv-pagination">{{ $comments->links() }}</div>
</section>

<style>
.lv-comments { max-width: 720px; margin: 48px auto; padding: 0 16px; }
.lv-comments h2 { color: #0B7285; font-size: 24px; margin-bottom: 24px; }
.lv-comment { margin: 24px 0; padding: 16px; border-radius: 12px; background: #FFFFFF; box-shadow: 0 2px 8px rgba(11,114,133,0.05); }
.lv-comment--level-1 { margin-left: 32px; border-left: 3px solid #0B7285; }
.lv-comment header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.lv-comment-avatar { background: linear-gradient(135deg, #0B7285, #C2410C); color: #FFFFFF; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0; }
.lv-comment time { font-size: 13px; color: #64748B; margin-left: auto; }
.lv-comment-body { line-height: 1.6; white-space: pre-wrap; word-wrap: break-word; color: #1F2937; }
.lv-reactions { display: flex; gap: 8px; margin: 12px 0; flex-wrap: wrap; }
.lv-reaction-btn { min-height: 36px; padding: 6px 12px; background: #F8FAFB; border: 1px solid #E2E8F0; border-radius: 20px; cursor: pointer; display: inline-flex; gap: 4px; align-items: center; font-size: 14px; }
.lv-reaction-btn:hover, .lv-reaction-btn:focus-visible { background: #FFFFFF; border-color: #0B7285; outline: 2px solid #0B7285; outline-offset: 2px; }
.lv-comment-reply-btn { background: transparent; border: none; color: #0B7285; cursor: pointer; padding: 8px 12px; min-height: 36px; font-size: 14px; }
.lv-comment-reply-btn:hover, .lv-comment-reply-btn:focus-visible { text-decoration: underline; outline: 2px solid #0B7285; outline-offset: 2px; }
.lv-comment-form { margin: 24px 0; padding: 16px; background: #F8FAFB; border-radius: 12px; }
.lv-comment-form label { display: block; margin: 8px 0 4px; font-weight: 600; color: #0B7285; font-size: 14px; }
.lv-comment-form input[type="text"], .lv-comment-form input[type="email"], .lv-comment-form textarea { width: 100%; padding: 10px; border: 1px solid #CBD5E1; border-radius: 6px; min-height: 44px; font-family: inherit; box-sizing: border-box; }
.lv-comment-form input:focus-visible, .lv-comment-form textarea:focus-visible { outline: 3px solid #C2410C; outline-offset: 2px; border-color: #0B7285; }
.lv-comment-form input[type="checkbox"] { min-width: 18px; min-height: 18px; margin-right: 8px; vertical-align: middle; }
.lv-comment-form span[role="alert"] { color: #C2410C; font-size: 13px; display: block; margin-top: 4px; }
.lv-btn-primary { background: #0B7285; color: #FFFFFF; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; min-height: 44px; font-weight: 600; }
.lv-btn-primary:hover, .lv-btn-primary:focus-visible { background: #075560; outline: 3px solid #C2410C; outline-offset: 2px; }
.lv-btn-secondary { background: transparent; color: #0B7285; padding: 12px 24px; border: 1px solid #0B7285; border-radius: 8px; cursor: pointer; min-height: 44px; margin-left: 8px; }
.lv-empty { text-align: center; padding: 32px; color: #64748B; }
.lv-pagination { margin: 32px 0; }
@media (max-width: 640px) { .lv-comment--level-1 { margin-left: 16px; } }
</style>
</div>
