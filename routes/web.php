<?php

use App\Http\Controllers\Api\AnalyticsEventController;
use App\Http\Controllers\Api\MarkerDetailController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\ExtractionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ObjectiveController;
use App\Http\Controllers\Player\MapNoteController;
use App\Http\Controllers\Player\MarkerStateController;
use App\Http\Controllers\Player\RaidRouteController;
use App\Http\Controllers\Player\TrackedItemController;
use App\Http\Controllers\RaidPlannerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\VerificationController;
use App\Support\Seo;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
| Public, anonymous-friendly pages. The map must always work without an account.
*/
Route::get('/', HomeController::class)->name('home');
Route::get('maps/{map}', [MapController::class, 'show'])->name('maps.show');
Route::get('extractions/{map}', [ExtractionController::class, 'show'])->name('extractions.show');
Route::get('items', [ItemController::class, 'index'])->name('items.index');
Route::get('items/{item}', [ItemController::class, 'show'])->name('items.show');
Route::get('objectives', [ObjectiveController::class, 'index'])->name('objectives.index');
Route::get('objectives/{objective}', [ObjectiveController::class, 'show'])->name('objectives.show');
Route::get('planner', [RaidPlannerController::class, 'show'])->name('planner.show');
Route::get('about', fn () => Inertia::render('About', [
    'seo' => Seo::make('About', 'About this unofficial, community-made Active Matter interactive map and raid companion: data policy, confidence scores and how to send feedback.'),
]))->name('about');
Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('robots.txt', fn () => response(implode("\n", [
    'User-agent: *',
    'Disallow: /admin',
    'Disallow: /api/',
    'Disallow: /me/',
    'Disallow: /settings',
    '',
    'Sitemap: '.route('sitemap'),
]), 200, ['Content-Type' => 'text/plain']))->name('robots');

Route::prefix('api')->name('api.')->group(function () {
    Route::get('search', SearchController::class)->middleware('throttle:search')->name('search');
    Route::get('markers/{marker}', MarkerDetailController::class)->name('markers.show');
    Route::post('planner', [RaidPlannerController::class, 'plan'])->middleware('throttle:search')->name('planner.plan');
    Route::post('events', AnalyticsEventController::class)->middleware('throttle:search')->name('events.store');
    Route::post('reports', [ReportController::class, 'store'])->middleware('throttle:reports')->name('reports.store');
});

/*
| Player features (require an account).
*/
Route::middleware(['auth', 'throttle:player-writes'])->prefix('api')->name('api.')->group(function () {
    Route::post('markers/{marker}/confirm', [VerificationController::class, 'store'])->name('markers.confirm');
    Route::put('markers/{marker}/state', [MarkerStateController::class, 'update'])->name('markers.state');
    Route::post('notes', [MapNoteController::class, 'store'])->name('notes.store');
    Route::patch('notes/{note}', [MapNoteController::class, 'update'])->name('notes.update');
    Route::delete('notes/{note}', [MapNoteController::class, 'destroy'])->name('notes.destroy');
    Route::post('routes', [RaidRouteController::class, 'store'])->name('routes.store');
    Route::patch('routes/{route}', [RaidRouteController::class, 'update'])->name('routes.update');
    Route::delete('routes/{route}', [RaidRouteController::class, 'destroy'])->name('routes.destroy');
    Route::put('tracked-items/{item:id}', [TrackedItemController::class, 'update'])->name('tracked-items.update');
    Route::delete('tracked-items/{item:id}', [TrackedItemController::class, 'destroy'])->name('tracked-items.destroy');
    Route::post('goals/{recipe}', [TrackedItemController::class, 'addGoal'])->name('goals.store');
});

Route::middleware('auth')->prefix('me')->name('me.')->group(function () {
    Route::get('items', [TrackedItemController::class, 'index'])->name('items');
    Route::get('routes', [RaidRouteController::class, 'index'])->name('routes');
});

Route::redirect('dashboard', '/me/items')->middleware('auth')->name('dashboard');

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
