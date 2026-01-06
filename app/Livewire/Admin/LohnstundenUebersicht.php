<?php
/**
 * ════════════════════════════════════════════════════════════════════════════
 * DATEI: LohnstundenUebersicht.php
 * PFAD:  app/Livewire/Admin/LohnstundenUebersicht.php
 * ════════════════════════════════════════════════════════════════════════════
 */

namespace App\Livewire\Admin;

use App\Models\Lohnstunde;
use App\Models\User;
use App\Models\Unternehmensprofil;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mime\Email;
use Carbon\Carbon;

#[Layout('layouts.app')]
#[Title('Lohnstunden-Übersicht')]
class LohnstundenUebersicht extends Component
{
    // Filter
    public $selectedMitarbeiter = '';
    public $selectedMonat;
    public $selectedJahr;

    // Bearbeiten Modal
    public $showEditModal = false;
    public $editId = null;
    public $editDatum;
    public $editTyp;
    public $editStunden;
    public $editNotizen;

    // E-Mail Modal
    public $showEmailModal = false;
    public $emailAdresse = '';
    public $emailBetreff = '';
    public $emailNachricht = '';

    // Flash Messages
    public $successMessage = '';
    public $errorMessage = '';

    public function mount()
    {
        $this->selectedMonat = now()->month;
        $this->selectedJahr = now()->year;
        
        // Letzte E-Mail-Adresse aus Cache laden (pro User)
        $this->emailAdresse = Cache::get('lohnstunden_email_' . auth()->id(), '');
    }

    /**
     * Mitarbeiter für Dropdown holen
     */
    public function getMitarbeiterProperty()
    {
        return User::where('role', 'mitarbeiter')
            ->orderBy('name')
            ->get();
    }

    /**
     * Lohnstunden-Daten für die Tabelle
     */
    public function getLohnstundenProperty()
    {
        $query = Lohnstunde::with('user')
            ->monat($this->selectedMonat, $this->selectedJahr)
            ->orderBy('datum', 'desc');

        if ($this->selectedMitarbeiter) {
            $query->where('user_id', $this->selectedMitarbeiter);
        }

        return $query->get();
    }

    /**
     * Daten für die Monatsübersicht (Tage als Spalten)
     */
    public function getMonatsUebersichtProperty()
    {
        $mitarbeiterIds = $this->selectedMitarbeiter 
            ? [$this->selectedMitarbeiter] 
            : $this->mitarbeiter->pluck('id')->toArray();

        $data = [];
        $anzahlTage = Carbon::create($this->selectedJahr, $this->selectedMonat)->daysInMonth;

        foreach ($mitarbeiterIds as $userId) {
            $user = User::find($userId);
            if (!$user) continue;

            $row = [
                'user' => $user,
                'tage' => [],
                'summen' => []
            ];

            // Alle Lohnstunden des Mitarbeiters für diesen Monat
            $eintraege = Lohnstunde::where('user_id', $userId)
                ->monat($this->selectedMonat, $this->selectedJahr)
                ->get()
                ->groupBy(function ($item) {
                    return $item->datum->day;
                });

            // Für jeden Tag
            for ($tag = 1; $tag <= $anzahlTage; $tag++) {
                $tagesEintraege = $eintraege->get($tag, collect());
                $row['tage'][$tag] = $tagesEintraege;
            }

            // Summen pro Typ berechnen
            $allEntries = Lohnstunde::where('user_id', $userId)
                ->monat($this->selectedMonat, $this->selectedJahr)
                ->get();

            foreach (Lohnstunde::getTypen() as $typ => $label) {
                $row['summen'][$typ] = $allEntries->where('typ', $typ)->sum('stunden');
            }

            $row['total'] = $allEntries->sum('stunden');

            $data[] = $row;
        }

        return $data;
    }

    /**
     * Eintrag bearbeiten - Modal öffnen
     */
    public function editEintrag($id)
    {
        $eintrag = Lohnstunde::findOrFail($id);
        
        $this->editId = $eintrag->id;
        $this->editDatum = $eintrag->datum->format('Y-m-d');
        $this->editTyp = $eintrag->typ;
        $this->editStunden = $eintrag->stunden;
        $this->editNotizen = $eintrag->notizen;
        
        $this->showEditModal = true;
    }

