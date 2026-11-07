<?php
/*
════════════════════════════════════════════════════════════════════════════
DATEI: TagesinkassiPdfController.php
PFAD:  app/Http/Controllers/TagesinkassiPdfController.php
════════════════════════════════════════════════════════════════════════════
*/

namespace App\Http\Controllers;

use App\Services\RegistroCorrispettiviPdf;

class TagesinkassiPdfController extends Controller
{
    public function __construct(private RegistroCorrispettiviPdf $registro) {}

    /** Registro für einen Monat */
    public function monat(int $jahr, int $monat)
    {
        abort_unless($monat >= 1 && $monat <= 12, 404);

        return $this->anzeigen($this->registro->monat($jahr, $monat));
    }

    /** Alle Monate eines Jahres + Jahresübersicht */
    public function jahr(int $jahr)
    {
        return $this->anzeigen($this->registro->jahr($jahr));
    }

    private function anzeigen(array $pdf)
    {
        return response($pdf['inhalt'], 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdf['dateiname'] . '"',
        ]);
    }
}
