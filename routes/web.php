<?php

use Illuminate\Support\Facades\Route;
use Saucebase\Core\Http\Controllers\HomeController;
use Saucebase\Core\Http\Controllers\LocalizationController;
use Saucebase\Core\Http\Controllers\RobotsController;
use Saucebase\Core\Http\Controllers\SettingsController;
use Saucebase\Core\Http\Controllers\SitemapController;

// The application's routes load after these, so a route it declares with the same URI wins.
Route::middleware('web')->group(function () {
    Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
    Route::get('/robots.txt', RobotsController::class)->name('robots');

    Route::post('/locale/{locale}', LocalizationController::class)->name('locale');

    Route::middleware(['auth', 'verified'])->group(function () {
        // User-scoped, so it opens without a workspace.
        Route::get('/settings', SettingsController::class)->name('settings');
        Route::get('/home', HomeController::class)->name('home');
    });
});
