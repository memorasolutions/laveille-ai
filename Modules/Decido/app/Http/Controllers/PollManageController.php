<?php

declare(strict_types=1);

namespace Modules\Decido\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\View\View as ViewContract;
use InvalidArgumentException;
use Modules\Decido\Enums\PollType;
use Modules\Decido\Enums\VoteMode;
use Modules\Decido\Models\Poll;
use Modules\Decido\Services\SlotGenerationService;

class PollManageController extends Controller
{
    public function index(): ViewContract
    {
        $polls = Poll::where('creator_id', Auth::id())->latest()->get();

        return View::make('decido::manage.index', compact('polls'));
    }

    public function create(): ViewContract
    {
        return View::make('decido::manage.create', [
            'pollTypes' => PollType::cases(),
            'voteModes' => VoteMode::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $isDateType = $request->input('type') === 'date';

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:date,classic'],
            'timezone' => ['required', 'string', 'max:60'],
            'duration_minutes' => $isDateType ? ['required', 'integer', 'min:5', 'max:480'] : ['nullable'],
            'range_start_time' => $isDateType ? ['required', 'date_format:H:i'] : ['nullable'],
            'range_end_time' => $isDateType ? ['required', 'date_format:H:i', 'after:range_start_time'] : ['nullable'],
            'step_minutes' => $isDateType ? ['required', 'integer', 'in:15,30,60'] : ['nullable'],
            // max:60 = round 9 (skill /100) : aucune borne n'existait, contrairement au type
            // classique (déjà plafonné à 20 options ci-dessous) - 3800 créneaux générés en test
            // réel (40 dates x plage large x pas 15 min), risque de performance/abus.
            'candidate_dates' => $isDateType ? ['required', 'array', 'min:1', 'max:60'] : ['nullable'],
            'candidate_dates.*' => $isDateType ? ['date', 'after_or_equal:today'] : ['nullable'],
            'vote_mode' => $isDateType ? ['nullable'] : ['required', 'in:single_choice,approval'],
            'options' => $isDateType ? ['nullable'] : ['required', 'array', 'min:2', 'max:20'],
            'options.*' => $isDateType ? ['nullable'] : ['required', 'string', 'max:255'],
        ]);

        $poll = new Poll;
        $poll->title = $validated['title'];
        $poll->description = $validated['description'] ?? null;
        $poll->type = $validated['type'];
        $poll->vote_mode = $isDateType ? 'yes_no_maybe' : $validated['vote_mode'];
        $poll->timezone = $validated['timezone'];
        $poll->creator_id = Auth::id();
        $poll->status = 'draft';

        if ($isDateType) {
            $poll->duration_minutes = (int) $validated['duration_minutes'];
            $poll->range_start_time = $validated['range_start_time'];
            $poll->range_end_time = $validated['range_end_time'];
            $poll->step_minutes = (int) $validated['step_minutes'];
        }

        $plainToken = Str::random(40);
        $poll->admin_token_hash = hash('sha256', $plainToken);
        $poll->save();

        try {
            if ($isDateType) {
                $slotService = new SlotGenerationService;
                $slots = $slotService->generateSlots(
                    $validated['candidate_dates'],
                    $validated['range_start_time'],
                    $validated['range_end_time'],
                    (int) $validated['duration_minutes'],
                    (int) $validated['step_minutes'],
                    $validated['timezone']
                );

                // Round 9 (skill /100) : le plafond de 60 dates candidates seul ne borne pas le
                // volume total (plage large + pas court peut générer des centaines de créneaux
                // par date) - 3800 créneaux créés en test réel sans ce garde-fou. Même pattern
                // d'erreur que SlotGenerationService::validateInputs() (poll supprimé, formulaire
                // renvoyé avec l'erreur).
                if (count($slots) > 500) {
                    throw new InvalidArgumentException(
                        'Le nombre total de créneaux générés ('.count($slots).') dépasse la limite de 500. Réduis le nombre de dates, la plage horaire ou augmente le pas de temps.'
                    );
                }

                foreach ($slots as $index => $slot) {
                    $poll->options()->create([
                        'label' => $slot['label'],
                        'starts_at' => $slot['starts_at'],
                        'ends_at' => $slot['ends_at'],
                        'sort_order' => $index,
                    ]);
                }
            } else {
                foreach ($validated['options'] as $index => $optionLabel) {
                    $poll->options()->create([
                        'label' => $optionLabel,
                        'starts_at' => null,
                        'ends_at' => null,
                        'sort_order' => $index,
                    ]);
                }
            }
        } catch (InvalidArgumentException $e) {
            $poll->delete();

            return Redirect::back()->withInput()->withErrors(['candidate_dates' => $e->getMessage()]);
        }

        $poll->status = 'open';
        $poll->save();

        Session::flash('admin_token_plain', $plainToken);

        return Redirect::route('decido.manage', [
            'poll' => $poll->public_id,
            'adminToken' => $plainToken,
        ])->with('success', 'Le sondage a été créé avec succès.');
    }

