<?php

declare(strict_types=1);

namespace Modules\Decido\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\View\View as ViewContract;
use InvalidArgumentException;
use Modules\Decido\Enums\VoteMode;
use Modules\Decido\Models\Poll;
use Modules\Decido\Rules\DistinctNormalized;
use Modules\Decido\Services\SlotGenerationService;
use Modules\Decido\Services\TimezoneListService;

class PollManageController extends Controller
{
    public function index(): ViewContract
    {
        $polls = Poll::where('creator_id', Auth::id())->latest()->get();

        return View::make('decido::manage.index', compact('polls'));
    }

    public function create(): ViewContract
    {
        return View::make('decido::manage.create');
    }

    // Option E (veille pp_search juillet 2026, validée Perplexity + Codex + Gemini, 95/100) :
    // /decido/creer n'est plus qu'un choix rapide de type (create()) - chaque type a désormais
    // son propre formulaire dédié et allégé, plutôt qu'une seule longue page avec rendu
    // conditionnel x-show. Réduit le nombre de champs visibles au minimum pertinent par type.
    public function createDate(): ViewContract
    {
        return View::make('decido::manage.create-date', [
            'timezones' => $this->timezoneOptions(),
        ]);
    }

    public function createClassic(): ViewContract
    {
        return View::make('decido::manage.create-classic', [
            'voteModes' => VoteMode::cases(),
            'timezones' => $this->timezoneOptions(),
        ]);
    }

    /**
     * @return array<int, array{id: string, label: string, region: string, offset: string}>
     */
    private function timezoneOptions(): array
    {
        return TimezoneListService::list();
    }

