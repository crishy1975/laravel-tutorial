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
Route::middleware(['auth'])->prefix('rechnung')->name('rechnung.')->group(function () {

    // ═══════════════════════════════════════════════════════════
    // RECHNUNGEN - Hauptrouten
    // ═══════════════════════════════════════════════════════════

    // Liste aller Rechnungen
    Route::get('/', [RechnungController::class, 'index'])->name('index');

    // Neue Rechnung aus Gebäude erstellen
    Route::get('/create', [RechnungController::class, 'create'])->name('create');

    // Rechnung speichern (POST nach create)
    Route::post('/', [RechnungController::class, 'store'])->name('store');

    // Einzelne Rechnung anzeigen
    Route::get('/{id}', [RechnungController::class, 'show'])->name('show');

    // Rechnung bearbeiten
    Route::get('/{id}/edit', [RechnungController::class, 'edit'])->name('edit');

    // Rechnung aktualisieren
    Route::put('/{id}', [RechnungController::class, 'update'])->name('update');

    // Rechnung löschen (nur Entwürfe)
    Route::delete('/{id}', [RechnungController::class, 'destroy'])->name('destroy');

    // ═══════════════════════════════════════════════════════════
    // ⭐ ZAHLUNGS-AKTIONEN
    // ═══════════════════════════════════════════════════════════

    // Rechnung als bezahlt markieren
    Route::post('/{id}/mark-bezahlt', [RechnungController::class, 'markAsBezahlt'])->name('mark-bezahlt');

    // Rechnung senden (E-Mail)
    Route::post('/{id}/send', [RechnungController::class, 'send'])->name('send');

    // Rechnung stornieren
    Route::post('/{id}/cancel', [RechnungController::class, 'cancel'])->name('cancel');

    // Zahlungsziel berechnen (AJAX)
    Route::post('/calculate-zahlungsziel', [RechnungController::class, 'calculateZahlungsziel'])->name('calculate-zahlungsziel');

    // ═══════════════════════════════════════════════════════════
    // RECHNUNGSPOSITIONEN
    // ═══════════════════════════════════════════════════════════

    // Neue Position hinzufügen
    Route::post('/{rechnungId}/position', [RechnungController::class, 'storePosition'])->name('position.store');

    // Position aktualisieren
    Route::put('/position/{positionId}', [RechnungController::class, 'updatePosition'])->name('position.update');

    // Position löschen
    Route::delete('/position/{positionId}', [RechnungController::class, 'destroyPosition'])->name('position.destroy');

    // ═══════════════════════════════════════════════════════════
    // EXPORT (optional, wenn später implementiert)
    // ═══════════════════════════════════════════════════════════

    // PDF generieren
    Route::get('/{id}/pdf', [RechnungController::class, 'generatePdf'])->name('pdf');

    // FatturaPA XML generieren
    Route::get('/{id}/xml', [RechnungController::class, 'generateXml'])->name('xml');
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

Route::prefix('einstellungen')->name('unternehmensprofil.')->middleware(['auth'])->group(function () {
    // Übersicht
    Route::get('/profil', [UnternehmensprofilController::class, 'index'])
        ->name('index');
    
    // Bearbeiten
    Route::get('/profil/bearbeiten', [UnternehmensprofilController::class, 'bearbeiten'])
        ->name('bearbeiten');
    
    // Speichern
    Route::put('/profil', [UnternehmensprofilController::class, 'speichern'])
        ->name('speichern');
    
    // SMTP Test
    Route::post('/profil/smtp-test', [UnternehmensprofilController::class, 'smtpTesten'])
        ->name('smtp.testen');
});

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








// ==================== Auth Routes (Breeze) ====================
require __DIR__ . '/auth.php';