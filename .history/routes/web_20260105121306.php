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
use App\Http\Controllers\GebaeudeLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GebaeudeDocumentController;
use App\Http\Controllers\FaelligkeitsSimulatorController;
use App\Http\Controllers\ErinnerungenController;
use App\Http\Controllers\RechnungLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\TextvorschlagController;
use App\Http\Controllers\ArbeitsberichtController;


// ═══════════════════════════════════════════════════════════════════════════════
// 🏠 HOME - Redirect basierend auf Rolle
// ═══════════════════════════════════════════════════════════════════════════════

Route::get('/', function () {
    if (auth()->check()) {
        return auth()->user()->isAdmin() 
            ? redirect()->route('dashboard')
            : redirect()->route('mitarbeiter.dashboard');
    }
    return redirect()->route('login');
})->middleware('auth')->name('home');


// ═══════════════════════════════════════════════════════════════════════════════
// 👷 MITARBEITER BEREICH (Livewire)
// ═══════════════════════════════════════════════════════════════════════════════

Route::middleware(['auth', 'mitarbeiter'])->prefix('mitarbeiter')->name('mitarbeiter.')->group(function () {
    
    // Dashboard
    Route::get('/', \App\Livewire\Mitarbeiter\Dashboard::class)->name('dashboard');
    
    // Lohnstundenerfassung
    Route::get('/lohnstunden', \App\Livewire\Mitarbeiter\Lohnstundenerfassung::class)->name('lohnstunden');
    
    // TODO: Später hinzufügen
    // Route::get('/reinigung', \App\Livewire\Mitarbeiter\Reinigungsplanung::class)->name('reinigung');
    // Route::get('/gebaeude', \App\Livewire\Mitarbeiter\GebaeudeVerwaltung::class)->name('gebaeude');
});


// ═══════════════════════════════════════════════════════════════════════════════
// 🔐 ADMIN BEREICH - Alle bestehenden Routen
// ═══════════════════════════════════════════════════════════════════════════════