    /**
     * Eintrag speichern
     */
    public function saveEintrag()
    {
        $this->validate([
            'editDatum' => 'required|date',
            'editTyp' => 'required|string',
            'editStunden' => 'required|numeric|min:0|max:24',
        ]);

        $eintrag = Lohnstunde::findOrFail($this->editId);
        $eintrag->update([
            'datum' => $this->editDatum,
            'typ' => $this->editTyp,
            'stunden' => $this->editStunden,
            'notizen' => $this->editNotizen,
        ]);

        $this->showEditModal = false;
        $this->resetEditForm();
        $this->successMessage = 'Eintrag erfolgreich aktualisiert!';
    }

    /**
     * Eintrag löschen
     */
    public function deleteEintrag($id)
    {
        Lohnstunde::findOrFail($id)->delete();
        $this->successMessage = 'Eintrag erfolgreich gelöscht!';
    }

    /**
     * Edit-Form zurücksetzen
     */
    private function resetEditForm()
    {
        $this->editId = null;
        $this->editDatum = null;
        $this->editTyp = null;
        $this->editStunden = null;
        $this->editNotizen = null;
    }

    /**
     * Excel-Export im Vorlagen-Format
     */
    public function exportExcel()
    {
        $spreadsheet = $this->createSpreadsheet();
        
        // Wenn nur ein Mitarbeiter ausgewählt, dessen Namen für Datei verwenden
        if ($this->selectedMitarbeiter) {
            $user = User::find($this->selectedMitarbeiter);
            $filename = $this->selectedMonat . '_' . $this->selectedJahr . '_' . str_replace(' ', '_', $user->name) . '.xlsx';
        } else {
            $filename = 'Lohnstunden_' . $this->selectedMonat . '_' . $this->selectedJahr . '.xlsx';
        }
        
        $path = storage_path('app/public/' . $filename);
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Spreadsheet im Vorlagen-Format erstellen
     */
    private function createSpreadsheet()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Lohnstunden');

        $anzahlTage = Carbon::create($this->selectedJahr, $this->selectedMonat)->daysInMonth;
        $monatName = Carbon::create($this->selectedJahr, $this->selectedMonat)->translatedFormat('F Y');

        // ════════════════════════════════════════════════════════════════
        // HEADER BEREICH
        // ════════════════════════════════════════════════════════════════
        
        // Zeile 5: Firma
        $sheet->setCellValue('A5', 'Firma:    Resch GmbH');
        $sheet->mergeCells('A5:L5');
        $sheet->getStyle('A5')->getFont()->setBold(true);

        // Zeile 7: Titel
        $sheet->setCellValue('C7', 'ARBEITSTAGE UND GELEISTETE ARBEITSSTUNDEN IM MONAT');
        $sheet->getStyle('C7')->getFont()->setBold(true);

        // Zeile 8: Monat
        $sheet->setCellValue('C8', 'Monat:');
        $sheet->setCellValue('D8', $monatName);
        $sheet->mergeCells('D8:G8');

        // Zeile 9: Jahr
        $sheet->setCellValue('C9', 'Jahr:');
        $sheet->setCellValue('D9', $this->selectedJahr);
        $sheet->mergeCells('D9:G9');

        // ════════════════════════════════════════════════════════════════
        // DATUMSZEILE (Zeile 11)
        // ════════════════════════════════════════════════════════════════
        
        $ersterTag = Carbon::create($this->selectedJahr, $this->selectedMonat, 1);
        $sheet->setCellValue('D11', $ersterTag);
        $sheet->getStyle('D11')->getNumberFormat()->setFormatCode('DD.MM.YYYY');
        
        // Formeln für folgende Tage
        for ($tag = 2; $tag <= 31; $tag++) {
            $col = $this->getColumnLetter($tag + 2); // D=Tag1, E=Tag2, etc.
            $prevCol = $this->getColumnLetter($tag + 1);
            $sheet->setCellValue($col . '11', "={$prevCol}11+1");
            $sheet->getStyle($col . '11')->getNumberFormat()->setFormatCode('DD.MM.YYYY');
        }

        // ════════════════════════════════════════════════════════════════
        // HEADER ZEILE (Zeile 12)
        // ════════════════════════════════════════════════════════════════
        
        $sheet->setCellValue('A12', 'Nr.');
        $sheet->setCellValue('B12', "\nZU- UND VORNAME");
        
        // Tage 1-31
        for ($tag = 1; $tag <= 31; $tag++) {
            $col = $this->getColumnLetter($tag + 2); // D=1, E=2, etc.
            $sheet->setCellValue($col . '12', $tag);
        }
        
        // Spalte AI (35) = Insgesamt Stunden
        $sheet->setCellValue('AI12', 'Insgesamt Stunden');
        $sheet->setCellValue('AJ12', "\nAnmerkungen");

        // Header-Styling
        $headerRange = 'A12:AJ12';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('D9D9D9');

        // ════════════════════════════════════════════════════════════════
        // MITARBEITER DATEN
        // ════════════════════════════════════════════════════════════════
        
        $currentRow = 13;
        $mitarbeiterNr = 1;

        foreach ($this->monatsUebersicht as $data) {
            $user = $data['user'];
            
            // Zeile 1: Normalstunden (No)
            $noRow = $currentRow;
            // Zeile 2: Überstunden (Üb)
            $uebRow = $currentRow + 1;

            // Nr. und Name (merged über 2 Zeilen)
            $sheet->setCellValue('A' . $noRow, $mitarbeiterNr);
            $sheet->mergeCells('A' . $noRow . ':A' . $uebRow);
            
            $sheet->setCellValue('B' . $noRow, $user->name);
            $sheet->mergeCells('B' . $noRow . ':B' . $uebRow);

            // Typ-Spalte
            $sheet->setCellValue('C' . $noRow, 'No');
            $sheet->setCellValue('C' . $uebRow, 'Üb');

            // Einträge pro Tag
            for ($tag = 1; $tag <= $anzahlTage; $tag++) {
                $col = $this->getColumnLetter($tag + 2);
                $datum = Carbon::create($this->selectedJahr, $this->selectedMonat, $tag);
                $tagesEintraege = $data['tage'][$tag] ?? collect();

                // Wochenende?
                if ($datum->isWeekend()) {
                    $sheet->setCellValue($col . $noRow, 'W');
                    $sheet->getStyle($col . $noRow)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E0E0E0');
                    $sheet->getStyle($col . $uebRow)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('E0E0E0');
                } else {
                    // Normale Einträge
                    $noStunden = 0;
                    $uebStunden = 0;
                    $sonderTyp = null;

                    foreach ($tagesEintraege as $eintrag) {
                        if ($eintrag->typ === 'No') {
                            $noStunden += $eintrag->stunden;
                        } elseif ($eintrag->typ === 'Üb') {
                            $uebStunden += $eintrag->stunden;
                        } else {
                            // Sondertypen (K, F, U, etc.)
                            $sonderTyp = $eintrag->typ;
                        }
                    }

                    // Normalstunden oder Sondertyp
                    if ($sonderTyp) {
                        $sheet->setCellValue($col . $noRow, $sonderTyp);
                    } elseif ($noStunden > 0) {
                        $sheet->setCellValue($col . $noRow, $noStunden);
                    }

                    // Überstunden
                    if ($uebStunden > 0) {
                        $sheet->setCellValue($col . $uebRow, $uebStunden);
                    }
                }
            }

            // Leere Tage für Monate mit weniger als 31 Tagen grau markieren
            for ($tag = $anzahlTage + 1; $tag <= 31; $tag++) {
                $col = $this->getColumnLetter($tag + 2);
                $sheet->getStyle($col . $noRow . ':' . $col . $uebRow)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('BFBFBF');
            }

            // Summen-Formel (Spalte AI)
            $sheet->setCellValue('AI' . $noRow, '=SUM(D' . $noRow . ':AH' . $noRow . ')');
            $sheet->setCellValue('AI' . $uebRow, '=SUM(D' . $uebRow . ':AH' . $uebRow . ')');

            // Rahmen für Mitarbeiter-Block
            $blockRange = 'A' . $noRow . ':AJ' . $uebRow;
            $sheet->getStyle($blockRange)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setRGB('000000');

            $currentRow += 2;
            $mitarbeiterNr++;
        }

        // ════════════════════════════════════════════════════════════════
        // LEGENDE (am Ende)
        // ════════════════════════════════════════════════════════════════
        
        $legendeStart = $currentRow + 3;
        
        $legende = [
            ['No = Normalstunden', 'K = Krankheit'],
            ['Üb = Überstunden', 'U = Unfall'],
            ['F = Ferien', 'S = Schule'],
            ['P = Permessi/Freistunden', 'M = Mutterschaft'],
            ['A = Abwesend', 'BS = Blutspende'],
            ['C = Lohnausgleich', 'H = Hochzeitsurlaub'],
        ];

        foreach ($legende as $index => $row) {
            $sheet->setCellValue('A' . ($legendeStart + $index), $row[0]);
            $sheet->setCellValue('D' . ($legendeStart + $index), $row[1]);
        }

        // ════════════════════════════════════════════════════════════════
        // SPALTENBREITEN
        // ════════════════════════════════════════════════════════════════
        
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(26);
        $sheet->getColumnDimension('C')->setWidth(7);
        
        // Tage-Spalten (D bis AH)
        for ($i = 4; $i <= 34; $i++) {
            $col = $this->getColumnLetter($i - 1);
            $sheet->getColumnDimension($col)->setWidth(4);
        }
        
        $sheet->getColumnDimension('AI')->setWidth(15);
        $sheet->getColumnDimension('AJ')->setWidth(15);

        // Zeilen-Alignment
        $sheet->getStyle('A12:AJ100')->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $sheet->getStyle('B12:B100')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        return $spreadsheet;
    }

