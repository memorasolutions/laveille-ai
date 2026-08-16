<?php

declare(strict_types=1);

namespace Modules\Decido\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\View\View as ViewContract;
use Modules\Decido\Models\Poll;
use Modules\Decido\Models\PollComment;
use Modules\Decido\Models\PollDecline;
use Modules\Decido\Models\PollVote;
use Modules\Decido\Services\PollExportService;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PublicPollController extends Controller
{
    public function show(Request $request, string $slug): ViewContract
    {
        $poll = Poll::findByShareIdentifier($slug);
        if (! $poll || $poll->status->value === 'draft') {
            abort(404);
        }

        $voterToken = $request->cookie('decido_voter_'.$poll->public_id);
        $existingVotes = [];
        $existingDecline = false;
        if ($voterToken) {
            $existingVotes = PollVote::where('voter_token', $voterToken)
                ->where('poll_id', $poll->id)
                ->pluck('value', 'option_id')
                ->toArray();

            // LOT 1, point 3 : un votant qui revient sur la page doit voir qu'il a déjà déclaré
            // qu'aucune date ne lui convenait, au même titre que l'alerte "tu as déjà voté".
            $existingDecline = PollDecline::where('voter_token', $voterToken)
                ->where('poll_id', $poll->id)
                ->exists();
        }

        // LOT 2 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 5) : le commentaire du
        // votant courant (pour pré-remplir le champ s'il revient modifier sa réponse), et TOUS les
        // commentaires du sondage (visibles publiquement à côté des pseudonymes - un commentaire
        // que personne ne lit ne sert à rien, voir vote.blade.php).
        $existingComment = null;
        if ($voterToken) {
            $existingComment = PollComment::where('voter_token', $voterToken)
                ->where('poll_id', $poll->id)
                ->value('comment');
        }

        $poll->load(['options.votes', 'comments']);

        return View::make('decido::public.vote', [
            'poll' => $poll,
            'options' => $poll->options,
            'voterToken' => $voterToken,
            'existingVotes' => $existingVotes,
            'existingDecline' => $existingDecline,
            'existingComment' => $existingComment,
            'comments' => $poll->comments,
        ]);
    }

    public function vote(Request $request, string $slug): RedirectResponse
    {
        $poll = Poll::findByShareIdentifier($slug);
        if (! $poll || $poll->status->value !== 'open') {
            abort(404);
        }

        $optionIds = $poll->options->pluck('id')->toArray();

        $rules = [
            'voter_pseudonym' => ['required', 'string', 'max:100'],
            // LOT 2, point 5 : commentaire libre FACULTATIF, un par (sondage, votant). 280
            // caractères (registre "court", même ordre de grandeur qu'un tweet) - large marge
            // pour "je peux seulement après 18h" tout en dissuadant un pavé de texte à modérer.
            'comment' => ['nullable', 'string', 'max:280'],
        ];

        switch ($poll->vote_mode->value) {
            case 'yes_no_maybe':
                $rules['votes'] = ['required', 'array', 'min:1'];
                foreach ($optionIds as $id) {
                    $rules["votes.{$id}"] = ['sometimes', 'in:yes,maybe,no'];
                }
                break;
            case 'single_choice':
                $rules['votes'] = ['required', 'integer', 'in:'.implode(',', $optionIds)];
                break;
            case 'approval':
                $rules['votes'] = ['required', 'array'];
                $rules['votes.*'] = ['required', 'integer', 'in:'.implode(',', $optionIds)];
                break;
        }

        $validated = $request->validate($rules);

        $cookieName = 'decido_voter_'.$poll->public_id;
        $voterToken = $request->cookie($cookieName) ?? (string) Str::uuid();
        $voterPseudonym = $validated['voter_pseudonym'];
        // LOT 2, point 5 : nettoyé de toute balise HTML AVANT écriture (défense en profondeur, en
        // plus de l'échappement Blade {{ }} systématique à l'affichage - jamais {!! !!} sur ce
        // champ). Aucun linkifier n'est appliqué nulle part sur ce texte : une URL collée par un
        // participant reste du texte brut, jamais un lien cliquable auto-généré.
        $comment = isset($validated['comment']) ? trim(strip_tags($validated['comment'])) : '';

        // Décido round 6 (skill /100) : le statut n'était vérifié qu'une fois en tout début de
        // méthode, sans verrou - un vote soumis dans la fenêtre entre cette vérification et
        // l'écriture pouvait être accepté silencieusement même si l'organisateur venait de
        // clôturer le sondage entre-temps (TOCTOU). lockForUpdate + re-vérification DANS la
        // transaction éliminent cette fenêtre de course.
        DB::transaction(function () use ($poll, $optionIds, $validated, $voterToken, $voterPseudonym, $comment) {
            $lockedPoll = Poll::whereKey($poll->id)->lockForUpdate()->first();
            if (! $lockedPoll || $lockedPoll->status->value !== 'open') {
                abort(404);
            }

            if ($poll->vote_mode->value === 'yes_no_maybe') {
                $answeredOptionIds = array_map('intval', array_keys($validated['votes']));
                foreach ($validated['votes'] as $optionId => $value) {
                    PollVote::updateOrCreate(
                        ['option_id' => (int) $optionId, 'voter_token' => $voterToken],
                        ['poll_id' => $poll->id, 'voter_pseudonym' => $voterPseudonym, 'value' => $value]
                    );
                }

                // LOT 2 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 4) : DÉFAUT CORRIGÉ
                // ICI - un créneau omis de cette soumission (le votant a retiré son choix) doit
                // voir son ancien vote SUPPRIMÉ, pas conservé. Avant ce fix, seuls les créneaux
                // PRÉSENTS dans $validated['votes'] étaient upsertés ci-dessus ; un vote plus
                // ancien sur un créneau désormais absent de la requête survivait indéfiniment en
                // base - le participant croyait avoir effacé son choix, la donnée disait le
                // contraire. Portée STRICTEMENT limitée à ($optionIds - options de CE sondage
                // uniquement) × ($voterToken - le votant COURANT, identifié par son cookie chiffré,
                // jamais un jeton fourni en champ de formulaire - round 24, skill /100) : aucun
                // autre votant ni aucun autre sondage ne peut être affecté, même par une requête
                // forgée. Même pattern que single_choice/approval ci-dessous, qui suppriment déjà
                // symétriquement les options désélectionnées.
                PollVote::where('voter_token', $voterToken)
                    ->whereIn('option_id', $optionIds)
                    ->whereNotIn('option_id', $answeredOptionIds)
                    ->delete();
            } elseif ($poll->vote_mode->value === 'single_choice') {
                $selectedOptionId = (int) $validated['votes'];
                PollVote::updateOrCreate(
                    ['option_id' => $selectedOptionId, 'voter_token' => $voterToken],
                    ['poll_id' => $poll->id, 'voter_pseudonym' => $voterPseudonym, 'value' => 'selected']
                );

                PollVote::where('voter_token', $voterToken)
                    ->whereIn('option_id', $optionIds)
                    ->where('option_id', '!=', $selectedOptionId)
                    ->delete();
            } elseif ($poll->vote_mode->value === 'approval') {
                $selectedOptionIds = array_map('intval', $validated['votes']);
                foreach ($selectedOptionIds as $optionId) {
                    PollVote::updateOrCreate(
                        ['option_id' => $optionId, 'voter_token' => $voterToken],
                        ['poll_id' => $poll->id, 'voter_pseudonym' => $voterPseudonym, 'value' => 'selected']
                    );
                }

                PollVote::where('voter_token', $voterToken)
                    ->whereIn('option_id', $optionIds)
                    ->whereNotIn('option_id', $selectedOptionIds)
                    ->delete();
            }

            // LOT 1, point 3 : un vote normal annule un refus global déclaré précédemment par ce
            // même votant (mutuelle exclusivité, voir le commentaire de la migration
            // decido_poll_declines) - sinon l'organisateur verrait à la fois des choix précis ET
            // un refus pour la même personne.
            PollDecline::where('poll_id', $poll->id)->where('voter_token', $voterToken)->delete();

            // LOT 2, point 5 : UN commentaire par (sondage, votant) - un commentaire vide (champ
            // laissé vide à une nouvelle soumission) EFFACE l'ancien, comportement symétrique au
            // fix du point 4 ci-dessus : rien ne doit "survivre" silencieusement à une soumission
            // qui l'omet.
            if ($comment !== '') {
                PollComment::updateOrCreate(
                    ['poll_id' => $poll->id, 'voter_token' => $voterToken],
                    ['voter_pseudonym' => $voterPseudonym, 'comment' => $comment]
                );
            } else {
                PollComment::where('poll_id', $poll->id)->where('voter_token', $voterToken)->delete();
            }
        });

        return Redirect::back()
            ->withCookie(Cookie::make($cookieName, $voterToken, 525600, null, null, null, true))
            ->with('success', 'Votre vote a été enregistré.');
    }

    /**
     * LOT 1, point 3 : "aucune date ne me convient", en un geste, distinct d'un simple silence.
     * Même pattern TOCTOU-safe (lockForUpdate + re-vérification du statut) que vote() ci-dessus.
     */
    public function decline(Request $request, string $slug): RedirectResponse
    {
        $poll = Poll::findByShareIdentifier($slug);
        if (! $poll || $poll->status->value !== 'open') {
            abort(404);
        }

        $validated = $request->validate([
            'voter_pseudonym' => ['required', 'string', 'max:100'],
            // LOT 2, point 5 : même champ facultatif que vote() - un votant qui décline reste
            // libre de laisser un mot ("je participe à distance si jamais une date est retenue").
            'comment' => ['nullable', 'string', 'max:280'],
        ]);

        $cookieName = 'decido_voter_'.$poll->public_id;
        $voterToken = $request->cookie($cookieName) ?? (string) Str::uuid();
        $voterPseudonym = $validated['voter_pseudonym'];
        $optionIds = $poll->options->pluck('id')->toArray();
        // Même traitement anti-injection que vote() - voir son commentaire pour le détail.
        $comment = isset($validated['comment']) ? trim(strip_tags($validated['comment'])) : '';

        DB::transaction(function () use ($poll, $optionIds, $voterToken, $voterPseudonym, $comment) {
            $lockedPoll = Poll::whereKey($poll->id)->lockForUpdate()->first();
            if (! $lockedPoll || $lockedPoll->status->value !== 'open') {
                abort(404);
            }

            // Mutuellement exclusif avec les votes normaux (voir commentaire de la migration
            // decido_poll_declines) : un refus global remplace tout choix précis déjà fait par ce
            // votant, pour ne jamais afficher des réponses contradictoires à l'organisateur.
            PollVote::where('voter_token', $voterToken)
                ->whereIn('option_id', $optionIds)
                ->delete();

            PollDecline::updateOrCreate(
                ['poll_id' => $poll->id, 'voter_token' => $voterToken],
                ['voter_pseudonym' => $voterPseudonym]
            );

            // LOT 2, point 5 : même règle "vide efface l'ancien" que vote() ci-dessus.
            if ($comment !== '') {
                PollComment::updateOrCreate(
                    ['poll_id' => $poll->id, 'voter_token' => $voterToken],
                    ['voter_pseudonym' => $voterPseudonym, 'comment' => $comment]
                );
            } else {
                PollComment::where('poll_id', $poll->id)->where('voter_token', $voterToken)->delete();
            }
        });

        return Redirect::back()
            ->withCookie(Cookie::make($cookieName, $voterToken, 525600, null, null, null, true))
            ->with('success', "Ta réponse a été enregistrée : aucune date ne te convient. L'organisateur en sera informé.");
    }

    /**
     * LOT 2 (docs/specs/2026-08-16-decido-reste-a-faire.md, point 4) : le fix ci-dessus dans
     * vote() ne couvre que le cas "un créneau omis à une nouvelle soumission" - ceci est le geste
     * EXPLICITE et IRRÉVERSIBLE d'effacer TOUTE la participation du votant à CE sondage (votes +
     * déclin + commentaire), déclenché depuis la page publique avec confirmation (jamais une
     * fenêtre native du navigateur - voir vote.blade.php, réutilise x-core::confirm-modal via
     * l'attribut data-confirm déjà câblé globalement par FrontTheme/layouts/master.blade.php).
     *
     * SÉCURITÉ (le point le plus important de ce lot) : portée STRICTEMENT limitée au voter_token
     * lu depuis le COOKIE CHIFFRÉ du demandeur ($request->cookie(), jamais un jeton fourni en
     * champ de formulaire ou en paramètre de route) - même garantie que vote()/decline(), prouvée
     * par le round 24 (skill /100) : un voter_token deviné ou volé ne peut rien sans le cookie
     * signé par APP_KEY. Les 3 suppressions sont en plus bornées à $poll->id (ou aux
     * $optionIds de CE seul sondage pour PollVote) : aucune autre personne ni aucun autre sondage
     * ne peut être touché. Le cookie est ensuite oublié : plus aucune donnée de ce votant ne
     * subsiste, il n'y a donc plus de raison de le ré-identifier au prochain chargement.
     */
    public function clearVote(Request $request, string $slug): RedirectResponse
    {
        $poll = Poll::findByShareIdentifier($slug);
        if (! $poll || $poll->status->value === 'draft') {
            abort(404);
        }

        $cookieName = 'decido_voter_'.$poll->public_id;
        $voterToken = $request->cookie($cookieName);

        if (! $voterToken) {
            return Redirect::back()->with('success', 'Aucune participation trouvée à effacer sous ce lien.');
        }

        DB::transaction(function () use ($poll, $voterToken) {
            $optionIds = $poll->options()->pluck('id')->toArray();

            PollVote::where('voter_token', $voterToken)
                ->whereIn('option_id', $optionIds)
                ->delete();

            PollDecline::where('poll_id', $poll->id)->where('voter_token', $voterToken)->delete();

            PollComment::where('poll_id', $poll->id)->where('voter_token', $voterToken)->delete();
        });

        return Redirect::back()
            ->withCookie(Cookie::forget($cookieName))
            ->with('success', 'Ta participation à ce sondage a été entièrement effacée.');
    }

    /**
     * LOT 1, point 1 : export ICS accessible au votant, débloqué seulement après clôture avec un
     * créneau final. Réutilise PollExportService::exportIcs() (même service que la version
     * organisateur, PollManageController::exportIcs()) - aucune logique d'export dupliquée ici,
     * seul le contrôle d'accès diffère (page déjà publique une fois le sondage clôturé, pas de
     * jeton admin requis).
     */
    public function exportIcs(Request $request, string $slug): HttpResponse
    {
        $poll = Poll::findByShareIdentifier($slug);
        if (! $poll || $poll->status->value !== 'closed' || $poll->final_option_id === null) {
            abort(404);
        }

        try {
            $ics = (new PollExportService)->exportIcs($poll);
        } catch (RuntimeException $e) {
            abort(404);
        }

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$poll->public_id.'.ics"',
        ]);
    }
}
