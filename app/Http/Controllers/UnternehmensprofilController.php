<?php

namespace App\Http\Controllers;

use App\Models\Unternehmensprofil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Unternehmensprofil Controller
 * 
 * Verwaltet alle Firmeneinstellungen zentral.
 */
class UnternehmensprofilController extends Controller
{
    /**
     * Zeigt das aktive Profil.
     */
    public function index()
    {
        $profil = Unternehmensprofil::aktiv() ?? new Unternehmensprofil();
        
        return view('einstellungen.profil.index', compact('profil'));
    }

    /**
     * Formular zum Bearbeiten.
     */
    public function bearbeiten()
    {
        $profil = Unternehmensprofil::aktiv() ?? new Unternehmensprofil();
        
        // Dropdown-Optionen
        $regimeFiscaleOptionen = Unternehmensprofil::getRegimeFiscaleOptionen();
        $verschluesselungOptionen = ['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'Keine'];
        $spraachen = ['de' => 'Deutsch', 'it' => 'Italienisch', 'en' => 'Englisch'];
        $waehrungen = ['EUR' => 'Euro (EUR)', 'USD' => 'US-Dollar (USD)', 'CHF' => 'Schweizer Franken (CHF)'];
        
        return view('einstellungen.profil.bearbeiten', compact(
            'profil',
            'regimeFiscaleOptionen',
            'verschluesselungOptionen',
            'spraachen',
            'waehrungen'
        ));
    }

    /**
     * Speichert das Profil.
     */
    public function speichern(Request $request)
    {
        $validated = $request->validate([
            // Firmendaten
            'firmenname' => 'required|string|max:255',
            'firma_zusatz' => 'nullable|string|max:255',
            'geschaeftsfuehrer' => 'nullable|string|max:255',
            'handelsregister' => 'nullable|string|max:255',
            'registergericht' => 'nullable|string|max:255',
            
            // Adresse
            'strasse' => 'required|string|max:255',
            'hausnummer' => 'required|string|max:10',
            'adresszusatz' => 'nullable|string|max:255',
            'postleitzahl' => 'required|string|max:10',
            'ort' => 'required|string|max:255',
            'bundesland' => 'nullable|string|max:255',
            'land' => 'required|string|max:2',
            
            // Kontakt
            'telefon' => 'nullable|string|max:30',
            'telefon_mobil' => 'nullable|string|max:30',
            'fax' => 'nullable|string|max:30',
            'email' => 'required|email|max:255',
            'email_buchhaltung' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            
            // Steuern
            'steuernummer' => 'nullable|string|max:255',
            'umsatzsteuer_id' => 'nullable|string|max:255',
            
            // Bank
            'bank_name' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:34',
            'bic' => 'nullable|string|max:11',
            'kontoinhaber' => 'nullable|string|max:255',
            
            // E-Mail Versand
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer',
            'smtp_verschluesselung' => 'nullable|string|in:tls,ssl,none',
            'smtp_benutzername' => 'nullable|string|max:255',
            'smtp_passwort' => 'nullable|string|max:255',
            'email_absender' => 'nullable|email|max:255',
            'email_absender_name' => 'nullable|string|max:255',
            'email_antwort_an' => 'nullable|email|max:255',
            'email_cc' => 'nullable|string|max:500',
            'email_bcc' => 'nullable|string|max:500',
            'email_signatur' => 'nullable|string',
            'email_fusszeile' => 'nullable|string',
            
            // PDF/Design
            'logo_breite' => 'nullable|integer|min:50|max:1000',
            'logo_hoehe' => 'nullable|integer|min:50|max:1000',
            'briefkopf_text' => 'nullable|string',
            'briefkopf_rechts' => 'nullable|string',
            'fusszeile_text' => 'nullable|string',
            'farbe_primaer' => 'nullable|string|max:7',
            'farbe_sekundaer' => 'nullable|string|max:7',
            'farbe_akzent' => 'nullable|string|max:7',
            'schriftart' => 'nullable|string|max:50',
            'schriftgroesse' => 'nullable|integer|min:6|max:20',
            
            // Rechnungen
            'rechnungsnummer_praefix' => 'nullable|string|max:10',
            'rechnungsnummer_startjahr' => 'nullable|integer|min:2020|max:2099',
            'rechnungsnummer_laenge' => 'nullable|integer|min:1|max:10',
            'zahlungsziel_tage' => 'nullable|integer|min:1|max:365',
            'zahlungshinweis' => 'nullable|string',
            'kleinunternehmer_hinweis' => 'nullable|string',
            'rechnung_einleitung' => 'nullable|string',
            'rechnung_schlusstext' => 'nullable|string',
            'rechnung_agb_text' => 'nullable|string',
            
            // FatturaPA
            'ragione_sociale' => 'nullable|string|max:255',
            'partita_iva' => 'nullable|string|max:11',
            'codice_fiscale' => 'nullable|string|max:16',
            'regime_fiscale' => 'nullable|string|max:4',
            'pec_email' => 'nullable|email|max:255',
            'rea_ufficio' => 'nullable|string|max:2',
            'rea_numero' => 'nullable|string|max:20',
            'capitale_sociale' => 'nullable|numeric|min:0',
            'stato_liquidazione' => 'nullable|in:LN,LS',
            
            // System
            'waehrung' => 'nullable|string|max:3',
            'sprache' => 'nullable|string|max:2',
            'zeitzone' => 'nullable|string|max:50',
            'datumsformat' => 'nullable|string|max:20',
            'zahlenformat' => 'nullable|string|max:20',
            'ist_kleinunternehmer' => 'nullable|boolean',
            'mwst_ausweisen' => 'nullable|boolean',
            'standard_mwst_satz' => 'nullable|numeric|min:0|max:100',
            'notizen' => 'nullable|string',
        ]);

        // Profil laden oder neu erstellen
        $profil = Unternehmensprofil::aktiv() ?? new Unternehmensprofil();
        $profil->fill($validated);
        $profil->ist_aktiv = true;
        $profil->save();

        return redirect()
            ->route('unternehmensprofil.index')
            ->with('success', 'Unternehmensprofil erfolgreich gespeichert.');
    }

    /**
     * Logo hochladen.
     */
    public function logoHochladen(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'typ' => 'required|in:haupt,rechnung,email',
        ]);