    public function store(Request $request): RedirectResponse
    {
        $isDateType = $request->input('type') === 'date';

        // SIM_decido0717 (2026-07-17, gate de vérification finale) : "America/Montreal" a été
        // retiré de la base IANA tzdata en 2014 (fusionné dans America/Toronto, mêmes règles
        // HNE/HAE) - ce n'est donc plus un identifiant reconnu par timezone_identifiers_list()
        // sur PHP moderne. Le <select> du formulaire (description-timezone-fields.blade.php)
        // propose toujours "Montréal (HNE/HAE)" avec value="America/Montreal" (libellé légitime,
        // conservé pour l'UX et pour ne pas casser la préservation old() - FEAT_010), ce qui
        // faisait échouer À CHAQUE FOIS la règle de validation 'timezone' ci-dessous ("Le champ
        // timezone doit être un fuseau horaire valide."), rendant la création d'un sondage
        // strictement impossible dès que ce choix (le plus naturel sur un site québécois) était
        // sélectionné. On normalise l'alias historique vers l'identifiant canonique AVANT
        // validation, plutôt que de toucher au template : la donnée persistée reste toujours un
        // fuseau IANA valide.
        if ($request->input('timezone') === 'America/Montreal') {
            $request->merge(['timezone' => 'America/Toronto']);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            // 'max:5000' = round 23 (skill /100) : avant ce fix, `description` n'avait AUCUNE limite
            // de longueur, contrairement à `title` (max:255) - une valeur arbitrairement grande était
            // acceptée par la validation. `description` est stockée dans une colonne MySQL `text`
            // (limite réelle : 65 535 octets, cf. migration create_decido_polls_table). Preuve réelle
            // isolée hors framework (INSERT direct sur la DB locale, config('database.connections.mysql.strict')
            // = true comme en prod) : une description de 5 Mo ne produit PAS de troncature silencieuse
            // mais lève une PDOException SQLSTATE 22001 "Data too long for column 'description'" -
            // cette exception (Illuminate\Database\QueryException, PAS une InvalidArgumentException)
            // n'est interceptée NULLE PART dans store() : elle remonte telle quelle jusqu'au
            // gestionnaire d'exceptions global, produisant un crash 500 brut pour une simple entrée
            // trop longue (copier-coller massif, requête forgée) - même défaut de robustesse que le
            // fuseau horaire invalide corrigé au round 18. DB::transaction() (round 16) annule bien
            // l'INSERT partiel (pas de sondage fantôme), mais un 500 au lieu d'une erreur de
            // validation claire reste un défaut. 5000 caractères (mb_strlen, jamais des octets bruts
            // côté règle Laravel `max` sur un string) laisse une marge large pour un texte libre
            // légitime tout en restant très en-deçà de la limite de 65 535 octets même dans le pire
            // cas UTF-8 (4 octets/caractère système emoji = 20 000 octets max).
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', 'in:date,classic'],
            // 'timezone' (règle Laravel) = round 18 (skill /100) : avant ce fix, seul 'string|max:60'
            // gardait ce champ - une chaîne arbitraire non-IANA ("Not/AZone", "'; DROP TABLE...", une
            // valeur à rallonge) passait la validation intacte puis atteignait directement
            // SlotGenerationService::generateSlots() -> Carbon::createFromFormat(..., $timezone), qui
            // construit un DateTimeZone en interne. DateTimeZone::__construct() lève une \Exception
            // brute (jamais une InvalidArgumentException) sur une chaîne invalide, donc NI cette
            // validation NI le catch (InvalidArgumentException $e) plus bas n'interceptaient l'erreur :
            // elle remontait telle quelle jusqu'au gestionnaire d'exceptions global (crash 500). La
            // règle 'timezone' de Laravel vérifie strictement l'appartenance à
            // timezone_identifiers_list(DateTimeZone::ALL) - les vraies zones IANA uniquement (rejette
            // aussi les abréviations ambiguës type "EST" et les décalages bruts type "+05:00").
            'timezone' => ['required', 'string', 'max:60', 'timezone'],
            'duration_minutes' => $isDateType ? ['required', 'integer', 'min:5', 'max:480'] : ['nullable'],
            'range_start_time' => $isDateType ? ['required', 'date_format:H:i'] : ['nullable'],
            'range_end_time' => $isDateType ? ['required', 'date_format:H:i', 'after:range_start_time'] : ['nullable'],
            // Refonte 2026-07-17 (round 2) : le champ passe d'un select à 3 valeurs fixes (15/30/60)
            // à une valeur calculée dynamiquement côté client (durée/2 arrondi, ou durée exacte, ou
            // personnalisée) - la contrainte `in:15,30,60` rejetterait désormais la majorité des
            // soumissions légitimes. SlotGenerationService ne borne réellement que stepMinutes > 0 ;
            // on aligne la validation sur les mêmes bornes que duration_minutes (5-480) en défense.
            'step_minutes' => $isDateType ? ['required', 'integer', 'min:5', 'max:480'] : ['nullable'],
            // max:60 = round 9 (skill /100) : aucune borne n'existait, contrairement au type
            // classique (déjà plafonné à 20 options ci-dessous) - 3800 créneaux générés en test
            // réel (40 dates x plage large x pas 15 min), risque de performance/abus.
            'candidate_dates' => $isDateType ? ['required', 'array', 'min:1', 'max:60'] : ['nullable'],
            // 'distinct' = round 11 (skill /100) : aucune règle n'empêchait de soumettre deux fois
            // la même date candidate (double-clic, copier-coller, requête forgée) - SlotGenerationService
            // générait alors deux jeux de créneaux strictement identiques (même libellé, mêmes
            // starts_at/ends_at), créés comme deux PollOption distinctes. Les votants qui cliquaient
            // l'une ou l'autre carte voyaient leurs votes silencieusement scindés entre les deux
            // lignes dans les résultats, faussant le décompte sans jamais remonter d'erreur.
            // 'date_format:Y-m-d' (round 20, skill /100, remplace l'ancienne règle 'date') : `distinct`
            // compare des CHAÎNES EXACTES, pas des dates calendaires - la règle Laravel générique `date`
            // acceptait n'importe quel format reconnu par strtotime() ("2027-03-14", "2027-3-14",
            // "2027-03-14T00:00:00"...). Deux dates soumises dans des formats DIFFÉRENTS mais
            // représentant le MÊME jour ("2027-03-14" et "2027-3-14") passaient donc `distinct` (chaînes
            // différentes) tout en étant ensuite parsées IDENTIQUEMENT par
            // SlotGenerationService::generateSlots() -> Carbon::createFromFormat('Y-m-d H:i', ...) -
            // recréant exactement le bug du round 11 (deux jeux de créneaux strictement identiques,
            // votes scindés) sans jamais déclencher `distinct`. Preuve réelle avant ce fix : POST avec
            // candidate_dates=['2027-03-14','2027-3-14'] créait 4 PollOption (2 créneaux x 2 dates
            // identiques) au lieu de 2, avec starts_at/ends_at rigoureusement identiques deux à deux.
            // Le <input type="date"> du formulaire (create.blade.php) soumet TOUJOURS le format canonique
            // Y-m-d (spec HTML5) - cette règle ne restreint donc aucun usage légitime, seulement les
            // requêtes forgées hors formulaire.
            'candidate_dates.*' => $isDateType ? ['date_format:Y-m-d', 'after_or_equal:today', 'distinct'] : ['nullable'],
            'vote_mode' => $isDateType ? ['nullable'] : ['required', 'in:single_choice,approval'],
            // new DistinctNormalized (round 20, skill /100) : complète 'distinct' ci-dessous - voir
            // Modules/Decido/app/Rules/DistinctNormalized.php pour le détail. 'distinct' seul ne détecte
            // que l'égalité de chaîne EXACTE ; deux libellés qui ne diffèrent que par la casse
            // ("Pizza"/"pizza") ou des espaces internes multiples ("Pizza 4 fromages"/"Pizza  4 fromages")
            // le contournaient tout en créant 2 PollOption visuellement indiscernables pour un votant,
            // recréant le bug de scission de votes du round 11 par un simple changement de format.
            'options' => $isDateType ? ['nullable'] : ['required', 'array', 'min:2', 'max:20', new DistinctNormalized],
            // 'distinct' = round 11 (skill /100) : même faille pour le type classique - deux options
            // au libellé strictement identique ("Pizza" / "Pizza") créaient deux PollOption distinctes,
            // scindant les votes de la même façon (un sondage à 5 votes pour "Pizza" pouvait afficher
            // 3 et 2 sur deux lignes séparées au lieu de révéler la vraie majorité).
            'options.*' => $isDateType ? ['nullable'] : ['required', 'string', 'max:255', 'distinct'],
            // Nouvelle fonctionnalité (demande utilisateur 2026-07-17, veille pp_search validée
            // Perplexity+Codex+Gemini, 95/100 - "modéliser chaque jour comme une LISTE de fenêtres
            // de disponibilité, jamais une seule paire début/fin") : range_start_time/range_end_time
            // restent la plage PAR DÉFAUT (utilisée quand une date n'a aucune entrée ci-dessous),
            // mais chaque date candidate peut désormais avoir PLUSIEURS plages (ex. 9h-12h ET
            // 14h-17h, pour sauter le dîner) - candidate_date_ranges[index] est un tableau
            // d'objets {start, end}, index correspondant à candidate_dates[index]. Remplace
            // l'ancien candidate_date_start_times[]/candidate_date_end_times[] (une seule
            // surcharge par date, insuffisant).
            'candidate_date_ranges' => $isDateType ? ['nullable', 'array'] : ['nullable'],
            'candidate_date_ranges.*' => $isDateType ? ['nullable', 'array'] : ['nullable'],
            'candidate_date_ranges.*.*.start' => $isDateType ? ['nullable', 'date_format:H:i', 'required_with:candidate_date_ranges.*.*.end'] : ['nullable'],
            'candidate_date_ranges.*.*.end' => $isDateType ? ['nullable', 'date_format:H:i', 'required_with:candidate_date_ranges.*.*.start'] : ['nullable'],
        ]);

