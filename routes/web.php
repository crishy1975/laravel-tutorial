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

/*
|--------------------------------------------------------------------------
| WEB ROUTES (vollständig & bereinigt)
|--------------------------------------------------------------------------
| - Einheitliche Namen & Parameter (id, tour, gebaeude, …)
| - whereNumber() für IDs
| - Auth/Verified dort, wo UI-geschützt ist
| - Keine Duplikate / vergessenen Routen
*/

/* ==================== Home ==================== */
// Startseite → direkt zu Gebäude-Index
Route::get('/', fn() => redirect()->route('gebaeude.index'))
    ->middleware(['auth', 'verified'])
    ->name('home');


/* ==================== Auth (Breeze / Fortify) ==================== */
// Profile (Standard aus Breeze)
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

        // 🔗 Touren: Bulk-Attach (für dein Modal in gebaeude.index)
        // (Route-Name exakt wie in deinen Views: ge baeude.touren.bulkAttach)
        Route::post('/touren/bulk-attach', [GebaeudeController::class, 'bulkAttachTour'])
            ->name('touren.bulkAttach');

        // 🧾 Artikel-Positionen (pro Gebäude)
        Route::post('/{id}/artikel',         [ArtikelGebaeudeController::class, 'store'])
            ->whereNumber('id')->name('artikel.store');

        Route::post('/{id}/artikel/reorder', [ArtikelGebaeudeController::class, 'reorder'])
            ->whereNumber('id')->name('artikel.reorder');
        Route::post('/reset-gemachte-reinigungen', [GebaeudeController::class, 'resetGemachteReinigungen'])
            ->name('resetGemachteReinigungen');
        Route::post('/{id}/faellig/recalc', [GebaeudeController::class, 'recalcFaelligkeit'])
            ->whereNumber('id')
            ->name('faellig.recalc');
        Route::post('/faellig/recalc-all', [GebaeudeController::class, 'recalcFaelligAll'])
            ->name('gebaeude.faellig.recalcAll');
    });

// 🧾 Artikel-Positionen (Einzel-ID: Update/Destroy)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::put('/artikel-gebaeude/{id}',    [ArtikelGebaeudeController::class, 'update'])
        ->whereNumber('id')->name('artikel.gebaeude.update');

    Route::delete('/artikel-gebaeude/{id}', [ArtikelGebaeudeController::class, 'destroy'])
        ->whereNumber('id')->name('artikel.gebaeude.destroy');
});


/* ==================== Timeline (pro Gebäude) ==================== */
// Speichern eines Timeline-Eintrags (View nutzt: ge baeude.timeline.store)
Route::post('/gebaeude/{id}/timeline', [TimelineController::class, 'timelineStore'])
    ->middleware(['auth', 'verified'])
    ->whereNumber('id')->name('gebaeude.timeline.store');

// Löschen eines Timeline-Eintrags (View nutzt: timeline.destroy)
Route::delete('/timeline/{id}', [TimelineController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->whereNumber('id')->name('timeline.destroy');

// NEU: Verrechnen-Status eines Timeline-Eintrags toggeln
Route::patch('/timeline/{id}/verrechnen', [TimelineController::class, 'toggleVerrechnen'])
    ->middleware(['auth', 'verified'])
    ->whereNumber('id')
    ->name('timeline.toggleVerrechnen');


/* ==================== Touren ==================== */
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
    // View: route('tour.gebaeude.detach', ['tour' => $tour->id, 'gebaeude' => $g->id])
    Route::delete('/tour/{tour}/gebaeude/{gebaeude}', [TourController::class, 'detach'])
        ->whereNumber('tour')->whereNumber('gebaeude')
        ->name('tour.gebaeude.detach');

    // Bulk-Detach (mehrere Verknüpfungen gleichzeitig)
    // View: route('tour.gebaeude.bulkDetach', $tour->id)
    Route::delete('/tour/{tour}/gebaeude', [TourController::class, 'bulkDetach'])
        ->whereNumber('tour')
        ->name('tour.gebaeude.bulkDetach');

    // Resource (ohne show) → index/create/store/edit/update/destroy
    Route::resource('tour', TourController::class)->except(['show']);
});


Route::middleware(['auth'])->group(function () {

    // ═══════════════════════════════════════════════════════════
    // RECHNUNGEN - Hauptrouten
    // ═══════════════════════════════════════════════════════════

    // Liste aller Rechnungen
    Route::get('/rechnung', [RechnungController::class, 'index'])
        ->name('rechnung.index');

    // Neue Rechnung aus Gebäude erstellen
    // Aufruf: /rechnung/create?gebaeude_id=123
    Route::get('/rechnung/create', [RechnungController::class, 'create'])
        ->name('rechnung.create');

    // Rechnung speichern (POST nach create)
    Route::post('/rechnung', [RechnungController::class, 'store'])
        ->name('rechnung.store');

    // Einzelne Rechnung anzeigen
    Route::get('/rechnung/{id}', [RechnungController::class, 'show'])
        ->name('rechnung.show');

    // Rechnung bearbeiten
    Route::get('/rechnung/{id}/edit', [RechnungController::class, 'edit'])
        ->name('rechnung.edit');

    // Rechnung aktualisieren
    Route::put('/rechnung/{id}', [RechnungController::class, 'update'])
        ->name('rechnung.update');

    // Rechnung löschen (nur Entwürfe)
    Route::delete('/rechnung/{id}', [RechnungController::class, 'destroy'])
        ->name('rechnung.destroy');

    // ═══════════════════════════════════════════════════════════
    // RECHNUNGSPOSITIONEN
    // ═══════════════════════════════════════════════════════════

    // Neue Position hinzufügen
    Route::post('/rechnung/{rechnungId}/position', [RechnungController::class, 'storePosition'])
        ->name('rechnung.position.store');

    // Position aktualisieren
    Route::put('/rechnung/position/{positionId}', [RechnungController::class, 'updatePosition'])
        ->name('rechnung.position.update');

    // Position löschen
    Route::delete('/rechnung/position/{positionId}', [RechnungController::class, 'destroyPosition'])
        ->name('rechnung.position.destroy');

    // ═══════════════════════════════════════════════════════════
    // GEBÄUDE - Rechnung erstellen
    // ═══════════════════════════════════════════════════════════

    // Rechnung direkt aus Gebäude erstellen
    // Aufruf: POST /gebaeude/123/rechnung
    Route::post('/gebaeude/{id}/rechnung', [GebaeudeController::class, 'createRechnung'])
        ->name('gebaeude.rechnung.create');

    // ═══════════════════════════════════════════════════════════
    // EXPORT (optional, wenn später implementiert)
    // ═══════════════════════════════════════════════════════════

    // PDF generieren
    Route::get('/rechnung/{id}/pdf', [RechnungController::class, 'generatePdf'])
        ->name('rechnung.pdf');

    // FatturaPA XML generieren
    Route::get('/rechnung/{id}/xml', [RechnungController::class, 'generateXml'])
        ->name('rechnung.xml');
});

/* ==================== Auth Scaffolding ==================== */
require __DIR__ . '/auth.php';
