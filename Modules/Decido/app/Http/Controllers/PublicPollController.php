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
use Modules\Decido\Models\PollVote;

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
        if ($voterToken) {
            $existingVotes = PollVote::where('voter_token', $voterToken)
                ->where('poll_id', $poll->id)
                ->pluck('value', 'option_id')
                ->toArray();
        }

        $poll->load('options.votes');

        return View::make('decido::public.vote', [
            'poll' => $poll,
            'options' => $poll->options,
            'voterToken' => $voterToken,
            'existingVotes' => $existingVotes,
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

        // Décido round 6 (skill /100) : le statut n'était vérifié qu'une fois en tout début de
        // méthode, sans verrou - un vote soumis dans la fenêtre entre cette vérification et
        // l'écriture pouvait être accepté silencieusement même si l'organisateur venait de
        // clôturer le sondage entre-temps (TOCTOU). lockForUpdate + re-vérification DANS la
        // transaction éliminent cette fenêtre de course.
        DB::transaction(function () use ($poll, $optionIds, $validated, $voterToken, $voterPseudonym) {
            $lockedPoll = Poll::whereKey($poll->id)->lockForUpdate()->first();
            if (! $lockedPoll || $lockedPoll->status->value !== 'open') {
                abort(404);
            }

            if ($poll->vote_mode->value === 'yes_no_maybe') {
                foreach ($validated['votes'] as $optionId => $value) {
                    PollVote::updateOrCreate(
                        ['option_id' => (int) $optionId, 'voter_token' => $voterToken],
                        ['poll_id' => $poll->id, 'voter_pseudonym' => $voterPseudonym, 'value' => $value]
                    );
                }
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
        });

        return Redirect::back()
            ->withCookie(Cookie::make($cookieName, $voterToken, 525600, null, null, null, true))
            ->with('success', 'Votre vote a été enregistré.');
    }
}
