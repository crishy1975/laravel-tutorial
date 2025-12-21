<?php

use Illuminate\Support\Facades\Route;

/* ===== Controller Imports ===== */
use App\Http\Controllers\ToolsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdresseController;
use App\Http\Controllers\GebaeudeController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\ArtikelGebaeudeController;
use App\Http\Controllers\RechnungController;
use App\Http\Controllers\PreisAufschlagController;
use App\Http\Controllers\UnternehmensprofilController;
use App\Http\Controllers\ReinigungsplanungController;
use App\Http\Controllers\BankBuchungController;
use App\Http\Controllers\MahnungController;
use App\Http\Controllers\AngebotController;


// Home / Dashboard Redirect
Route::get('/', fn() => redirect()->route('gebaeude.index'))
    ->middleware(['auth', 'verified'])
    ->name('home');



// ==================== Profile Routes ====================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


/* ==================== Tools (z. B. VIES) ==================== */
Route::post('/tools/vies-lookup', [ToolsController::class, 'viesLookup'])
    ->middleware(['auth', 'verified'])
    ->name('tools.viesLookup');


/* ==================== Adressen ==================== */
Route::middleware(['auth', 'verified'])
    ->prefix('adresse')
    ->name('adresse.')
    ->group(function () {
        // Liste & CRUD
        Route::get('/',        [AdresseController::class, 'index'])->name('index');
        Route::get('/create',  [AdresseController::class, 'create'])->name('create');
        Route::post('/',       [AdresseController::class, 'store'])->name('store');

        // Optional: Bulk-Löschen
        Route::post('/bulk-destroy', [AdresseController::class, 'bulkDestroy'])->name('bulkDestroy');

        // Optional: JSON-Detail
        Route::get('/{id}/json', [AdresseController::class, 'showJson'])
            ->whereNumber('id')->name('json');

        // Show / Edit / Update / Delete
        Route::get('/{id}',      [AdresseController::class, 'show'])
            ->whereNumber('id')->name('show');

        Route::get('/{id}/edit', [AdresseController::class, 'edit'])
            ->whereNumber('id')->name('edit');

        Route::put('/{id}',      [AdresseController::class, 'update'])
            ->whereNumber('id')->name('update');

        Route::delete('/{id}',   [AdresseController::class, 'destroy'])
            ->whereNumber('id')->name('destroy');
    });


/* ==================== Gebäude ==================== */
Route::middleware(['auth', 'verified'])
    ->prefix('gebaeude')
    ->name('gebaeude.')
    ->group(function () {
        // Liste & CRUD
        Route::get('/',        [GebaeudeController::class, 'index'])->name('index');
        Route::get('/create',  [GebaeudeController::class, 'create'])->name('create');
        Route::post('/',       [GebaeudeController::class, 'store'])->name('store');

        Route::get('/{id}/edit', [GebaeudeController::class, 'edit'])
            ->whereNumber('id')->name('edit');

        Route::put('/{id}',      [GebaeudeController::class, 'update'])
            ->whereNumber('id')->name('update');

        Route::delete('/{id}',   [GebaeudeController::class, 'destroy'])
            ->whereNumber('id')->name('destroy');

        // 🔗 Touren: Bulk-Attach
        Route::post('/touren/bulk-attach', [GebaeudeController::class, 'bulkAttachTour'])
            ->name('touren.bulkAttach');

        // 🧾 Artikel-Positionen (pro Gebäude)
        Route::post('/{id}/artikel',         [ArtikelGebaeudeController::class, 'store'])
            ->whereNumber('id')->name('artikel.store');

        Route::post('/{id}/artikel/reorder', [ArtikelGebaeudeController::class, 'reorder'])
            ->whereNumber('id')->name('artikel.reorder');

        // Reinigungen zurücksetzen
        Route::post('/reset-gemachte-reinigungen', [GebaeudeController::class, 'resetGemachteReinigungen'])
            ->name('resetGemachteReinigungen');

        // Fälligkeit berechnen
        Route::post('/{id}/faellig/recalc', [GebaeudeController::class, 'recalcFaelligkeit'])
            ->whereNumber('id')
            ->name('faellig.recalc');

        Route::post('/faellig/recalc-all', [GebaeudeController::class, 'recalcFaelligAll'])
            ->name('faellig.recalcAll');

        // Rechnung aus Gebäude erstellen
        Route::post('/{id}/rechnung', [GebaeudeController::class, 'createRechnung'])
            ->whereNumber('id')
            ->name('rechnung.create');
    });