    /**
     * Spalten-Buchstabe aus Index berechnen (1=A, 2=B, etc.)
     */
    private function getColumnLetter($index)
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intval($index / 26);
        }
        return $letter;
    }

    /**
     * E-Mail Modal öffnen
     */
    public function openEmailModal()
    {
        // Prüfen ob SMTP konfiguriert ist
        $profil = Unternehmensprofil::aktiv();
        if (!$profil || !$profil->hatSmtpKonfiguration()) {
            $this->errorMessage = 'E-Mail-Versand nicht konfiguriert. Bitte SMTP-Einstellungen im Unternehmensprofil hinterlegen.';
            return;
        }
        
        $monatName = Carbon::create($this->selectedJahr, $this->selectedMonat)->translatedFormat('F Y');
        $this->emailBetreff = 'Lohnstunden ' . $monatName;
        $this->emailNachricht = "Anbei die Lohnstunden-Übersicht für {$monatName}.\n\nMit freundlichen Grüßen\n" . ($profil->firmenname ?? '');
        
        // Letzte E-Mail-Adresse aus Cache laden (falls leer)
        if (empty($this->emailAdresse)) {
            $this->emailAdresse = Cache::get('lohnstunden_email_' . auth()->id(), '');
        }
        
        $this->showEmailModal = true;
    }

    /**
     * Excel per E-Mail senden (mit SMTP aus Datenbank)
     */
    public function sendEmail()
    {
        $this->validate([
            'emailAdresse' => 'required|email',
            'emailBetreff' => 'required|string|max:200',
        ]);

        // E-Mail-Adresse VOR dem Reset speichern für Erfolgsmeldung
        $gesendetAn = $this->emailAdresse;

        try {
            // ════════════════════════════════════════════════════════════
            // SMTP-Konfiguration aus Datenbank laden
            // ════════════════════════════════════════════════════════════
            $profil = Unternehmensprofil::aktiv();
            
            if (!$profil) {
                throw new \Exception('Kein aktives Unternehmensprofil gefunden.');
            }
            
            if (!$profil->hatSmtpKonfiguration()) {
                throw new \Exception('SMTP-Einstellungen im Unternehmensprofil nicht vollständig. Bitte Host, Port, Benutzername und Passwort hinterlegen.');
            }

            // E-Mail-Adresse im Cache speichern (30 Tage)
            Cache::put('lohnstunden_email_' . auth()->id(), $this->emailAdresse, now()->addDays(30));

            // ════════════════════════════════════════════════════════════
            // Excel erstellen
            // ════════════════════════════════════════════════════════════
            $spreadsheet = $this->createSpreadsheet();
            $monatName = Carbon::create($this->selectedJahr, $this->selectedMonat)->translatedFormat('F Y');
            $filename = 'Lohnstunden_' . str_replace(' ', '_', $monatName) . '.xlsx';
            $path = storage_path('app/temp/' . $filename);
            
            // Temp-Verzeichnis erstellen falls nicht vorhanden
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            $writer = new Xlsx($spreadsheet);
            $writer->save($path);

            // ════════════════════════════════════════════════════════════
            // Dynamischen Mailer mit DB-Konfiguration erstellen
            // ════════════════════════════════════════════════════════════
            Config::set('mail.mailers.unternehmen', [
                'transport' => 'smtp',
                'host' => $profil->smtp_host,
                'port' => $profil->smtp_port,
                'encryption' => $profil->smtp_verschluesselung, // 'ssl' oder 'tls'
                'username' => $profil->smtp_benutzername,
                'password' => $profil->smtp_passwort,
                'timeout' => null,
            ]);

            // From-Adresse setzen
            $fromAddress = $profil->email_absender ?? $profil->email ?? $profil->smtp_benutzername;
            $fromName = $profil->email_absender_name ?? $profil->firmenname ?? 'Resch GmbH';

            // ════════════════════════════════════════════════════════════
            // E-Mail senden mit dynamischem Mailer
            // ════════════════════════════════════════════════════════════
            Mail::mailer('unternehmen')
                ->send([], [], function ($message) use ($path, $filename, $fromAddress, $fromName, $profil) {
                    $message->from($fromAddress, $fromName)
                        ->to($this->emailAdresse)
                        ->subject($this->emailBetreff)
                        ->text($this->emailNachricht . ($profil->email_signatur ? "\n\n" . $profil->email_signatur : ''))
                        ->attach($path, [
                            'as' => $filename,
                            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ]);
                    
                    // CC hinzufügen falls vorhanden
                    if ($profil->email_cc) {
                        $message->cc($profil->email_cc);
                    }
                    
                    // BCC hinzufügen falls vorhanden
                    if ($profil->email_bcc) {
                        $message->bcc($profil->email_bcc);
                    }
                });

            // Temp-Datei löschen
            if (file_exists($path)) {
                unlink($path);
            }

            $this->showEmailModal = false;
            
            // Nur Betreff und Nachricht zurücksetzen, E-Mail-Adresse behalten!
            $this->emailBetreff = '';
            $this->emailNachricht = '';
            
            // Erfolgsmeldung MIT der gespeicherten E-Mail-Adresse
            $this->successMessage = 'E-Mail erfolgreich gesendet an ' . $gesendetAn;

        } catch (\Exception $e) {
            $this->errorMessage = 'Fehler beim Senden: ' . $e->getMessage();
        }
    }

    /**
     * Badge-Klasse basierend auf Typ zurückgeben
     */
    public function getTypBadgeClass($typ)
    {
        return match($typ) {
            'No' => 'bg-primary',
            'Üb' => 'bg-warning text-dark',
            'F'  => 'bg-success',
            'P'  => 'bg-info',
            'A'  => 'bg-secondary',
            'C'  => 'bg-dark',
            'K'  => 'bg-danger',
            'U'  => 'bg-danger',
            'S'  => 'bg-info',
            'M'  => 'bg-pink',
            'BS' => 'bg-danger',
            'H'  => 'bg-success',
            default => 'bg-secondary'
        };
    }

    public function render()
    {
        return view('livewire.admin.lohnstunden-uebersicht', [
            'typen' => Lohnstunde::getTypen(),
            'monate' => [
                1 => 'Januar', 2 => 'Februar', 3 => 'März',
                4 => 'April', 5 => 'Mai', 6 => 'Juni',
                7 => 'Juli', 8 => 'August', 9 => 'September',
                10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
            ],
            'jahre' => range(now()->year - 2, now()->year + 1),
            'anzahlTage' => Carbon::create($this->selectedJahr, $this->selectedMonat)->daysInMonth,
        ]);
    }
}
