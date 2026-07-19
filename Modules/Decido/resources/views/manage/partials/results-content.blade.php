{{-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca --}}
{{-- Partial extrait de manage/results.blade.php (2026-07-19) : contenu pur de la page de gestion
     d'un sondage (résumé, drill-down, grille croisée, partage/export, clôture), sans wrapper de
     mise en page - le wrapper diffère selon le contexte d'accès (voir results.blade.php) :
     propriétaire connecté -> layout "Mon espace" (sidebar) ; délégué anonyme via jeton -> layout
     public sans sidebar. Attend $poll, $options, $adminToken. --}}
                <h1 class="h2 mb-4">Résultats - {{ $poll->title }}</h1>

                @if(session('admin_token_plain'))
                    @php
                        $adminUrl = route('decido.manage', ['poll' => $poll->public_id, 'adminToken' => session('admin_token_plain')]);
                    @endphp
                    <div class="alert alert-warning d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
                        <div>
                            {{-- Message reformulé (demande utilisateur 2026-07-18) : "il ne sera plus jamais
                                 réaffiché" était trompeur pour TOI le créateur - decido.store exige d'être
                                 connecté (middleware auth), et PollManageController::authorizeManage() laisse
                                 déjà passer le créateur connecté avec n'importe quel jeton (Auth::id() ===
                                 $poll->creator_id) : tu retrouveras toujours cette page via "Mes sondages".
                                 Ce lien précis (avec le jeton en clair) ne sert vraiment qu'à DÉLÉGUER l'accès
                                 de gestion à un co-organisateur qui n'a pas de compte. --}}
                            <strong>Lien de gestion à partager avec un co-organisateur si besoin :</strong><br>
                            <code class="d-block mt-1" style="word-break: break-all;">{{ $adminUrl }}</code>
                            <small class="text-muted d-block mt-1">Toi, tu retrouveras toujours ce sondage via <a href="{{ route('decido.index') }}">Mes sondages</a>.</small>
                        </div>
                        <button type="button" class="ct-btn ct-btn-outline ct-btn-sm flex-shrink-0"
                                aria-label="Copier le lien de gestion"
                                x-data="{ copied: false }"
                                x-on:click="copyToClipboard('{{ $adminUrl }}', 'Lien de gestion copié').then(ok => { if (ok) { copied = true; setTimeout(() => copied = false, 2000) } })">
                            <span x-show="!copied">Copier</span>
                            <span x-show="copied" aria-hidden="true">✓ Copié !</span>
                        </button>
                    </div>
                @endif

                @php
                    // Décido round 6 (skill /100) : reclé par voter_token (identifiant réellement unique
                    // d'un votant) plutôt que par voter_pseudonym (texte libre) - deux votants distincts
                    // au même pseudonyme (homonymes) voyaient auparavant un de leurs deux votes
                    // silencieusement écrasé/disparu du résumé et du tableau croisé.
                    $isDateType = $poll->type->value === 'date';
                    $isYesNoMaybe = $poll->vote_mode->value === 'yes_no_maybe';
                    $totalVoters = $options->flatMap(fn ($opt) => $opt->votes)->unique('voter_token')->count();

                    $optionStats = $options->map(function ($option) use ($isYesNoMaybe, $totalVoters) {
                        $votes = $option->votes;
                        $yes = $isYesNoMaybe ? $votes->where('value', 'yes')->count() : $votes->where('value', 'selected')->count();
                        $maybe = $isYesNoMaybe ? $votes->where('value', 'maybe')->count() : 0;
                        $no = $isYesNoMaybe ? $votes->where('value', 'no')->count() : 0;
                        $noResponse = $isYesNoMaybe ? max(0, $totalVoters - $yes - $maybe - $no) : 0;

                        return (object) [
                            'option' => $option,
                            'yes' => $yes,
                            'maybe' => $maybe,
                            'no' => $no,
                            'noResponse' => $noResponse,
                        ];
                    });

                    $bestCount = $optionStats->pluck('yes')->max() ?? 0;
                    $bestOptions = $bestCount > 0 ? $optionStats->where('yes', $bestCount)->values() : collect();
                    $otherOptionsCount = $options->count() - $bestOptions->count();

                    $groupedByDay = $isDateType
                        ? $options->groupBy(fn ($opt) => $opt->starts_at ? \Carbon\Carbon::parse($opt->starts_at->format('Y-m-d H:i:s'), 'UTC')->timezone($poll->timezone)->locale('fr')->isoFormat('dddd D MMMM') : null)
                        : null;

                    $voters = $options->flatMap(fn ($opt) => $opt->votes)
                        ->unique('voter_token')
                        ->map(fn ($vote) => (object) ['token' => $vote->voter_token, 'name' => $vote->voter_pseudonym])
                        ->values();

                    $matrix = [];
                    foreach ($options as $option) {
                        foreach ($option->votes as $vote) {
                            $matrix[$option->id][$vote->voter_token] = $vote->value;
                        }
                    }
                @endphp

                <div x-data="{ openDrill: null }">
                    <h2 class="h4 mb-3">{{ $isDateType ? 'Meilleurs créneaux' : 'Options les mieux classées' }}</h2>

                    @if($totalVoters === 0)
                        <div class="alert alert-light border">
                            Aucun vote pour l'instant.
                        </div>
                    @elseif($bestOptions->isEmpty())
                        {{-- Round 27 (revue adversariale) : $bestCount peut être 0 alors que des
                             votants ont déjà répondu (ex. tout le monde a coché "Peut-être"/"Non"
                             sans jamais cocher "Oui") - la section restait vide sans aucun message,
                             confus pour l'organisateur. --}}
                        <div class="alert alert-light border">
                            @if($totalVoters === 1)
                                1 personne a voté, mais aucun créneau n'a encore de réponse « Oui ». Consulte la grille détaillée ci-dessous pour voir la répartition des réponses.
                            @else
                                {{ $totalVoters }} personnes ont voté, mais aucun créneau n'a encore de réponse « Oui ». Consulte la grille détaillée ci-dessous pour voir la répartition des réponses.
                            @endif
                        </div>
                    @else
                        @foreach($bestOptions as $stat)
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h3 class="h5 mb-2">{{ $stat->option->label }}</h3>
                                    @if($isYesNoMaybe)
                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                            <span class="badge" style="background-color: var(--sys-success-bg); color: var(--sys-success); border: 1px solid var(--sys-success); display: inline-flex; align-items: center; gap: 4px;">
                                                ✓ {{ $stat->yes }} oui
                                            </span>
                                            <span class="badge" style="background-color: var(--sys-warning-bg); color: var(--sys-warning); border: 1px solid var(--sys-warning); display: inline-flex; align-items: center; gap: 4px;">
                                                ? {{ $stat->maybe }} peut-être
                                            </span>
                                            <span class="badge" style="background-color: #fff; color: var(--sys-danger); border: 1px solid var(--sys-danger); display: inline-flex; align-items: center; gap: 4px;">
                                                ✕ {{ $stat->no }} non
                                            </span>
                                        </div>
                                        <p class="text-muted small">{{ $stat->noResponse }} sans réponse</p>
                                    @else
                                        <div class="mb-2">
                                            <span class="badge" style="background-color: var(--sys-success-bg); color: var(--sys-success); border: 1px solid var(--sys-success); display: inline-flex; align-items: center; gap: 4px;">
                                                ✓ {{ $stat->yes }} sur {{ $totalVoters }} participants
                                            </span>
                                        </div>
                                    @endif
                                    <button type="button" class="ct-btn ct-btn-outline ct-btn-sm mt-2"
                                            x-on:click="openDrill === {{ $stat->option->id }} ? openDrill = null : openDrill = {{ $stat->option->id }}"
                                            x-text="openDrill === {{ $stat->option->id }} ? 'Masquer les réponses' : 'Voir qui a répondu'"
                                            x-bind:aria-expanded="(openDrill === {{ $stat->option->id }}).toString()"
                                            aria-controls="decido-drill-{{ $stat->option->id }}">
                                        Voir qui a répondu
                                    </button>
                                    <div id="decido-drill-{{ $stat->option->id }}" x-show="openDrill === {{ $stat->option->id }}" x-cloak class="mt-3 pt-3 border-top">
                                        @if($stat->option->votes->isEmpty())
                                            <p class="text-muted small">Aucun vote pour cette option.</p>
                                        @else
                                            @foreach($stat->option->votes as $vote)
                                                <div class="d-flex justify-content-between small py-1">
                                                    <span>{{ $vote->voter_pseudonym }}</span>
                                                    <span>
                                                        @php
                                                            $label = match($vote->value) {
                                                                'yes' => 'Oui',
                                                                'maybe' => 'Peut-être',
                                                                'no' => 'Non',
                                                                'selected' => 'Sélectionné',
                                                                default => $vote->value,
                                                            };
                                                        @endphp
                                                        {{ $label }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if($otherOptionsCount > 0)
                            <p class="text-muted small">+ {{ $otherOptionsCount }} autre(s) option(s) disponible(s) - <a href="#decido-comparaison-complete">voir le détail complet ci-dessous</a>.</p>
                        @endif
                    @endif
                </div>

                <details id="decido-comparaison-complete" class="mt-4 border rounded p-3">
                    <summary class="h5" style="cursor: pointer;">Comparer toutes les réponses ({{ $voters->count() }} participant(s) × {{ $options->count() }} option(s))</summary>
                    @if($voters->isEmpty())
                        <p class="text-muted">Aucun vote pour l'instant.</p>
                    @else
                        <div class="table-responsive mt-3" style="max-height: 70vh; overflow-y: auto;">
                            <table class="table table-bordered table-sm align-middle">
                                <caption class="visually-hidden">Tableau croisé des réponses par participant et par option</caption>
                                @if($isDateType)
                                    <thead>
                                        <tr>
                                            <th></th>
                                            @foreach($groupedByDay as $dayName => $dayOptions)
                                                <th colspan="{{ $dayOptions->count() }}" scope="colgroup" class="text-center" style="background-color: var(--c-primary); color: #fff;">
                                                    {{ $dayName }}
                                                </th>
                                            @endforeach
                                        </tr>
                                        <tr>
                                            <th scope="col" style="position: sticky; left: 0; background: #fff; z-index: 2;">Participant</th>
                                            @foreach($groupedByDay as $dayOptions)
                                                @foreach($dayOptions as $option)
                                                    <th scope="col" class="text-center small">{{ \Carbon\Carbon::parse($option->starts_at->format('Y-m-d H:i:s'), 'UTC')->timezone($poll->timezone)->isoFormat('H[h]mm') }}</th>
                                                @endforeach
                                            @endforeach
                                        </tr>
                                    </thead>
                                @else
                                    <thead>
                                        <tr>
                                            <th scope="col" style="position: sticky; left: 0; background: #fff; z-index: 2;">Participant</th>
                                            @foreach($options as $option)
                                                <th scope="col" class="text-center">{{ $option->label }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                @endif
                                <tbody>
                                    @foreach($voters as $voter)
                                        <tr>
                                            <th scope="row" style="position: sticky; left: 0; background: #fff; z-index: 1;">{{ $voter->name }}</th>
                                            @if($isDateType)
                                                @foreach($groupedByDay as $dayOptions)
                                                    @foreach($dayOptions as $option)
                                                        @php
                                                            $value = $matrix[$option->id][$voter->token] ?? null;
                                                        @endphp
                                                        <td class="text-center">
                                                            @if($value === 'yes' || $value === 'selected')
                                                                <span aria-label="{{ $voter->name }} : oui" style="color: var(--sys-success);">✓</span>
                                                            @elseif($value === 'maybe')
                                                                <span aria-label="{{ $voter->name }} : peut-être" style="color: var(--sys-warning);">?</span>
                                                            @elseif($value === 'no')
                                                                <span aria-label="{{ $voter->name }} : non" style="color: var(--sys-danger);">✕</span>
                                                            @else
                                                                <span aria-label="{{ $voter->name }} : sans réponse" class="text-muted">–</span>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                @endforeach
                                            @else
                                                @foreach($options as $option)
                                                    @php
                                                        $value = $matrix[$option->id][$voter->token] ?? null;
                                                    @endphp
                                                    <td class="text-center">
                                                        @if($value === 'yes' || $value === 'selected')
                                                            <span aria-label="{{ $voter->name }} : oui" style="color: var(--sys-success);">✓</span>
                                                        @elseif($value === 'maybe')
                                                            <span aria-label="{{ $voter->name }} : peut-être" style="color: var(--sys-warning);">?</span>
                                                        @elseif($value === 'no')
                                                            <span aria-label="{{ $voter->name }} : non" style="color: var(--sys-danger);">✕</span>
                                                        @else
                                                            <span aria-label="{{ $voter->name }} : sans réponse" class="text-muted">–</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th scope="row" style="position: sticky; left: 0; background: #fff;">Total</th>
                                        @if($isDateType)
                                            @foreach($groupedByDay as $dayOptions)
                                                @foreach($dayOptions as $option)
                                                    @php
                                                        $stat = $optionStats->firstWhere('option.id', $option->id);
                                                    @endphp
                                                    <td class="text-center small">{{ $stat->yes }}✓</td>
                                                @endforeach
                                            @endforeach
                                        @else
                                            @foreach($options as $option)
                                                @php
                                                    $stat = $optionStats->firstWhere('option.id', $option->id);
                                                @endphp
                                                <td class="text-center small">{{ $stat->yes }}✓</td>
                                            @endforeach
                                        @endif
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </details>

                <div class="mt-5 p-3 border rounded">
                    <h3 class="h5 mb-3">Partage et export</h3>
                    @php
                        // Décido (2026-07-19, demande utilisateur) : mailto: plutot qu'un envoi serveur -
                        // ouvre le client courriel de L'ORGANISATEUR (Gmail/Outlook/Mail.app), c'est lui qui
                        // envoie depuis sa propre adresse. Zéro infrastructure d'envoi, zéro email collecté
                        // côté plateforme (aucun enjeu Loi 25/RGPD), comportement identique au vrai Framadate.
                        $mailInviteSubject = 'Sondage : ' . $poll->title;
                        $mailInviteBody = "Bonjour,\n\nVous êtes invité(e) à répondre à ce sondage :\n" . $poll->title . "\n\n" . $poll->share_url . "\n\nMerci de votre participation !";

                        // Regroupement dans le composant DRY action-menu (2026-07-19, demande utilisateur) :
                        // uniquement les actions de copie/téléchargement/navigation, mêmes conditions @if
                        // qu'avant (rien de la logique métier - routes, adminToken, statut du sondage - n'a
                        // changé). Restent HORS menu, à dessein : x-core::mailto-share-btn (composant Blade
                        // dédié avec son propre rendu, pas un simple lien que le menu sait afficher), le
                        // bouton "Télécharger le QR code" (l'attribut download="" est ce qui force le
                        // téléchargement du PNG puisque PollManageController::qrCode() répond en
                        // Content-Disposition: inline - le composant action-menu ne supporte pas cet
                        // attribut, donc il reste collé à son aperçu image juste en dessous), et le
                        // formulaire de création de lien court (nécessite un champ texte, pas un simple clic).
                        $shareActions = [
                            ['label' => 'Copier le lien public', 'icon' => 'copy', 'alpineClick' => "open = false; copyToClipboard('{$poll->share_url}', 'Lien public copié')"],
                        ];

                        if ($poll->getShortUrlString()) {
                            $shareActions[] = ['label' => 'Copier le lien court', 'icon' => 'copy', 'alpineClick' => "open = false; copyToClipboard('{$poll->getShortUrlString()}', 'Lien court copié')"];

                            if (auth()->check() && auth()->id() === $poll->creator_id) {
                                // Réservé au créateur connecté : UserShortUrlController::edit() vérifie
                                // la propriété du lien, un token-only delegate n'y aurait pas accès.
                                $shareActions[] = ['label' => 'Options avancées du lien court', 'icon' => 'external-link', 'url' => route('shorturl.user.edit', ['short_url' => $poll->shortUrl]), 'target' => '_blank'];
                            }
                        }

                        $shareActions[] = ['divider' => true];
                        $shareActions[] = ['label' => 'Télécharger en CSV', 'icon' => 'download', 'url' => route('decido.export.csv', ['poll' => $poll->public_id, 'adminToken' => $adminToken])];

                        if ($poll->status->value === 'closed' && $poll->final_option_id) {
                            $shareActions[] = ['label' => 'Télécharger en ICS', 'icon' => 'calendar-plus', 'url' => route('decido.export.ics', ['poll' => $poll->public_id, 'adminToken' => $adminToken])];
                        }
                    @endphp
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                        <span class="text-muted">Lien public :</span>
                        <code>{{ $poll->share_url }}</code>
                        <x-core::mailto-share-btn :subject="$mailInviteSubject" :body="$mailInviteBody" label="Envoyer par courriel" />
                        @include('core::components.action-menu', ['actions' => $shareActions])
                    </div>

                    @if($poll->getShortUrlString())
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <span class="text-muted">Lien court :</span>
                            <code>{{ $poll->getShortUrlString() }}</code>
                        </div>
                    @else
                        {{-- Slug personnalisé (demande utilisateur 2026-07-18) : connectés seulement (Décido
                             exige déjà d'être connecté pour créer un sondage), réutilise EXACTEMENT la
                             validation du module ShortUrl (alpha_dash, unique, mots réservés) - voir
                             PollManageController::createShortLink(). Champ facultatif : slug aléatoire
                             généré si laissé vide. Domaine affiché en texte fixe (pas de menu déroulant) :
                             0 domaine secondaire n'est configuré actuellement sur ce site. --}}
                        <form method="POST" action="{{ route('decido.shortlink', ['poll' => $poll->public_id, 'adminToken' => $adminToken]) }}" class="mb-3">
                            @csrf
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <span class="text-muted small">{{ parse_url(config('app.url'), PHP_URL_HOST) }}/s/</span>
                                <input type="text" name="slug" maxlength="50" pattern="[a-zA-Z0-9_-]*"
                                       placeholder="perso (facultatif)"
                                       aria-label="Slug personnalisé du lien court (facultatif)"
                                       class="form-control form-control-sm" style="max-width:180px;"
                                       value="{{ old('slug') }}">
                                <button type="submit" class="ct-btn ct-btn-outline ct-btn-sm">
                                    Créer un lien court
                                </button>
                            </div>
                            @error('shortlink')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                            @error('slug')
                                <small class="text-danger d-block mt-1">{{ $message }}</small>
                            @enderror
                        </form>
                    @endif

                    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                        <img src="{{ route('decido.qr', ['poll' => $poll->public_id, 'adminToken' => $adminToken]) }}"
                             alt="QR code du sondage {{ $poll->title }}" width="140" height="140" loading="lazy"
                             class="border rounded">
                        <a href="{{ route('decido.qr', ['poll' => $poll->public_id, 'adminToken' => $adminToken]) }}"
                           download="qr-decido-{{ $poll->public_id }}.png"
                           class="ct-btn ct-btn-outline ct-btn-sm">
                            Télécharger le QR code
                        </a>
                    </div>
                </div>

                @if($poll->status->value === 'open')
                    {{-- Clarifié (2026-07-19, signalé par l'utilisateur : "pas clair ça, j'imagine que
                         les utilisateurs ne comprendront pas") : la section se limitait à une liste de
                         radios sans texte explicatif ni décompte de votes visible à côté, et le titre
                         H3 dupliquait mot pour mot le libellé du bouton - rien n'indiquait CE QUE fait
                         "clôturer" (verrouille le vote, fixe l'option finale publique, débloque l'export
                         ICS) ni pourquoi choisir un créneau plutôt qu'un autre. Ajout : phrase d'intro
                         explicite, badge "✓ X oui" par option (réutilise $optionStats déjà calculé
                         plus haut, pas de nouvelle requête), pré-sélection du/des créneau(x) gagnant(s)
                         ($bestOptions, déjà calculé) pour réduire la friction sans forcer le choix, et
                         libellé de bouton différencié du titre de section. --}}
                    <div class="mt-5 p-3 border rounded bg-light">
                        <h3 class="h5 mb-2">Clôturer le sondage</h3>
                        <p class="text-muted small mb-3">
                            Choisis le créneau final retenu pour la rencontre, puis confirme. Cette action ferme définitivement le vote : ce créneau devient l'option finale affichée aux participants (lien public) et débloque l'export ICS (ajout à un calendrier).
                        </p>
                        <form method="POST" action="{{ route('decido.close', ['poll' => $poll->public_id, 'adminToken' => $adminToken]) }}">
                            @csrf
                            @foreach($options as $option)
                                @php $stat = $optionStats->firstWhere('option.id', $option->id); @endphp
                                <div class="form-check mb-2 d-flex align-items-center gap-2 flex-wrap">
                                    <input class="form-check-input" type="radio" name="final_option_id" id="opt_{{ $option->id }}" value="{{ $option->id }}"
                                        @if($bestOptions->contains('option.id', $option->id)) checked @endif required>
                                    <label class="form-check-label" for="opt_{{ $option->id }}">{{ $option->label }}</label>
                                    @if($stat && $stat->yes > 0)
                                        <span class="badge" style="background-color: var(--sys-success-bg); color: var(--sys-success); border: 1px solid var(--sys-success); display: inline-flex; align-items: center; gap: 4px;">
                                            ✓ {{ $stat->yes }} {{ $isYesNoMaybe ? 'oui' : ($stat->yes > 1 ? 'participants' : 'participant') }}
                                        </span>
                                    @endif
                                </div>
                            @endforeach
                            <x-core::button type="submit" variant="primary" class="mt-2">Confirmer et clôturer le sondage</x-core::button>
                        </form>
                    </div>
                @elseif($poll->status->value === 'closed' && $poll->final_option_id)
                    <div class="mt-5 p-3 border rounded" style="border-color: var(--c-primary); background-color: rgba(6, 78, 90, 0.05);">
                        <h3 class="h5 mb-2" style="color: var(--c-primary);">Option finale choisie</h3>
                        <p class="mb-0">{{ $options->firstWhere('id', $poll->final_option_id)?->label }}</p>
                    </div>
                @endif