// Gebäude Aufschlag-Routes (DIREKT danach, NICHT in einer Gruppe!)
Route::post('gebaeude/{gebaeude}/aufschlag', [GebaeudeController::class, 'setAufschlag'])
    ->middleware(['auth'])
    ->name('gebaeude.aufschlag.set');

Route::delete('gebaeude/{gebaeude}/aufschlag', [GebaeudeController::class, 'removeAufschlag'])
    ->middleware(['auth'])
    ->name('gebaeude.aufschlag.remove');

Route::get('gebaeude/{gebaeude}/aufschlag', [GebaeudeController::class, 'getAufschlag'])
    ->middleware(['auth'])
    ->name('gebaeude.aufschlag.get');

// 🧾 Artikel-Positionen (Einzel-ID: Update/Destroy)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::put('/artikel-gebaeude/{id}',    [ArtikelGebaeudeController::class, 'update'])
        ->whereNumber('id')->name('artikel.gebaeude.update');

    Route::delete('/artikel-gebaeude/{id}', [ArtikelGebaeudeController::class, 'destroy'])
        ->whereNumber('id')->name('artikel.gebaeude.destroy');
});


// ══════════════════════════════════════════════════════════
// GEBÄUDE - TIMELINE
// ══════════════════════════════════════════════════════════
Route::post('/gebaeude/{id}/timeline', [TimelineController::class, 'timelineStore'])
    ->middleware(['auth', 'verified'])
    ->whereNumber('id')->name('gebaeude.timeline.store');