    public function manage(Request $request, string $poll, string $adminToken): ViewContract
    {
        $pollModel = Poll::findByShareIdentifier($poll);
        if (! $pollModel) {
            abort(404);
        }

        $this->authorizeManage($pollModel, $adminToken);

        $pollModel->load(['options.votes']);

        return View::make('decido::manage.results', [
            'poll' => $pollModel,
            'options' => $pollModel->options,
            'adminToken' => $adminToken,
        ]);
    }

    public function close(Request $request, string $poll, string $adminToken): RedirectResponse
    {
        $pollModel = Poll::findByShareIdentifier($poll);
        if (! $pollModel) {
            abort(404);
        }

        $this->authorizeManage($pollModel, $adminToken);

        $validated = $request->validate([
            'final_option_id' => ['nullable', 'integer'],
        ]);

        $finalOptionId = $validated['final_option_id'] ?? null;

        if ($finalOptionId !== null && ! $pollModel->options()->where('id', $finalOptionId)->exists()) {
            abort(404);
        }

        $pollModel->status = 'closed';
        $pollModel->final_option_id = $finalOptionId;
        $pollModel->expires_at = now()->addMonths(config('decido.expiration_months_after_close', 6));
        $pollModel->save();

        return Redirect::route('decido.manage', [
            'poll' => $pollModel->public_id,
            'adminToken' => $adminToken,
        ])->with('success', 'Le sondage a été clôturé.');
    }

    public function exportCsv(Request $request, string $poll, string $adminToken): \Symfony\Component\HttpFoundation\Response
    {
        $pollModel = Poll::findByShareIdentifier($poll);
        if (! $pollModel) {
            abort(404);
        }

        $this->authorizeManage($pollModel, $adminToken);

        $pollModel->load('options.votes');

        $csv = (new \Modules\Decido\Services\PollExportService)->exportCsv($pollModel);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$pollModel->public_id.'-votes.csv"',
        ]);
    }

    public function exportIcs(Request $request, string $poll, string $adminToken): \Symfony\Component\HttpFoundation\Response
    {
        $pollModel = Poll::findByShareIdentifier($poll);
        if (! $pollModel) {
            abort(404);
        }

        $this->authorizeManage($pollModel, $adminToken);

        try {
            $ics = (new \Modules\Decido\Services\PollExportService)->exportIcs($pollModel);
        } catch (\RuntimeException $e) {
            return Redirect::route('decido.manage', ['poll' => $pollModel->public_id, 'adminToken' => $adminToken])
                ->withErrors(['export' => $e->getMessage()]);
        }

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$pollModel->public_id.'.ics"',
        ]);
    }

    public function createShortLink(Request $request, string $poll, string $adminToken): RedirectResponse
    {
        $pollModel = Poll::findByShareIdentifier($poll);
        if (! $pollModel) {
            abort(404);
        }

        $this->authorizeManage($pollModel, $adminToken);

        // Round 8 (skill /100) : class_exists() seul ne détecte pas un module ShortUrl désactivé
        // via modules_statuses.json (nwidart garde les classes en autoload même désactivé) - un
        // lien court "fantôme" pouvait être créé sans erreur, pointant vers des routes jamais
        // enregistrées (404 réel). ModuleChecker vérifie le vrai état d'activation.
        if (! \Modules\Core\Services\ModuleChecker::isAvailable('ShortUrl')) {
            return Redirect::route('decido.manage', ['poll' => $pollModel->public_id, 'adminToken' => $adminToken])
                ->withErrors(['shortlink' => "Le service de liens courts n'est pas disponible."]);
        }

        if ($pollModel->short_url_id) {
            return Redirect::route('decido.manage', ['poll' => $pollModel->public_id, 'adminToken' => $adminToken]);
        }

        $userId = $pollModel->creator_id ?? Auth::id();

        $shortUrl = app(\Modules\ShortUrl\Services\ShortUrlService::class)->createShortUrl([
            'original_url' => $pollModel->share_url,
            'title' => 'Sondage Décido : '.$pollModel->title,
            'redirect_type' => 301,
            'is_active' => true,
        ], $userId);

        $pollModel->update(['short_url_id' => $shortUrl->id]);

        return Redirect::route('decido.manage', ['poll' => $pollModel->public_id, 'adminToken' => $adminToken])
            ->with('success', 'Lien court créé.');
    }

    public function qrCode(Request $request, string $poll, string $adminToken, \Modules\Core\Services\QrCodeService $qr): \Symfony\Component\HttpFoundation\Response
    {
        $pollModel = Poll::findByShareIdentifier($poll);
        if (! $pollModel) {
            abort(404);
        }

        $this->authorizeManage($pollModel, $adminToken);

        $url = $pollModel->getShortUrlString() ?? $pollModel->share_url;
        $png = $qr->generate($url);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qr-decido-'.$pollModel->public_id.'.png"',
        ]);
    }

    private function authorizeManage(Poll $poll, string $adminToken): void
    {
        $isOwner = Auth::check() && Auth::id() === $poll->creator_id;
        $hasValidToken = $poll->verifyAdminToken($adminToken);

        if (! $isOwner && ! $hasValidToken) {
            abort(403);
        }
    }
}
