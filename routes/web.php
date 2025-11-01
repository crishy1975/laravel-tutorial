<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GebaeudeController;
use App\Http\Controllers\AdresseController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\TimelineController;

/* -------------------- Adressen -------------------- */
Route::middleware(['auth', 'verified'])
    ->prefix('adresse')
    ->name('adresse.')
    ->group(function () {
        Route::get('/',              [AdresseController::class, 'index'])->name('index');
        Route::get('/create',        [AdresseController::class, 'create'])->name('create');
        Route::post('/',             [AdresseController::class, 'store'])->name('store');

        // optional/empfohlen: zuerst statisch
        Route::post('/bulk-destroy', [AdresseController::class, 'bulkDestroy'])->name('bulkDestroy');

        // optional: JSON-Endpoint
        Route::get('/{id}/json',     [AdresseController::class, 'showJson'])
            ->whereNumber('id')->name('json');

        Route::get('/{id}',          [AdresseController::class, 'show'])
            ->whereNumber('id')->name('show');

        Route::get('/{id}/edit',     [AdresseController::class, 'edit'])
            ->whereNumber('id')->name('edit');

        Route::put('/{id}',          [AdresseController::class, 'update'])
            ->whereNumber('id')->name('update');

        Route::delete('/{id}',       [AdresseController::class, 'destroy'])
            ->whereNumber('id')->name('destroy');
    });

/* -------------------- Tools -------------------- */
Route::post('/tools/vies-lookup', [\App\Http\Controllers\ToolsController::class, 'viesLookup'])
    ->name('tools.viesLookup');

/* -------------------- Home -------------------- */
Route::get('/', fn () => redirect()->route('gebaeude.index'))
    ->middleware(['auth','verified'])->name('home');

/* -------------------- Gebäude -------------------- */
Route::middleware(['auth', 'verified'])
    ->prefix('gebaeude')
    ->name('gebaeude.')
    ->group(function () {
        Route::get('/',        [GebaeudeController::class, 'index'])->name('index');
        Route::get('/create',  [GebaeudeController::class, 'create'])->name('create');
        Route::post('/',       [GebaeudeController::class, 'store'])->name('store');

        Route::post('/bulk-attach-tour', [GebaeudeController::class, 'bulkAttachTour'])
            ->name('touren.bulkAttach');

        Route::get('/{id}/edit', [GebaeudeController::class, 'edit'])
            ->whereNumber('id')->name('edit');

        Route::put('/{id}',      [GebaeudeController::class, 'update'])
            ->whereNumber('id')->name('update');

        Route::delete('/{id}',   [GebaeudeController::class, 'destroy'])
            ->whereNumber('id')->name('destroy');

        Route::post('/{id}/timeline', [GebaeudeController::class, 'timelineStore'])
            ->whereNumber('id')->name('timeline.store');

        Route::delete('/{id}/timeline/{timeline}', [GebaeudeController::class, 'timelineDestroy'])
            ->whereNumber('id')->whereNumber('timeline')->name('timeline.destroy');
    });

/* -------------------- Touren -------------------- */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::patch('tour/reorder', [TourController::class, 'reorder'])->name('tour.reorder');
    Route::patch('tour/{tour}/toggle', [TourController::class, 'toggleActive'])
        ->whereNumber('tour')->name('tour.toggle');

    Route::get('tour/{id}', [TourController::class, 'show'])
        ->whereNumber('id')->name('tour.show');

    Route::delete('tour/{tour}/gebaeude/{gebaeude}', [TourController::class, 'detachGebaeude'])
        ->whereNumber('tour')->whereNumber('gebaeude')->name('tour.gebaeude.detach');

    Route::post('tour/{tour}/gebaeude/detach-bulk', [TourController::class, 'detachGebaeudeBulk'])
        ->whereNumber('tour')->name('tour.gebaeude.detachBulk');

    Route::resource('tour', TourController::class)->except(['show']);
});
// POST: Timeline-Eintrag zu Gebäude speichern
Route::post('/gebaeude/{id}/timeline', [TimelineController::class, 'timelineStore'])
    ->name('gebaeude.timeline.store');

// DELETE: Timeline-Eintrag löschen
Route::delete('/timeline/{id}', [TimelineController::class, 'destroy'])
    ->name('timeline.destroy');

require __DIR__.'/auth.php';