// Löschen eines Timeline-Eintrags
Route::delete('/timeline/{id}', [TimelineController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->whereNumber('id')->name('timeline.destroy');

// Verrechnen-Flag toggeln (AJAX)
Route::patch('/timeline/{id}/verrechnen', [TimelineController::class, 'toggleVerrechnen'])
    ->middleware(['auth', 'verified'])
    ->whereNumber('id')
    ->name('timeline.toggleVerrechnen');

// ══════════════════════════════════════════════════════════
// TOUREN
// ══════════════════════════════════════════════════════════
Route::middleware(['auth', 'verified'])->group(function () {

    // Reihung aller Touren (Drag&Drop)
    Route::patch('/tour/reorder', [TourController::class, 'reorder'])
        ->name('tour.reorder');

    // Aktiv-Flag toggeln
    Route::patch('/tour/{tour}/toggle', [TourController::class, 'toggleActive'])
        ->whereNumber('tour')->name('tour.toggle');

    // Show (separat, da Resource 'show' ausgeschlossen wird)
    Route::get('/tour/{id}', [TourController::class, 'show'])
        ->whereNumber('id')->name('tour.show');

    // 🔗 Tour ⇄ Gebäude: NUR Pivot löschen

    // Einzel-Detach (eine Verknüpfung entfernen)
    Route::delete('/tour/{tour}/gebaeude/{gebaeude}', [TourController::class, 'detach'])
        ->whereNumber('tour')->whereNumber('gebaeude')
        ->name('tour.gebaeude.detach');

    // Bulk-Detach (mehrere Verknüpfungen gleichzeitig)
    Route::delete('/tour/{tour}/gebaeude', [TourController::class, 'bulkDetach'])
        ->whereNumber('tour')
        ->name('tour.gebaeude.bulkDetach');

    // Resource (ohne show) → index/create/store/edit/update/destroy
    Route::resource('tour', TourController::class)->except(['show']);
});


// ══════════════════════════════════════════════════════════
// RECHNUNGEN
// ══════════════════════════════════════════════════════════



// Rechnungen Routes (falls noch nicht vorhanden)
Route::prefix('rechnung')->name('rechnung.')->middleware(['auth'])->group(function () {

    // Übersicht (falls noch nicht vorhanden)
    Route::get('/', [RechnungController::class, 'index'])->name('index');

    // Erstellen/Bearbeiten
    Route::get('/create', [RechnungController::class, 'create'])->name('create');
    Route::post('/store', [RechnungController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [RechnungController::class, 'edit'])->name('edit');
    Route::put('/{id}', [RechnungController::class, 'update'])->name('update');
    Route::delete('/{id}', [RechnungController::class, 'destroy'])->name('destroy');

    // ⭐ PDF ROUTES (FEHLTEN!)
    Route::get('/{id}/pdf', [RechnungController::class, 'generatePdf'])->name('pdf');
    Route::get('/{id}/pdf/preview', [RechnungController::class, 'previewPdf'])->name('pdf.preview');
    Route::get('/{id}/pdf/download', [RechnungController::class, 'downloadPdf'])->name('pdf.download');

    // Status ändern
    Route::post('/{id}/status', [RechnungController::class, 'updateStatus'])->name('status.update');

    // FatturaPA XML
    Route::get('/{id}/xml', [RechnungController::class, 'generateXml'])->name('xml');
    Route::get('/{id}/xml/download', [RechnungController::class, 'downloadXml'])->name('xml.download');
    Route::post('/{id}/xml/send', [RechnungController::class, 'sendXml'])->name('xml.send');
    Route::post('/{id}/mark-sent', [App\Http\Controllers\RechnungController::class, 'markAsSent'])->name('mark-sent');
});

// ═══════════════════════════════════════════════════════════
// FATTURAPA XML LOG MANAGEMENT (direkt über Log-ID)
// ═══════════════════════════════════════════════════════════
Route::middleware(['auth'])->prefix('fattura-xml')->name('fattura.xml.')->group(function () {
    // Download über Log-ID
    Route::get('{logId}/download', [RechnungController::class, 'downloadXmlByLog'])->name('download');

    // Log löschen
    Route::delete('{logId}', [RechnungController::class, 'deleteXmlLog'])->name('delete');
});

// ═══════════════════════════════════════════════════════════
// PREIS-AUFSCHLÄGE
// ═══════════════════════════════════════════════════════════

Route::middleware(['auth'])->prefix('preis-aufschlaege')->name('preis-aufschlaege.')->group(function () {
    Route::get('/', [PreisAufschlagController::class, 'index'])->name('index');
    Route::post('/global', [PreisAufschlagController::class, 'storeGlobal'])->name('store-global');
    Route::delete('/global/{id}', [PreisAufschlagController::class, 'destroyGlobal'])->name('destroy-global');
    Route::post('/preview', [PreisAufschlagController::class, 'preview'])->name('preview');
});

// ══════════════════════════════════════════════════════════
// UNTERNEHMENSPROFIL
// ══════════════════════════════════════════════════════════  

// routes/web.php
// ⭐ Unternehmensprofil Routes hinzufügen

// ═══════════════════════════════════════════════════════════════════════════
// UNTERNEHMENSPROFIL
// ═══════════════════════════════════════════════════════════════════════════
Route::prefix('einstellungen/profil')->name('unternehmensprofil.')->group(function () {

    // Übersicht
    Route::get('/', [UnternehmensprofilController::class, 'index'])
        ->name('index');

    // Bearbeiten
    Route::get('/bearbeiten', [UnternehmensprofilController::class, 'bearbeiten'])
        ->name('bearbeiten');

    // Speichern
    Route::post('/speichern', [UnternehmensprofilController::class, 'speichern'])
        ->name('speichern');

    // Logo
    Route::post('/logo/hochladen', [UnternehmensprofilController::class, 'logoHochladen'])
        ->name('logo.hochladen');
    Route::delete('/logo/loeschen', [UnternehmensprofilController::class, 'logoLoeschen'])
        ->name('logo.loeschen');

    // SMTP Testen
    Route::get('/smtp/testen', [UnternehmensprofilController::class, 'smtpTesten'])
        ->name('smtp.testen');
    Route::get('/pec-smtp/testen', [UnternehmensprofilController::class, 'pecSmtpTesten'])
        ->name('pec-smtp.testen');

    // Backup/Import
    Route::post('/duplizieren', [UnternehmensprofilController::class, 'duplizieren'])
        ->name('duplizieren');
    Route::get('/exportieren', [UnternehmensprofilController::class, 'exportieren'])
        ->name('exportieren');
    Route::post('/importieren', [UnternehmensprofilController::class, 'importieren'])
        ->name('importieren');
});

// ═══════════════════════════════════════════════════════════════════════════
// RECHNUNG E-MAIL VERSAND
// ═══════════════════════════════════════════════════════════════════════════
Route::post('/rechnung/{id}/email/send', [RechnungController::class, 'sendEmail'])
    ->name('rechnung.email.send');
// ═══════════════════════════════════════════════════════════
// 🧾 FATTURAPA ROUTES
// ═══════════════════════════════════════════════════════════
// Diese Routes in routes/web.php einfügen (im RechnungController-Bereich)!

// FatturaPA XML Management
Route::prefix('rechnung/{id}')->name('rechnung.')->group(function () {

    // XML Generierung
    Route::post('xml/generate', [RechnungController::class, 'generateXml'])
        ->name('xml.generate');

    // XML Regenerierung (überschreibt altes)
    Route::post('xml/regenerate', [RechnungController::class, 'regenerateXml'])
        ->name('xml.regenerate');

    // XML Preview (ohne Speichern)
    Route::get('xml/preview', [RechnungController::class, 'previewXml'])
        ->name('xml.preview');

    // XML Download
    Route::get('xml/download', [RechnungController::class, 'downloadXml'])
        ->name('xml.download');

    // XML Logs anzeigen
    Route::get('xml/logs', [RechnungController::class, 'xmlLogs'])
        ->name('xml.logs');

    // Debug-Info
    Route::get('xml/debug', [RechnungController::class, 'debugXml'])
        ->name('xml.debug');
});

// FatturaXmlLog Management (direkt über Log-ID)
Route::prefix('fattura-xml')->name('fattura.xml.')->group(function () {

    // Download über Log-ID
    Route::get('{logId}/download', [RechnungController::class, 'downloadXmlByLog'])
        ->name('download');

    // Log löschen
    Route::delete('{logId}', [RechnungController::class, 'deleteXmlLog'])
        ->name('delete');
});

// ═══════════════════════════════════════════════════════════════════════════
// RECHNUNG LOG ROUTES
// In routes/web.php einfügen
// ═══════════════════════════════════════════════════════════════════════════

use App\Http\Controllers\RechnungLogController;

// Log-Übersicht für eine Rechnung
Route::get('/rechnung/{rechnung}/logs', [RechnungLogController::class, 'index'])
    ->name('rechnung.logs.index');

// Neuen Log-Eintrag erstellen
Route::post('/rechnung/{rechnung}/logs', [RechnungLogController::class, 'store'])
    ->name('rechnung.logs.store');

// Log-Eintrag aktualisieren
Route::put('/rechnung/logs/{log}', [RechnungLogController::class, 'update'])
    ->name('rechnung.logs.update');

// Log-Eintrag löschen
Route::delete('/rechnung/logs/{log}', [RechnungLogController::class, 'destroy'])
    ->name('rechnung.logs.destroy');

// Erinnerung als erledigt markieren
Route::post('/rechnung/logs/{log}/erledigt', [RechnungLogController::class, 'erinnerungErledigt'])
    ->name('rechnung.logs.erledigt');

// Quick-Actions
Route::post('/rechnung/{rechnung}/logs/telefonat', [RechnungLogController::class, 'quickTelefonat'])
    ->name('rechnung.logs.telefonat');

Route::post('/rechnung/{rechnung}/logs/notiz', [RechnungLogController::class, 'quickNotiz'])
    ->name('rechnung.logs.notiz');

Route::post('/rechnung/{rechnung}/logs/mitteilung', [RechnungLogController::class, 'quickMitteilung'])
    ->name('rechnung.logs.mitteilung');

// Dashboard (globale Übersicht)
Route::get('/rechnung-logs/dashboard', [RechnungLogController::class, 'dashboard'])
    ->name('rechnung.logs.dashboard');


// E-Mail Versand
Route::post('/rechnung/{id}/email/send', [RechnungController::class, 'sendEmail'])
    ->name('rechnung.email.send');

// Reinigungsplanung
Route::prefix('reinigungsplanung')->name('reinigungsplanung.')->group(function () {
    Route::get('/', [ReinigungsplanungController::class, 'index'])->name('index');
    Route::post('/{gebaeude}/erledigt', [ReinigungsplanungController::class, 'markErledigt'])->name('erledigt');
    Route::get('/export', [ReinigungsplanungController::class, 'export'])->name('export');
});



// ═══════════════════════════════════════════════════════════════════════════
// Bank-Buchungen
// ═══════════════════════════════════════════════════════════════════════════

Route::prefix('bank')->name('bank.')->middleware('auth')->group(function () {

    // Uebersicht
    Route::get('/', [BankBuchungController::class, 'index'])
        ->name('index');

    // Import
    Route::get('/import', [BankBuchungController::class, 'importForm'])
        ->name('import');
    Route::post('/import', [BankBuchungController::class, 'import'])
        ->name('import.store');

    // Nicht zugeordnete
    Route::get('/unmatched', [BankBuchungController::class, 'unmatched'])
        ->name('unmatched');

    // Zugeordnete (Kontroll-Übersicht)
    Route::get('/matched', [BankBuchungController::class, 'matched'])
        ->name('matched');

    // Matching-Uebersicht
    Route::get('/matching', [BankBuchungController::class, 'matchingOverview'])
        ->name('matching');

    // Auto-Matching mit Progress
    Route::get('/auto-match', [BankBuchungController::class, 'autoMatchProgress'])
        ->name('autoMatchProgress');
    Route::post('/auto-match/batch', [BankBuchungController::class, 'autoMatchBatch'])
        ->name('autoMatchBatch');

    // Konfiguration
    Route::get('/config', [BankBuchungController::class, 'config'])
        ->name('config');
    Route::put('/config', [BankBuchungController::class, 'updateConfig'])
        ->name('config.update');
    Route::post('/config/reset', [BankBuchungController::class, 'resetConfig'])
        ->name('config.reset');


    // Einzelne Buchung
    Route::get('/{buchung}', [BankBuchungController::class, 'show'])
        ->name('show');

    // Manuell zuordnen
    Route::post('/{buchung}/match', [BankBuchungController::class, 'match'])
        ->name('match');

    // Zuordnung aufheben
    Route::delete('/{buchung}/unmatch', [BankBuchungController::class, 'unmatch'])
        ->name('unmatch');

    // Ignorieren
    Route::post('/{buchung}/ignore', [BankBuchungController::class, 'ignore'])
        ->name('ignore');
});


Route::prefix('mahnungen')->name('mahnungen.')->middleware('auth')->group(function () {

    // Dashboard
    Route::get('/', [MahnungController::class, 'index'])->name('index');

    // Historie
    Route::get('/historie', [MahnungController::class, 'historie'])->name('historie');

    // Mahnlauf
    Route::get('/mahnlauf', [MahnungController::class, 'mahnlaufVorbereiten'])->name('mahnlauf');
    Route::post('/mahnlauf/erstellen', [MahnungController::class, 'mahnungenErstellen'])->name('erstellen');

    // Versand
    Route::get('/versand', [MahnungController::class, 'versand'])->name('versand');
    Route::post('/versand', [MahnungController::class, 'versenden'])->name('versenden');

    // Mahnstufen-Konfiguration
    Route::get('/stufen', [MahnungController::class, 'stufen'])->name('stufen');
    Route::get('/stufen/{stufe}/bearbeiten', [MahnungController::class, 'stufeBearbeiten'])->name('stufe.bearbeiten');
    Route::put('/stufen/{stufe}', [MahnungController::class, 'stufeSpeichern'])->name('stufe.speichern');

    // ⭐ NEU: Einstellungen
    Route::get('/einstellungen', [MahnungController::class, 'einstellungen'])->name('einstellungen');
    Route::post('/einstellungen', [MahnungController::class, 'einstellungenSpeichern'])->name('einstellungen.speichern');

    // Ausschlüsse
    Route::get('/ausschluesse', [MahnungController::class, 'ausschluesse'])->name('ausschluesse');
    Route::post('/ausschluesse/kunde', [MahnungController::class, 'kundeAusschliessen'])->name('kunde.ausschliessen');
    Route::delete('/ausschluesse/kunde/{adresse}', [MahnungController::class, 'kundeAusschlussEntfernen'])->name('kunde.ausschluss.entfernen');
    Route::post('/ausschluesse/rechnung', [MahnungController::class, 'rechnungAusschliessen'])->name('rechnung.ausschliessen');
    Route::delete('/ausschluesse/rechnung/{rechnung}', [MahnungController::class, 'rechnungAusschlussEntfernen'])->name('rechnung.ausschluss.entfernen');

    // Einzelne Mahnung
    Route::get('/{mahnung}', [MahnungController::class, 'show'])->name('show');
    Route::post('/{mahnung}/stornieren', [MahnungController::class, 'stornieren'])->name('stornieren');
    Route::post('/{mahnung}/als-post-versendet', [MahnungController::class, 'alsPostVersendet'])->name('als-post-versendet');
    Route::get('/{mahnung}/pdf', [MahnungController::class, 'downloadPdf'])->name('pdf');

    // API
    Route::get('/api/statistiken', [MahnungController::class, 'apiStatistiken'])->name('api.statistiken');
    Route::post('/{mahnung}/versende-einzeln', [MahnungController::class, 'versendeEinzeln'])->name('versende-einzeln');
});


Route::prefix('angebote')->name('angebote.')->middleware(['auth'])->group(function () {

    // Übersicht
    Route::get('/', [AngebotController::class, 'index'])->name('index');

    // Neu erstellen (nur Gebäude-Auswahl)
    Route::get('/create', [AngebotController::class, 'create'])->name('create');

    // Aus Gebäude erstellen
    Route::post('/from-gebaeude/{gebaeude}', [AngebotController::class, 'createFromGebaeude'])
        ->name('from-gebaeude');

    // Einzelnes Angebot
    Route::get('/{angebot}', [AngebotController::class, 'edit'])->name('edit');
    Route::put('/{angebot}', [AngebotController::class, 'update'])->name('update');
    Route::delete('/{angebot}', [AngebotController::class, 'destroy'])->name('destroy');

    // PDF
    Route::get('/{angebot}/pdf', [AngebotController::class, 'pdf'])->name('pdf');

    // E-Mail Versand
    Route::get('/{angebot}/versand', [AngebotController::class, 'showVersand'])->name('versand');
    Route::post('/{angebot}/versenden', [AngebotController::class, 'versenden'])->name('versenden');

    // Status ändern
    Route::post('/{angebot}/status', [AngebotController::class, 'setStatus'])->name('status');

    // Zu Rechnung umwandeln
    Route::post('/{angebot}/zu-rechnung', [AngebotController::class, 'zuRechnung'])->name('zu-rechnung');

    // Kopieren
    Route::post('/{angebot}/kopieren', [AngebotController::class, 'kopieren'])->name('kopieren');

    // Positionen
    Route::post('/{angebot}/position', [AngebotController::class, 'addPosition'])->name('position.add');
    Route::put('/position/{position}', [AngebotController::class, 'updatePosition'])->name('position.update');
    Route::delete('/position/{position}', [AngebotController::class, 'deletePosition'])->name('position.delete');
    Route::post('/{angebot}/positionen/reorder', [AngebotController::class, 'reorderPositions'])->name('position.reorder');
});

Route::get('/test-pdf', function () {
    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><h1>Test PDF</h1><p>Umlaute: ä ö ü ß</p></body></html>';
    
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
        ->setPaper('a4', 'portrait')
        ->setOption('defaultFont', 'DejaVu Sans');
    
    return $pdf->stream('test.pdf');
});





// ==================== Auth Routes (Breeze) ====================
require __DIR__ . '/auth.php';
