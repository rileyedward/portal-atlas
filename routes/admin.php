<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DataExchangeController;
use App\Http\Controllers\Admin\GameVersionController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\MapController;
use App\Http\Controllers\Admin\MapEditorController;
use App\Http\Controllers\Admin\MarkerController;
use App\Http\Controllers\Admin\ObjectiveController;
use App\Http\Controllers\Admin\RecipeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SourceController;
use App\Http\Controllers\Admin\TaxonomyController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'can:manage-content'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('maps', MapController::class)->except('show');
    Route::get('maps/{map}/editor', MapEditorController::class)->name('maps.editor');
    Route::get('maps/{map}/markers/{marker}', [MarkerController::class, 'show'])->withTrashed()->name('maps.markers.show');
    Route::post('maps/{map}/markers', [MarkerController::class, 'store'])->name('maps.markers.store');
    Route::patch('maps/{map}/markers/{marker}', [MarkerController::class, 'update'])->name('maps.markers.update');
    Route::delete('maps/{map}/markers/{marker}', [MarkerController::class, 'destroy'])->name('maps.markers.destroy');
    Route::post('maps/{map}/markers/{marker}/duplicate', [MarkerController::class, 'duplicate'])->name('maps.markers.duplicate');
    Route::post('maps/{map}/markers/{marker}/verify', [MarkerController::class, 'verify'])->name('maps.markers.verify');

    Route::resource('items', ItemController::class)->except('show');
    Route::post('items/{item}/verify', [ItemController::class, 'verify'])->name('items.verify');
    Route::resource('objectives', ObjectiveController::class)->except('show');
    Route::resource('recipes', RecipeController::class)->except('show');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::patch('reports/{report}', [ReportController::class, 'update'])->name('reports.update');
    Route::post('reports/{report}/apply-suggestion', [ReportController::class, 'applySuggestion'])->name('reports.apply-suggestion');

    Route::get('versions', [GameVersionController::class, 'index'])->name('versions.index');
    Route::post('versions', [GameVersionController::class, 'store'])->name('versions.store');
    Route::patch('versions/{version}', [GameVersionController::class, 'update'])->name('versions.update');
    Route::delete('versions/{version}', [GameVersionController::class, 'destroy'])->name('versions.destroy');

    Route::get('sources', [SourceController::class, 'index'])->name('sources.index');
    Route::post('sources', [SourceController::class, 'store'])->name('sources.store');
    Route::patch('sources/{source}', [SourceController::class, 'update'])->name('sources.update');
    Route::delete('sources/{source}', [SourceController::class, 'destroy'])->name('sources.destroy');

    Route::get('taxonomy', [TaxonomyController::class, 'index'])->name('taxonomy.index');
    Route::post('taxonomy/types', [TaxonomyController::class, 'storeType'])->name('taxonomy.types.store');
    Route::patch('taxonomy/types/{type}', [TaxonomyController::class, 'updateType'])->name('taxonomy.types.update');
    Route::delete('taxonomy/types/{type}', [TaxonomyController::class, 'destroyType'])->name('taxonomy.types.destroy');
    Route::patch('taxonomy/categories/{category}', [TaxonomyController::class, 'updateCategory'])->name('taxonomy.categories.update');
    Route::post('taxonomy/item-categories', [TaxonomyController::class, 'storeItemCategory'])->name('taxonomy.item-categories.store');

    Route::get('data', [DataExchangeController::class, 'index'])->name('data.index');
    Route::get('data/maps/{map}/export', [DataExchangeController::class, 'export'])->name('data.export');
    Route::post('data/import', [DataExchangeController::class, 'import'])->name('data.import');

    Route::middleware('can:administer')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::patch('users/{user}', [UserController::class, 'update'])->name('users.update');
    });
});
