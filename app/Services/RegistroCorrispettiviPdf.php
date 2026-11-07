<?php
/*
════════════════════════════════════════════════════════════════════════════
DATEI: RegistroCorrispettiviPdf.php
PFAD:  app/Services/RegistroCorrispettiviPdf.php
(Erzeugt das Registro-PDF – genutzt von Controller (Anzeige) und Email-Versand)
════════════════════════════════════════════════════════════════════════════
*/

namespace App\Services;

use App\Models\Tagesinkasso;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class RegistroCorrispettiviPdf
{
    /**
     * @return array{inhalt: string, dateiname: string, label_de: string, label_it: string}
     */
    public function monat(int $jahr, int $monat): array
    {
        $seite = $this->baueMonat($jahr, $monat);

        return [
            'inhalt'    => $this->render([$seite], null),
            'dateiname' => sprintf('Registro_corrispettivi_%04d-%02d.pdf', $jahr, $monat),
            'label_de'  => $seite['label_de'],
            'label_it'  => $seite['label_it'],
        ];
    }

    /**
     * Alle Monate mit Einträgen + Jahresübersicht
     * @return array{inhalt: string, dateiname: string, label_de: string, label_it: string}
     */
    public function jahr(int $jahr): array
    {
        $alle = [];
        for ($m = 1; $m <= 12; $m++) {
            $alle[] = $this->baueMonat($jahr, $m);
        }

        $seiten = array_values(array_filter($alle, fn($s) => $s['eintraege']->isNotEmpty()));

        $s10 = round(array_sum(array_column($alle, 'summe10')), 2);
        $s22 = round(array_sum(array_column($alle, 'summe22')), 2);

        $uebersicht = [
            'jahr'    => $jahr,
            'monate'  => $alle,
            'summe10' => $s10,
            'summe22' => $s22,
            'summe'   => round($s10 + $s22, 2),
            'scorp10' => Tagesinkasso::scorporo($s10, 10),
            'scorp22' => Tagesinkasso::scorporo($s22, 22),
        ];

        return [
            'inhalt'    => $this->render($seiten, $uebersicht),
            'dateiname' => sprintf('Registro_corrispettivi_%04d.pdf', $jahr),
            'label_de'  => "Jahr {$jahr}",
            'label_it'  => "anno {$jahr}",
        ];
    }

    private function baueMonat(int $jahr, int $monat): array
    {
        $eintraege = Tagesinkasso::imMonat($jahr, $monat)->orderBy('datum')->get();
        $datum     = Carbon::create($jahr, $monat, 1);

        $s10 = round((float) $eintraege->sum('betrag_10'), 2);
        $s22 = round((float) $eintraege->sum('betrag_22'), 2);

        return [
            'jahr'      => $jahr,
            'monat'     => $monat,
            'label_de'  => $datum->copy()->locale('de')->isoFormat('MMMM YYYY'),
            'label_it'  => ucfirst($datum->copy()->locale('it')->isoFormat('MMMM YYYY')),
            'pagina'    => sprintf('%02d-%04d', $monat, $jahr),
            'eintraege' => $eintraege,
            'summe10'   => $s10,
            'summe22'   => $s22,
            'summe'     => round($s10 + $s22, 2),
            'scorp10'   => Tagesinkasso::scorporo($s10, 10),
            'scorp22'   => Tagesinkasso::scorporo($s22, 22),
        ];
    }

    private function render(array $seiten, ?array $uebersicht): string
    {
        return Pdf::loadView('pdf.registro-corrispettivi', [
                'seiten'     => $seiten,
                'uebersicht' => $uebersicht,
            ])
            ->setPaper('a4', 'portrait')
            ->output();
    }
}
