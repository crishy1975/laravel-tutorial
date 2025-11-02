<?php

namespace App\Services;

use App\Models\Gebaeude;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FaelligkeitsService
{
    /**
     * Berechnet die Fälligkeit eines Gebäudes und speichert das Flag `faellig`.
     *
     * Regel (rollierende Logik):
     *  - Bestimme den letzten aktiven Monat <= aktuellem Monat.
     *  - Wurde im Monat der letzten Reinigung dieser fällige Monat bereits erreicht
     *    (Y-m der letzten Reinigung >= Y-m des letzten aktiven Monats)? → dann NICHT fällig.
     *  - Sonst: fällig.
     */
    public function recalcForGebaeude(Gebaeude $g, ?Carbon $now = null): bool
    {
        $now = $now ?: Carbon::now('Europe/Rome');

        // 1) Letzte Reinigung (MAX(datum)) – ohne vererbte orderBy()!
        $last = $g->timelines()
            ->reorder()
            ->max('datum'); // string 'YYYY-MM-DD' oder null

        $lastDate = $last ? Carbon::parse($last) : null;

        // 2) Letzten aktiven Monat bis einschließlich jetzt finden
        $currentMonth = (int) $now->format('n'); // 1..12
        $lastActiveYm = $this->findLastActiveMonthYearString($g, $currentMonth, $now);
        $lastActiveDate = $lastActiveYm ? Carbon::createFromFormat('Y-m', $lastActiveYm)->startOfMonth() : null;

        // 3) Entscheidungslogik
        $faellig = 0;
        if ($lastActiveDate !== null) {
            $faellig = ($lastDate && $lastDate >= $lastActiveDate) ? 0 : 1;
        } else {
            // keine aktiven Monate konfiguriert → nicht fällig
            $faellig = 0;
        }

        // 4) Nur schreiben, wenn sich etwas ändert
        if ((int)($g->faellig ?? 0) !== $faellig) {
            $g->forceFill(['faellig' => $faellig])->save();
            Log::info('Faelligkeit neu gesetzt', [
                'gebaeude_id' => $g->id,
                'last'        => $last,
                'lastDate'    => $lastDate?->format('Y-m'),
                'lastActive'  => $lastActiveDate?->format('Y-m'),
                'faellig'     => $faellig,
            ]);
        }

        return (bool) $faellig;
    }

    /**
     * Batch: berechnet Fälligkeit für alle Gebäude (performant in Chunks).
     */
    public function recalcForAll(?Carbon $now = null): int
    {
        $now = $now ?: Carbon::now('Europe/Rome');
        $count = 0;

        Gebaeude::query()
            ->orderBy('id')
            ->chunkById(500, function ($chunk) use (&$count, $now) {
                foreach ($chunk as $g) {
                    $this->recalcForGebaeude($g, $now);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Liefert den letzten aktiven Monat als 'YYYY-MM' relativ zu $currentMonth (1..12).
     * Falls kein aktiver Monat vorhanden ist, null.
     */
    private function findLastActiveMonthYearString(Gebaeude $g, int $currentMonth, Carbon $now): ?string
    {
        $active = [];
        for ($m = 1; $m <= $currentMonth; $m++) {
            $field = 'm' . str_pad((string)$m, 2, '0', STR_PAD_LEFT);
            if ((int)($g->{$field} ?? 0) === 1) {
                $active[] = $m;
            }
        }
        if (empty($active)) {
            return null;
        }

        $lastActiveMonth = max($active);
        $year = (int)$now->format('Y');
        return sprintf('%04d-%02d', $year, $lastActiveMonth);
    }
}
