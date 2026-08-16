<?php

namespace App\Console\Commands;

use App\Models\Gebaeude;
use App\Models\StrassenMapping;
use App\Services\StrassenNormalizer;
use Illuminate\Console\Command;

class NormalizeStrassen extends Command
{
    protected $signature = 'gebaeude:normalize-strassen
                            {--dry-run : Nur Vorschau, nichts speichern}
                            {--reset : Auch manuelle Übersteuerungen zurücksetzen}
                            {--analyze : Detaillierte Analyse jeder Straße anzeigen}';

    protected $description = 'Normalisiert alle Straßennamen für korrekte Sortierung (DE/IT)';

    public function handle(): int
    {
        $dryRun  = $this->option('dry-run');
        $reset   = $this->option('reset');
        $analyze = $this->option('analyze');
        $normalizer = new StrassenNormalizer();

        $this->info('🏠 Straßen-Normalisierung');
        $this->line('');

        // 1. Alle einzigartigen Straßennamen sammeln
        $strassen = Gebaeude::whereNotNull('strasse')
            ->where('strasse', '!=', '')
            ->distinct()
            ->pluck('strasse');

        $this->info("Gefunden: {$strassen->count()} verschiedene Straßennamen");
        $this->line('');

        // 2. Mappings erstellen/aktualisieren
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($strassen as $strasse) {
            $sortKey = $normalizer->normalize($strasse);

            $existing = StrassenMapping::where('strasse_original', $strasse)->first();

            if ($existing) {
                if ($existing->is_manual && !$reset) {
                    $skipped++;
                    if ($analyze) {
                        $this->line("  ⏭ {$strasse} → {$existing->sort_key} (manuell, übersprungen)");
                    }
                    continue;
                }
                $existing->update(['sort_key' => $sortKey]);
                $updated++;
            } else {
                StrassenMapping::create([
                    'strasse_original' => $strasse,
                    'sort_key' => $sortKey,
                    'is_manual' => false,
                ]);
                $created++;
            }

            if ($analyze) {
                $this->line("  {$strasse} → {$sortKey}");
            }
        }

        $this->line('');
        $this->info("Mappings: {$created} neu, {$updated} aktualisiert, {$skipped} manuell übersprungen");

        // 3. Sort-Keys auf allen Gebäuden setzen
        if (!$dryRun) {
            $this->line('');
            $this->info('Aktualisiere Gebäude sort_keys...');

            $count = 0;
            Gebaeude::whereNotNull('strasse')
                ->where('strasse', '!=', '')
                ->chunk(200, function ($gebaeude) use ($normalizer, &$count) {
                    foreach ($gebaeude as $g) {
                        $sortKey = $normalizer->resolve($g->strasse);
                        $hnSortKey = $normalizer->normalizeHausnummer($g->hausnummer);

                        if ($g->strasse_sort_key !== $sortKey || $g->hausnummer_sort_key !== $hnSortKey) {
                            $g->update([
                                'strasse_sort_key' => $sortKey,
                                'hausnummer_sort_key' => $hnSortKey,
                            ]);
                            $count++;
                        }
                    }
                });

            $this->info("✅ {$count} Gebäude aktualisiert");
        } else {
            $this->warn('[DRY-RUN] Nichts gespeichert');
        }

        // 4. Gruppierung anzeigen
        $this->line('');
        $this->info('Gruppierung nach Sort-Key:');
        $this->line('');

        $groups = StrassenMapping::select('sort_key')
            ->selectRaw('COUNT(*) as anzahl')
            ->selectRaw('GROUP_CONCAT(strasse_original SEPARATOR ", ") as varianten')
            ->groupBy('sort_key')
            ->having('anzahl', '>', 1)
            ->orderBy('sort_key')
            ->get();

        if ($groups->isEmpty()) {
            $this->line('  Keine Straßen mit mehreren Schreibweisen gefunden.');
        } else {
            foreach ($groups as $group) {
                $gebaeudeCount = Gebaeude::where('strasse_sort_key', $group->sort_key)->count();
                $this->line("  <fg=yellow>{$group->sort_key}</> ({$group->anzahl} Varianten, {$gebaeudeCount} Gebäude)");
                $this->line("    → {$group->varianten}");
            }
        }

        $this->line('');
        return Command::SUCCESS;
    }
}
