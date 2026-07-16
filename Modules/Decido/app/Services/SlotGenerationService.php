<?php

declare(strict_types=1);

namespace Modules\Decido\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class SlotGenerationService
{
    /**
     * Génère tous les créneaux horaires possibles pour les dates candidates.
     *
     * @param  array<int, string>  $candidateDates  Dates au format 'Y-m-d' (fuseau du sondage)
     * @return array<int, array<string, CarbonInterface|string>>
     */
    public function generateSlots(
        array $candidateDates,
        string $rangeStartTime,
        string $rangeEndTime,
        int $durationMinutes,
        int $stepMinutes,
        string $timezone
    ): array {
        $this->validateInputs($rangeStartTime, $rangeEndTime, $durationMinutes, $stepMinutes);

        $slots = [];

        foreach ($candidateDates as $date) {
            // Round 8 (skill /100) : l'arithmétique se fait désormais entièrement en UTC (jamais
            // en heure locale) avant conversion pour l'affichage. Additionner des minutes sur une
            // instance Carbon localisée traverse silencieusement les changements d'heure (DST) -
            // un créneau de 30 min généré à cheval sur le passage à l'heure d'été durait en
            // réalité 90 min une fois relu (l'heure locale 02h00-02h59 n'existe pas ce jour-là).
            // L'UTC n'a pas de DST : la durée réelle d'un créneau est désormais toujours exacte.
            $rangeStart = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$rangeStartTime, $timezone)->utc();
            $rangeEnd = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$rangeEndTime, $timezone)->utc();

            $currentSlotStart = $rangeStart->copy();
            $dateSlots = [];

            while ($currentSlotStart->copy()->addMinutes($durationMinutes)->lte($rangeEnd)) {
                $slotEnd = $currentSlotStart->copy()->addMinutes($durationMinutes);

                $dateSlots[] = [
                    'starts_at' => $currentSlotStart->copy(),
                    'ends_at' => $slotEnd->copy(),
                ];

                $currentSlotStart->addMinutes($stepMinutes);
            }

            $slots = array_merge($slots, $this->labelSlots($dateSlots, $timezone));
        }

        return $slots;
    }

    /**
     * Ajoute le libellé lisible de chaque créneau (heure locale du sondage). Round 8 (skill
     * /100) : lors du retour à l'heure normale (fin de l'heure d'été), l'heure locale
     * 01h00-01h59 se produit DEUX FOIS - deux créneaux UTC distincts peuvent donc produire un
     * libellé strictement identique ("1 h 00 - 1 h 30"), rendant impossible pour un votant de
     * savoir lequel choisir. Si une collision de libellé est détectée pour une même date, le
     * décalage UTC est ajouté en désambiguïsation - uniquement sur les créneaux concernés, pour
     * ne pas alourdir l'affichage des cas non ambigus (l'immense majorité).
     *
     * @param  array<int, array{starts_at: CarbonInterface, ends_at: CarbonInterface}>  $dateSlots
     * @return array<int, array<string, CarbonInterface|string>>
     */
    private function labelSlots(array $dateSlots, string $timezone): array
    {
        $labeled = array_map(function (array $slot) use ($timezone) {
            $localStart = $slot['starts_at']->copy()->timezone($timezone);
            $localEnd = $slot['ends_at']->copy()->timezone($timezone);

            return $slot + [
                'label' => $this->formatSlotLabel($localStart, $localEnd),
                'local_start' => $localStart,
            ];
        }, $dateSlots);

        $labelCounts = array_count_values(array_column($labeled, 'label'));

        return array_map(function (array $slot) use ($labelCounts) {
            if ($labelCounts[$slot['label']] > 1) {
                $slot['label'] .= ' (UTC'.$slot['local_start']->format('P').')';
            }

            unset($slot['local_start']);

            return $slot;
        }, $labeled);
    }

    /**
     * Calcule le nombre de créneaux qu'une seule date candidate produirait.
     */
    public function maxPossibleSlots(
        string $rangeStartTime,
        string $rangeEndTime,
        int $durationMinutes,
        int $stepMinutes
    ): int {
        $this->validateInputs($rangeStartTime, $rangeEndTime, $durationMinutes, $stepMinutes);

        $start = Carbon::createFromFormat('Y-m-d H:i', '2000-01-01 '.$rangeStartTime, 'UTC');
        $end = Carbon::createFromFormat('Y-m-d H:i', '2000-01-01 '.$rangeEndTime, 'UTC');

        $totalMinutes = (int) $start->diffInMinutes($end);
        $remainingMinutes = $totalMinutes - $durationMinutes;

        return 1 + intdiv($remainingMinutes, $stepMinutes);
    }

    /**
     * Valide les paramètres d'entrée. Une plage horaire inversée (fin <= début)
     * est une erreur de saisie de l'admin — jamais interprétée silencieusement
     * comme une plage nocturne (aucune fonctionnalité "plage sur deux jours"
     * n'existe dans le design V1 de Décido).
     */
    private function validateInputs(
        string $rangeStartTime,
        string $rangeEndTime,
        int $durationMinutes,
        int $stepMinutes
    ): void {
        if ($durationMinutes <= 0) {
            throw new InvalidArgumentException('La durée de la rencontre doit être supérieure à zéro.');
        }

        if ($stepMinutes <= 0) {
            throw new InvalidArgumentException('Le pas de temps doit être supérieur à zéro.');
        }

        $start = Carbon::createFromFormat('Y-m-d H:i', '2000-01-01 '.$rangeStartTime, 'UTC');
        $end = Carbon::createFromFormat('Y-m-d H:i', '2000-01-01 '.$rangeEndTime, 'UTC');

        if ($end->lte($start)) {
            throw new InvalidArgumentException('L\'heure de fin de plage doit être après l\'heure de début.');
        }

        if ($start->diffInMinutes($end) < $durationMinutes) {
            throw new InvalidArgumentException('La durée de la rencontre dépasse la plage horaire disponible.');
        }
    }

    private function formatSlotLabel(CarbonInterface $start, CarbonInterface $end): string
    {
        $start = $start->copy()->locale('fr');
        $end = $end->copy()->locale('fr');

        $day = $start->isoFormat('dddd D MMMM');
        $startHour = $start->isoFormat('H [h] mm');
        $endHour = $end->isoFormat('H [h] mm');

        return "{$day}, {$startHour} - {$endHour}";
    }
}
