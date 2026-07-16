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
            $rangeStart = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$rangeStartTime, $timezone);
            $rangeEnd = Carbon::createFromFormat('Y-m-d H:i', $date.' '.$rangeEndTime, $timezone);

            $currentSlotStart = $rangeStart->copy();

            while ($currentSlotStart->copy()->addMinutes($durationMinutes)->lte($rangeEnd)) {
                $slotEnd = $currentSlotStart->copy()->addMinutes($durationMinutes);

                $slots[] = [
                    'starts_at' => $currentSlotStart->copy()->utc(),
                    'ends_at' => $slotEnd->copy()->utc(),
                    'label' => $this->formatSlotLabel($currentSlotStart, $slotEnd),
                ];

                $currentSlotStart->addMinutes($stepMinutes);
            }
        }

        return $slots;
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