Route::middleware(['auth', 'admin'])->group(function () {

    // ==================== Admin Livewire Components ====================
    Route::get('/lohnstunden', \App\Livewire\Admin\LohnstundenUebersicht::class)->name('admin.lohnstunden');
    

    // ==================== Dashboard ====================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/erledigt', [DashboardController::class, 'erledigtAjax'])->name('dashboard.erledigt');
    Route::post('/dashboard/faelligkeit-update', [DashboardController::class, 'aktualisiereFaelligkeiten'])->name('dashboard.faelligkeit-update');


    // ==================== Profile Routes ====================
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    // ==================== Tools (z. B. VIES) ====================
    Route::post('/tools/vies-lookup', [ToolsController::class, 'viesLookup'])->name('tools.viesLookup');


    // ==================== Adressen ====================
    Route::prefix('adresse')->name('adresse.')->group(function () {
        Route::get('/',        [AdresseController::class, 'index'])->name('index');
        Route::get('/create',  [AdresseController::class, 'create'])->name('create');
        Route::post('/',       [AdresseController::class, 'store'])->name('store');
        Route::post('/bulk-destroy', [AdresseController::class, 'bulkDestroy'])->name('bulkDestroy');
        Route::get('/{id}/json', [AdresseController::class, 'showJson'])->whereNumber('id')->name('json');
        Route::get('/{id}',      [AdresseController::class, 'show'])->whereNumber('id')->name('show');
        Route::get('/{id}/edit', [AdresseController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}',      [AdresseController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}',   [AdresseController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });


    // ==================== Gebäude ====================
    Route::prefix('gebaeude')->name('gebaeude.')->group(function () {
        Route::get('/',        [GebaeudeController::class, 'index'])->name('index');
        Route::get('/create',  [GebaeudeController::class, 'create'])->name('create');
        Route::post('/',       [GebaeudeController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [GebaeudeController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{id}',      [GebaeudeController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{id}',   [GebaeudeController::class, 'destroy'])->whereNumber('id')->name('destroy');

        // Touren: Bulk-Attach
        Route::post('/touren/bulk-attach', [GebaeudeController::class, 'bulkAttachTour'])->name('touren.bulkAttach');

        // Artikel-Positionen
        Route::post('/{id}/artikel',         [ArtikelGebaeudeController::class, 'store'])->whereNumber('id')->name('artikel.store');
        Route::post('/{id}/artikel/reorder', [ArtikelGebaeudeController::class, 'reorder'])->whereNumber('id')->name('artikel.reorder');

        // Reinigungen zurücksetzen
        Route::post('/reset-gemachte-reinigungen', [GebaeudeController::class, 'resetGemachteReinigungen'])->name('resetGemachteReinigungen');

        // Fälligkeit berechnen
        Route::post('/{id}/faellig/recalc', [GebaeudeController::class, 'recalcFaelligkeit'])->whereNumber('id')->name('faellig.recalc');
        Route::post('/faellig/recalc-all', [GebaeudeController::class, 'recalcFaelligAll'])->name('faellig.recalcAll');

        // Rechnung aus Gebäude erstellen
        Route::post('/{id}/rechnung', [GebaeudeController::class, 'createRechnung'])->whereNumber('id')->name('rechnung.create');

        // Gebäude-Log Routes
        Route::get('/{gebaeude}/logs', [GebaeudeLogController::class, 'index'])->whereNumber('gebaeude')->name('logs.index');
        Route::post('/{gebaeude}/logs', [GebaeudeLogController::class, 'store'])->whereNumber('gebaeude')->name('logs.store');
        Route::post('/{gebaeude}/logs/notiz', [GebaeudeLogController::class, 'notiz'])->whereNumber('gebaeude')->name('logs.notiz');
        Route::post('/{gebaeude}/logs/telefonat', [GebaeudeLogController::class, 'telefonat'])->whereNumber('gebaeude')->name('logs.telefonat');
        Route::post('/{gebaeude}/logs/problem', [GebaeudeLogController::class, 'problem'])->whereNumber('gebaeude')->name('logs.problem');
        Route::post('/{gebaeude}/logs/erinnerung', [GebaeudeLogController::class, 'erinnerung'])->whereNumber('gebaeude')->name('logs.erinnerung');
        Route::post('/{id}/erstelle-adresse', [GebaeudeController::class, 'erstelleAdresse'])->whereNumber('id')->name('erstelleAdresse');
    });

    // Gebäude-Log Einzelaktionen
    Route::delete('/gebaeude/logs/{log}', [GebaeudeLogController::class, 'destroy'])->whereNumber('log')->name('gebaeude.logs.destroy');
    Route::post('/gebaeude/logs/{log}/erledigt', [GebaeudeLogController::class, 'erledigt'])->whereNumber('log')->name('gebaeude.logs.erledigt');

    // Erinnerungs-Dashboard
    Route::get('/gebaeude-erinnerungen', [GebaeudeLogController::class, 'erinnerungen'])->name('gebaeude.erinnerungen');

    // Gebäude Aufschlag-Routes
    Route::post('gebaeude/{gebaeude}/aufschlag', [GebaeudeController::class, 'setAufschlag'])->name('gebaeude.aufschlag.set');
    Route::delete('gebaeude/{gebaeude}/aufschlag', [GebaeudeController::class, 'removeAufschlag'])->name('gebaeude.aufschlag.remove');
    Route::get('gebaeude/{gebaeude}/aufschlag', [GebaeudeController::class, 'getAufschlag'])->name('gebaeude.aufschlag.get');
    Route::delete('/gebaeude/bulk-destroy', [GebaeudeController::class, 'bulkDestroy'])->name('gebaeude.bulkDestroy');

    // Artikel-Positionen (Einzel-ID)
    Route::put('/artikel-gebaeude/{id}',    [ArtikelGebaeudeController::class, 'update'])->whereNumber('id')->name('artikel.gebaeude.update');
    Route::delete('/artikel-gebaeude/{id}', [ArtikelGebaeudeController::class, 'destroy'])->whereNumber('id')->name('artikel.gebaeude.destroy');


    // ==================== Timeline ====================
    Route::post('/gebaeude/{id}/timeline', [TimelineController::class, 'timelineStore'])->whereNumber('id')->name('gebaeude.timeline.store');
    Route::delete('/timeline/{id}', [TimelineController::class, 'destroy'])->whereNumber('id')->name('timeline.destroy');
    Route::patch('/timeline/{id}/verrechnen', [TimelineController::class, 'toggleVerrechnen'])->whereNumber('id')->name('timeline.toggleVerrechnen');


    // ==================== Touren ====================
    Route::patch('/tour/reorder', [TourController::class, 'reorder'])->name('tour.reorder');
    Route::patch('/tour/{tour}/toggle', [TourController::class, 'toggleActive'])->whereNumber('tour')->name('tour.toggle');
    Route::get('/tour/{id}', [TourController::class, 'show'])->whereNumber('id')->name('tour.show');
    Route::delete('/tour/{tour}/gebaeude/{gebaeude}', [TourController::class, 'detach'])->whereNumber('tour')->whereNumber('gebaeude')->name('tour.gebaeude.detach');
    Route::delete('/tour/{tour}/gebaeude', [TourController::class, 'bulkDetach'])->whereNumber('tour')->name('tour.gebaeude.bulkDetach');
    Route::resource('tour', TourController::class)->except(['show']);


    // ==================== Rechnungen ====================
    Route::prefix('rechnung')->name('rechnung.')->group(function () {
        Route::get('/', [RechnungController::class, 'index'])->name('index');
        Route::get('/create', [RechnungController::class, 'create'])->name('create');
        Route::post('/store', [RechnungController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [RechnungController::class, 'edit'])->name('edit');
        Route::put('/{id}', [RechnungController::class, 'update'])->name('update');
        Route::delete('/{id}', [RechnungController::class, 'destroy'])->name('destroy');

        // PDF Routes
        Route::get('/{id}/pdf', [RechnungController::class, 'generatePdf'])->name('pdf');
        Route::get('/{id}/pdf/preview', [RechnungController::class, 'previewPdf'])->name('pdf.preview');
        Route::get('/{id}/pdf/download', [RechnungController::class, 'downloadPdf'])->name('pdf.download');

        // Status ändern
        Route::post('/{id}/status', [RechnungController::class, 'updateStatus'])->name('status.update');

        // FatturaPA XML
        Route::get('/{id}/xml', [RechnungController::class, 'generateXml'])->name('xml');
        Route::get('/{id}/xml/download', [RechnungController::class, 'downloadXml'])->name('xml.download');
        Route::post('/{id}/xml/send', [RechnungController::class, 'sendXml'])->name('xml.send');
        Route::post('/{id}/mark-sent', [RechnungController::class, 'markAsSent'])->name('mark-sent');
    });

    // FatturaPA XML Log Management
    Route::prefix('fattura-xml')->name('fattura.xml.')->group(function () {
        Route::get('{logId}/download', [RechnungController::class, 'downloadXmlByLog'])->name('download');
        Route::delete('{logId}', [RechnungController::class, 'deleteXmlLog'])->name('delete');
    });

    // FatturaPA Routes
    Route::prefix('rechnung/{id}')->name('rechnung.')->group(function () {
        Route::post('xml/generate', [RechnungController::class, 'generateXml'])->name('xml.generate');
        Route::post('xml/regenerate', [RechnungController::class, 'regenerateXml'])->name('xml.regenerate');
        Route::get('xml/preview', [RechnungController::class, 'previewXml'])->name('xml.preview');
        Route::get('xml/download', [RechnungController::class, 'downloadXml'])->name('xml.download');
        Route::get('xml/logs', [RechnungController::class, 'xmlLogs'])->name('xml.logs');
        Route::get('xml/debug', [RechnungController::class, 'debugXml'])->name('xml.debug');
    });

    // Rechnung E-Mail Versand
    Route::post('/rechnung/{id}/email/send', [RechnungController::class, 'sendEmail'])->name('rechnung.email.send');

    // Rechnung Log Routes
    Route::get('/rechnung/{rechnung}/logs', [RechnungLogController::class, 'index'])->name('rechnung.logs.index');
    Route::post('/rechnung/{rechnung}/logs', [RechnungLogController::class, 'store'])->name('rechnung.logs.store');
    Route::put('/rechnung/logs/{log}', [RechnungLogController::class, 'update'])->name('rechnung.logs.update');
    Route::delete('/rechnung/logs/{log}', [RechnungLogController::class, 'destroy'])->name('rechnung.logs.destroy');
    Route::post('/rechnung/logs/{log}/erledigt', [RechnungLogController::class, 'erinnerungErledigt'])->name('rechnung.logs.erledigt');
    Route::post('/rechnung/{rechnung}/logs/telefonat', [RechnungLogController::class, 'quickTelefonat'])->name('rechnung.logs.telefonat');
    Route::post('/rechnung/{rechnung}/logs/notiz', [RechnungLogController::class, 'quickNotiz'])->name('rechnung.logs.notiz');
    Route::post('/rechnung/{rechnung}/logs/mitteilung', [RechnungLogController::class, 'quickMitteilung'])->name('rechnung.logs.mitteilung');
    Route::get('/rechnung-logs/dashboard', [RechnungLogController::class, 'dashboard'])->name('rechnung.logs.dashboard');


    // ==================== Preis-Aufschläge ====================
    Route::prefix('preis-aufschlaege')->name('preis-aufschlaege.')->group(function () {
        Route::get('/', [PreisAufschlagController::class, 'index'])->name('index');
        Route::post('/global', [PreisAufschlagController::class, 'storeGlobal'])->name('store-global');
        Route::delete('/global/{id}', [PreisAufschlagController::class, 'destroyGlobal'])->name('destroy-global');
        Route::post('/preview', [PreisAufschlagController::class, 'preview'])->name('preview');
    });


    // ==================== Unternehmensprofil ====================
    Route::prefix('einstellungen/profil')->name('unternehmensprofil.')->group(function () {
        Route::get('/', [UnternehmensprofilController::class, 'index'])->name('index');
        Route::get('/bearbeiten', [UnternehmensprofilController::class, 'bearbeiten'])->name('bearbeiten');
        Route::post('/speichern', [UnternehmensprofilController::class, 'speichern'])->name('speichern');
        Route::post('/logo/hochladen', [UnternehmensprofilController::class, 'logoHochladen'])->name('logo.hochladen');
        Route::delete('/logo/loeschen', [UnternehmensprofilController::class, 'logoLoeschen'])->name('logo.loeschen');
        Route::get('/smtp/testen', [UnternehmensprofilController::class, 'smtpTesten'])->name('smtp.testen');
        Route::get('/pec-smtp/testen', [UnternehmensprofilController::class, 'pecSmtpTesten'])->name('pec-smtp.testen');
        Route::post('/duplizieren', [UnternehmensprofilController::class, 'duplizieren'])->name('duplizieren');
        Route::get('/exportieren', [UnternehmensprofilController::class, 'exportieren'])->name('exportieren');
        Route::post('/importieren', [UnternehmensprofilController::class, 'importieren'])->name('importieren');
    });


    // ==================== Reinigungsplanung ====================
    Route::prefix('reinigungsplanung')->name('reinigungsplanung.')->group(function () {
        Route::get('/', [ReinigungsplanungController::class, 'index'])->name('index');
        Route::post('/{gebaeude}/erledigt', [ReinigungsplanungController::class, 'markErledigt'])->name('erledigt');
        Route::get('/export', [ReinigungsplanungController::class, 'export'])->name('export');
    });


    // ==================== Bank-Buchungen ====================
    Route::prefix('bank')->name('bank.')->group(function () {
        Route::get('/', [BankBuchungController::class, 'index'])->name('index');
        Route::get('/import', [BankBuchungController::class, 'importForm'])->name('import');
        Route::post('/import', [BankBuchungController::class, 'import'])->name('import.store');
        Route::get('/unmatched', [BankBuchungController::class, 'unmatched'])->name('unmatched');
        Route::get('/matched', [BankBuchungController::class, 'matched'])->name('matched');
        Route::get('/matching', [BankBuchungController::class, 'matchingOverview'])->name('matching');
        Route::get('/auto-match', [BankBuchungController::class, 'autoMatchProgress'])->name('autoMatchProgress');
        Route::post('/auto-match/batch', [BankBuchungController::class, 'autoMatchBatch'])->name('autoMatchBatch');
        Route::get('/config', [BankBuchungController::class, 'config'])->name('config');
        Route::put('/config', [BankBuchungController::class, 'updateConfig'])->name('config.update');
        Route::post('/config/reset', [BankBuchungController::class, 'resetConfig'])->name('config.reset');
        Route::get('/{buchung}', [BankBuchungController::class, 'show'])->name('show');
        Route::post('/{buchung}/match', [BankBuchungController::class, 'match'])->name('match');
        Route::delete('/{buchung}/unmatch', [BankBuchungController::class, 'unmatch'])->name('unmatch');
        Route::post('/{buchung}/ignore', [BankBuchungController::class, 'ignore'])->name('ignore');
    });


    // ==================== Mahnungen ====================
    Route::prefix('mahnungen')->name('mahnungen.')->group(function () {
        Route::get('/', [MahnungController::class, 'index'])->name('index');
        Route::get('/historie', [MahnungController::class, 'historie'])->name('historie');
        Route::get('/mahnlauf', [MahnungController::class, 'mahnlaufVorbereiten'])->name('mahnlauf');
        Route::post('/mahnlauf/erstellen', [MahnungController::class, 'mahnungenErstellen'])->name('erstellen');
        Route::get('/versand', [MahnungController::class, 'versand'])->name('versand');
        Route::post('/versand', [MahnungController::class, 'versenden'])->name('versenden');
        Route::get('/stufen', [MahnungController::class, 'stufen'])->name('stufen');
        Route::get('/stufen/{stufe}/bearbeiten', [MahnungController::class, 'stufeBearbeiten'])->name('stufe.bearbeiten');
        Route::put('/stufen/{stufe}', [MahnungController::class, 'stufeSpeichern'])->name('stufe.speichern');
        Route::get('/einstellungen', [MahnungController::class, 'einstellungen'])->name('einstellungen');
        Route::post('/einstellungen', [MahnungController::class, 'einstellungenSpeichern'])->name('einstellungen.speichern');
        Route::get('/ausschluesse', [MahnungController::class, 'ausschluesse'])->name('ausschluesse');
        Route::post('/ausschluesse/kunde', [MahnungController::class, 'kundeAusschliessen'])->name('kunde.ausschliessen');
        Route::delete('/ausschluesse/kunde/{adresse}', [MahnungController::class, 'kundeAusschlussEntfernen'])->name('kunde.ausschluss.entfernen');
        Route::post('/ausschluesse/rechnung', [MahnungController::class, 'rechnungAusschliessen'])->name('rechnung.ausschliessen');
        Route::delete('/ausschluesse/rechnung/{rechnung}', [MahnungController::class, 'rechnungAusschlussEntfernen'])->name('rechnung.ausschluss.entfernen');
        Route::get('/{mahnung}', [MahnungController::class, 'show'])->name('show');
        Route::post('/{mahnung}/stornieren', [MahnungController::class, 'stornieren'])->name('stornieren');
        Route::post('/{mahnung}/als-post-versendet', [MahnungController::class, 'alsPostVersendet'])->name('als-post-versendet');
        Route::get('/{mahnung}/pdf', [MahnungController::class, 'downloadPdf'])->name('pdf');
        Route::get('/api/statistiken', [MahnungController::class, 'apiStatistiken'])->name('api.statistiken');
        Route::post('/{mahnung}/versende-einzeln', [MahnungController::class, 'versendeEinzeln'])->name('versende-einzeln');
    });


    // ==================== Angebote ====================
    Route::prefix('angebote')->name('angebote.')->group(function () {
        Route::get('/', [AngebotController::class, 'index'])->name('index');
        Route::get('/create', [AngebotController::class, 'create'])->name('create');
        Route::get('/textvorschlaege', [AngebotController::class, 'textvorschlaege'])->name('textvorschlaege');
        Route::post('/from-gebaeude/{gebaeude}', [AngebotController::class, 'createFromGebaeude'])->name('from-gebaeude');
        Route::get('/{angebot}', [AngebotController::class, 'edit'])->name('edit');
        Route::put('/{angebot}', [AngebotController::class, 'update'])->name('update');
        Route::delete('/{angebot}', [AngebotController::class, 'destroy'])->name('destroy');
        Route::get('/{angebot}/pdf', [AngebotController::class, 'pdf'])->name('pdf');
        Route::get('/{angebot}/versand', [AngebotController::class, 'showVersand'])->name('versand');
        Route::post('/{angebot}/versenden', [AngebotController::class, 'versenden'])->name('versenden');
        Route::post('/{angebot}/status', [AngebotController::class, 'setStatus'])->name('status');
        Route::post('/{angebot}/zu-rechnung', [AngebotController::class, 'zuRechnung'])->name('zu-rechnung');
        Route::post('/{angebot}/kopieren', [AngebotController::class, 'kopieren'])->name('kopieren');
        Route::post('/{angebot}/position', [AngebotController::class, 'addPosition'])->name('position.add');
        Route::put('/position/{position}', [AngebotController::class, 'updatePosition'])->name('position.update');
        Route::delete('/position/{position}', [AngebotController::class, 'deletePosition'])->name('position.delete');
        Route::post('/{angebot}/positionen/reorder', [AngebotController::class, 'reorderPositions'])->name('position.reorder');
    });


    // ==================== Gebäude-Dokumente ====================
    Route::get('/gebaeude/dokumente', [GebaeudeDocumentController::class, 'index'])->name('gebaeude.dokumente.index');
    Route::post('/gebaeude/{gebaeude}/dokumente', [GebaeudeDocumentController::class, 'store'])->whereNumber('gebaeude')->name('gebaeude.dokumente.store');
    Route::post('/gebaeude/{gebaeude}/dokumente/multiple', [GebaeudeDocumentController::class, 'storeMultiple'])->whereNumber('gebaeude')->name('gebaeude.dokumente.storeMultiple');
    Route::put('/gebaeude/dokumente/{dokument}', [GebaeudeDocumentController::class, 'update'])->whereNumber('dokument')->name('gebaeude.dokumente.update');
    Route::delete('/gebaeude/dokumente/{dokument}', [GebaeudeDocumentController::class, 'destroy'])->whereNumber('dokument')->name('gebaeude.dokumente.destroy');
    Route::get('/gebaeude/dokumente/{dokument}/download', [GebaeudeDocumentController::class, 'download'])->whereNumber('dokument')->name('gebaeude.dokumente.download');
    Route::get('/gebaeude/dokumente/{dokument}/preview', [GebaeudeDocumentController::class, 'preview'])->whereNumber('dokument')->name('gebaeude.dokumente.preview');
    Route::get('/gebaeude/dokumente/{dokument}/thumbnail', [GebaeudeDocumentController::class, 'thumbnail'])->whereNumber('dokument')->name('gebaeude.dokumente.thumbnail');
    Route::post('/gebaeude/dokumente/{dokument}/wichtig', [GebaeudeDocumentController::class, 'toggleWichtig'])->whereNumber('dokument')->name('gebaeude.dokumente.toggleWichtig');
    Route::post('/gebaeude/dokumente/{dokument}/archiv', [GebaeudeDocumentController::class, 'toggleArchiv'])->whereNumber('dokument')->name('gebaeude.dokumente.toggleArchiv');


    // ==================== Fälligkeits-Simulator ====================
    Route::prefix('faelligkeit')->name('faelligkeit.')->group(function () {
        Route::get('/', [FaelligkeitsSimulatorController::class, 'index'])->name('index');
        Route::post('/simuliere', [FaelligkeitsSimulatorController::class, 'simuliere'])->name('simuliere');
        Route::post('/pruefe-gebaeude', [FaelligkeitsSimulatorController::class, 'pruefeGebaeude'])->name('pruefeGebaeude');
        Route::post('/batch-update', [FaelligkeitsSimulatorController::class, 'batchUpdate'])->name('batchUpdate');
        Route::post('/gebaeude/{id}/update', [FaelligkeitsSimulatorController::class, 'updateGebaeude'])->name('updateGebaeude');
    });


    // ==================== Erinnerungen ====================
    Route::get('/erinnerungen', [ErinnerungenController::class, 'index'])->name('erinnerungen.index');
    Route::post('/erinnerungen/toggle', [ErinnerungenController::class, 'toggle'])->name('erinnerungen.toggle');


    // ==================== Backup ====================
    Route::prefix('backup')->name('backup.')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('index');
        Route::post('/create', [BackupController::class, 'create'])->name('create');
        Route::get('/{backup}/download', [BackupController::class, 'download'])->name('download');
        Route::get('/{backup}/log', [BackupController::class, 'log'])->name('log');
        Route::delete('/{backup}', [BackupController::class, 'destroy'])->name('destroy');
        Route::post('/cleanup', [BackupController::class, 'cleanup'])->name('cleanup');
    });


    // ==================== Textvorschläge ====================
    Route::prefix('textvorschlaege')->name('textvorschlaege.')->group(function () {
        Route::get('/', [TextvorschlagController::class, 'index'])->name('index');
        Route::get('/create', [TextvorschlagController::class, 'create'])->name('create');
        Route::post('/', [TextvorschlagController::class, 'store'])->name('store');
        Route::get('/{textvorschlag}/edit', [TextvorschlagController::class, 'edit'])->name('edit');
        Route::put('/{textvorschlag}', [TextvorschlagController::class, 'update'])->name('update');
        Route::delete('/{textvorschlag}', [TextvorschlagController::class, 'destroy'])->name('destroy');
        Route::patch('/{textvorschlag}/toggle', [TextvorschlagController::class, 'toggleAktiv'])->name('toggle');
        Route::get('/api', [TextvorschlagController::class, 'api'])->name('api');
        Route::post('/api/store', [TextvorschlagController::class, 'apiStore'])->name('api.store');
    });


    // ==================== Arbeitsberichte (Admin) ====================
    Route::prefix('arbeitsberichte')->name('arbeitsbericht.')->group(function () {
        Route::get('/', [ArbeitsberichtController::class, 'index'])->name('index');
        Route::get('/gebaeude-suche', [ArbeitsberichtController::class, 'gebaeudeSearch'])->name('gebaeude.search');
        Route::get('/erstellen', [ArbeitsberichtController::class, 'create'])->name('create');
        Route::post('/', [ArbeitsberichtController::class, 'store'])->name('store');
        Route::get('/{arbeitsbericht}', [ArbeitsberichtController::class, 'show'])->name('show');
        Route::get('/{arbeitsbericht}/bearbeiten', [ArbeitsberichtController::class, 'edit'])->name('edit');
        Route::put('/{arbeitsbericht}', [ArbeitsberichtController::class, 'update'])->name('update');
        Route::delete('/{arbeitsbericht}', [ArbeitsberichtController::class, 'destroy'])->name('destroy');
        Route::get('/{arbeitsbericht}/pdf', [ArbeitsberichtController::class, 'pdf'])->name('pdf');
        Route::post('/{arbeitsbericht}/senden', [ArbeitsberichtController::class, 'senden'])->name('senden');
    });


    // ==================== Test PDF ====================
    Route::get('/test-pdf', function () {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><h1>Test PDF</h1><p>Umlaute: ä ö ü ß</p></body></html>';
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait')->setOption('defaultFont', 'DejaVu Sans');
        return $pdf->stream('test.pdf');
    });

}); // Ende Admin-Gruppe


// ═══════════════════════════════════════════════════════════════════════════════
// 🌐 ÖFFENTLICHE ROUTEN (ohne Login)
// ═══════════════════════════════════════════════════════════════════════════════

Route::prefix('bericht')->name('arbeitsbericht.public')->group(function () {
    Route::get('/{token}', [ArbeitsberichtController::class, 'publicView'])->name('');
    Route::get('/{token}/pdf', [ArbeitsberichtController::class, 'publicPdf'])->name('.pdf');
});


// ==================== Auth Routes (Breeze) ====================
require __DIR__ . '/auth.php';