        $profil = Unternehmensprofil::aktivOderFehler();
        
        $pfad = $profil->logoHochladen($request->file('logo'), $request->typ);

        return back()->with('success', 'Logo erfolgreich hochgeladen.');
    }

    /**
     * Logo löschen.
     */
    public function logoLoeschen(Request $request)
    {
        $request->validate([
            'typ' => 'required|in:haupt,rechnung,email',
        ]);

        $profil = Unternehmensprofil::aktivOderFehler();
        
        $feldname = match($request->typ) {
            'rechnung' => 'logo_rechnung_pfad',
            'email' => 'logo_email_pfad',
            default => 'logo_pfad',
        };

        if ($profil->$feldname) {
            Storage::delete($profil->$feldname);
            $profil->$feldname = null;
            $profil->save();
        }

        return back()->with('success', 'Logo erfolgreich gelöscht.');
    }

    /**
     * SMTP-Verbindung testen.
     */
    public function smtpTesten(Request $request)
    {
        $profil = Unternehmensprofil::aktivOderFehler();
        
        if (!$profil->hatSmtpKonfiguration()) {
            return back()->with('error', 'SMTP-Konfiguration ist unvollständig.');
        }

        try {
            // Test-E-Mail an die Haupt-E-Mail senden
            \Mail::raw('Dies ist eine Test-E-Mail von Ihrer FatturaPA-Anwendung.', function ($message) use ($profil) {
                $message->to($profil->email)
                    ->subject('SMTP-Test - FatturaPA System');
            });

            return back()->with('success', 'Test-E-Mail erfolgreich versendet! Prüfen Sie Ihren Posteingang.');
        } catch (\Exception $e) {
            return back()->with('error', 'SMTP-Test fehlgeschlagen: ' . $e->getMessage());
        }
    }

    /**
     * Dupliziert das aktive Profil (für Backup).
     */
    public function duplizieren()
    {
        $original = Unternehmensprofil::aktivOderFehler();
        
        $duplikat = $original->replicate();
        $duplikat->ist_aktiv = false;
        $duplikat->firmenname = $original->firmenname . ' (Kopie)';
        $duplikat->save();

        return back()->with('success', 'Profil erfolgreich dupliziert.');
    }

    /**
     * Exportiert das Profil als JSON (Backup).
     */
    public function exportieren()
    {
        $profil = Unternehmensprofil::aktivOderFehler();
        
        $data = $profil->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at']);
        
        $filename = 'unternehmensprofil_' . date('Y-m-d_His') . '.json';
        
        return response()->json($data, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Importiert ein Profil aus JSON.
     */
    public function importieren(Request $request)
    {
        $request->validate([
            'datei' => 'required|file|mimes:json',
        ]);

        $content = file_get_contents($request->file('datei')->getRealPath());
        $data = json_decode($content, true);

        if (!$data) {
            return back()->with('error', 'Ungültige JSON-Datei.');
        }

        // Aktuelles Profil deaktivieren
        Unternehmensprofil::where('ist_aktiv', true)->update(['ist_aktiv' => false]);

        // Neues Profil erstellen
        $profil = Unternehmensprofil::create(array_merge($data, ['ist_aktiv' => true]));

        return redirect()
            ->route('unternehmensprofil.index')
            ->with('success', 'Profil erfolgreich importiert.');
    }
}