        // Round 16 (skill /100) : la création du Poll (INSERT immédiat via $poll->save()) puis la
        // boucle de création de ses PollOption (jusqu'à 500 créneaux pour le type "date") n'étaient
        // enveloppées dans AUCUNE transaction. Seule une InvalidArgumentException (garde-fou >500
        // créneaux, round 9) était rattrapée pour supprimer manuellement le sondage orphelin ;
        // toute AUTRE exception survenant en cours de boucle (contrainte DB, perte de connexion,
        // timeout) - un scénario réel avec des centaines d'INSERT séquentiels - propageait
        // simplement l'erreur sans jamais nettoyer : un sondage "fantôme" restait en base
        // (status='draft', options partiellement créées), jamais promu à 'open', visible dans "Mes
        // sondages" du créateur mais définitivement inutilisable. DB::transaction() enveloppe
        // désormais la création complète (Poll + toutes ses options + passage à status='open') :
        // toute exception, quel que soit son type, annule intégralement l'opération (rollback
        // automatique) avant d'être soit rattrapée ci-dessous (message convivial), soit
        // retransmise telle quelle (page d'erreur générique) - dans les deux cas sans aucune trace
        // partielle en base.
        try {
            [$poll, $plainToken] = DB::transaction(function () use ($validated, $isDateType) {
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

                if ($isDateType) {
                    $slotService = new SlotGenerationService;

                    // Nouvelle fonctionnalité (demande utilisateur 2026-07-17, veille pp_search
                    // validée Perplexity+Codex+Gemini, 95/100) : chaque date candidate peut
                    // désormais avoir PLUSIEURS plages horaires (ex. 9h-12h ET 14h-17h). Chaque
                    // plage de chaque date est ajoutée à son groupe "plage effective" - une même
                    // date peut donc désormais apparaître dans PLUSIEURS groupes (un par plage),
                    // generateSlots() étant appelé une fois par groupe comme avant. La méthode
                    // elle-même (durcie par 20+ rounds d'audit /100 : DST round 8, RFC5545 round 9)
                    // reste totalement inchangée. Le tri final par starts_at restaure l'ordre
                    // chronologique, indépendant de l'ordre d'itération des groupes.
                    $dateGroups = [];
                    foreach ($validated['candidate_dates'] as $index => $date) {
                        // Une plage partielle (début renseigné sans fin, ou l'inverse) est déjà
                        // rejetée en amont par la règle de validation `required_with` sur
                        // candidate_date_ranges.*.*.start/end (vérifié empiriquement : Laravel
                        // considère bien 'end' => '' comme absent une fois que 'start' est non
                        // vide, donc le couple (start='14:00', end='') échoue la validation avant
                        // même d'atteindre ce code) - aucun contrôle manuel supplémentaire requis.
                        $ranges = array_values(array_filter(
                            $validated['candidate_date_ranges'][$index] ?? [],
                            fn ($r) => ! empty($r['start']) && ! empty($r['end'])
                        ));

                        if ($ranges === []) {
                            $ranges = [['start' => $validated['range_start_time'], 'end' => $validated['range_end_time']]];
                        } else {
                            // Plages multiples pour la MÊME date : trier par heure de début et
                            // rejeter tout chevauchement (ex. 9h-12h et 11h-14h saisies pour la
                            // même date auraient produit des créneaux qui se recoupent sans
                            // jamais faire remonter d'erreur claire à l'organisateur - veille
                            // pp_search : "reject overlapping ranges").
                            usort($ranges, fn ($a, $b) => $a['start'] <=> $b['start']);

                            for ($i = 1; $i < count($ranges); $i++) {
                                if ($ranges[$i]['start'] < $ranges[$i - 1]['end']) {
                                    throw new InvalidArgumentException(
                                        "Les plages horaires du {$date} se chevauchent ({$ranges[$i - 1]['start']}-{$ranges[$i - 1]['end']} et {$ranges[$i]['start']}-{$ranges[$i]['end']})."
                                    );
                                }
                            }
                        }

                        foreach ($ranges as $range) {
                            $dateGroups[$range['start'].'|'.$range['end']][] = $date;
                        }
                    }

                    $slots = [];
                    foreach ($dateGroups as $rangeKey => $datesInGroup) {
                        [$groupStart, $groupEnd] = explode('|', $rangeKey);
                        $slots = array_merge($slots, $slotService->generateSlots(
                            $datesInGroup,
                            $groupStart,
                            $groupEnd,
                            (int) $validated['duration_minutes'],
                            (int) $validated['step_minutes'],
                            $validated['timezone']
                        ));
                    }

                    usort($slots, static fn (array $a, array $b) => $a['starts_at'] <=> $b['starts_at']);

                    // Round 9 (skill /100) : le plafond de 60 dates candidates seul ne borne pas le
                    // volume total (plage large + pas court peut générer des centaines de créneaux
                    // par date) - 3800 créneaux créés en test réel sans ce garde-fou. Cette exception
                    // déclenche désormais le rollback automatique de DB::transaction() (round 16) au
                    // lieu d'un $poll->delete() manuel.
                    if (count($slots) > 500) {
                        throw new InvalidArgumentException(
                            'Le nombre total de créneaux générés ('.count($slots).') dépasse la limite de 500. Réduis le nombre de dates, la plage horaire ou augmente le pas de temps.'
                        );
                    }

                    // Round 19 (skill /100) : count($slots) > 500 était vérifié, jamais count($slots)
                    // === 0. SlotGenerationService::validateInputs() ne compare la plage horaire à la
                    // durée que sur une date de référence NEUTRE ('2000-01-01', sans DST), donc une
                    // plage/durée nominalement valides (ex. 01h30-03h00 = 90 min nominal, durée 60 min)
                    // passe la validation. Mais generateSlots() calcule l'écart RÉEL en UTC pour CHAQUE
                    // date candidate (round 8) : un jour de passage à l'heure d'été (avance d'horloge),
                    // l'écart réel entre 01h30 et 03h00 heure locale n'est que de 30 minutes (l'heure
                    // 02h00-02h59 n'existe pas ce jour-là) - inférieur à la durée de 60 min, donc CETTE
                    // date produit 0 créneau. Prouvé en réel : generateSlots(['2027-03-14'], '01:30',
                    // '03:00', 60, 15, 'America/Toronto') retourne un tableau vide. Si TOUTES les dates
                    // candidates soumises tombent dans ce cas (ex. une seule date choisie = le jour du
                    // changement d'heure), $slots était vide, la boucle foreach ci-dessous ne créait
                    // AUCUNE PollOption, et le sondage était quand même promu 'open' et sauvegardé :
                    // un sondage publié, partageable, sur lequel personne ne peut voter, sans aucun
                    // message d'erreur pour le créateur. Rejet propre désormais, avec rollback complet
                    // (transaction round 16) au lieu d'un sondage fantôme sans options.
                    if (count($slots) === 0) {
                        throw new InvalidArgumentException(
                            "Aucun créneau n'a pu être généré pour les dates sélectionnées avec cette plage horaire et cette durée. Vérifie que la durée de la rencontre ne dépasse pas la plage horaire réelle de chaque date (le passage à l'heure d'été peut raccourcir une plage en apparence valide)."
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

                $poll->status = 'open';
                $poll->save();

                return [$poll, $plainToken];
            });
        } catch (InvalidArgumentException $e) {
            return Redirect::back()->withInput()->withErrors(['candidate_dates' => $e->getMessage()]);
        }

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

        // Round 18 (skill /100) : close() n'était pas idempotente - rien n'empêchait de rappeler
        // cette action sur un sondage DÉJÀ clôturé (double-clic avant que l'UI ne masque le
        // formulaire - lequel n'est affiché que si status==='open' côté vue, aucune garantie côté
        // serveur -, ou simple rejeu de la requête POST). Un second appel écrasait silencieusement
        // final_option_id (potentiellement vers une AUTRE option, ou vers null si le second appel
        // omettait le champ) et repoussait indéfiniment expires_at à chaque rejeu, contournant la
        // politique de purge automatique (decido:purge-expired, round 5). Une fois clôturé, le
        // sondage reste sur son premier créneau final et sa première date d'expiration - on
        // redirige simplement vers la page de gestion sans rien muter, comme si la clôture avait
        // déjà eu lieu (elle a effectivement déjà eu lieu).
        if ($pollModel->status->value === 'closed') {
            return Redirect::route('decido.manage', [
                'poll' => $pollModel->public_id,
                'adminToken' => $adminToken,
            ])->with('success', 'Le sondage a été clôturé.');
        }

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

        $userId = $pollModel->creator_id ?? Auth::id();

        // Slug personnalisé (demande utilisateur 2026-07-18) : réutilise EXACTEMENT la validation
        // du formulaire ShortUrl existant (UserShortUrlController::store()) - alpha_dash, unique,
        // mots réservés - pour rester cohérent avec le reste du système plutôt que d'inventer une
        // règle distincte.
        $validated = $request->validate([
            'slug' => [
                'nullable', 'alpha_dash', 'max:50',
                'unique:short_urls,slug',
                'not_in:'.implode(',', \Modules\ShortUrl\Models\ShortUrl::RESERVED_SLUGS),
            ],
        ]);

        // Round 15 (skill /100) : la création + l'écriture du lien court passent désormais par
        // Poll::claimShortUrl(), qui relit l'état à l'intérieur d'une transaction verrouillée
        // (lockForUpdate) au lieu de faire confiance à $pollModel (potentiellement périmée sous
        // requêtes concurrentes) - voir le commentaire de la méthode pour le détail de la race
        // condition corrigée.
        try {
            $pollModel->claimShortUrl(
                $userId,
                app(\Modules\ShortUrl\Services\ShortUrlService::class),
                $validated['slug'] ?? null
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // Angle mort signalé par Gemini (validation croisée 2026-07-18) : entre la vérification
            // `unique` ci-dessus et l'écriture réelle, un autre visiteur peut avoir réservé le même
            // slug (contrainte unique en base = filet de sécurité final, pas la validation seule).
            return Redirect::route('decido.manage', ['poll' => $pollModel->public_id, 'adminToken' => $adminToken])
                ->withErrors(['slug' => 'Ce slug vient d\'être pris par quelqu\'un d\'autre - réessaie avec un autre.'])
                ->withInput();
        }

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
