<?php

use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// P23-004: SEO endpoints
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::get('/admin/google/callback', [App\Http\Controllers\GoogleAuthController::class, 'callback'])
    ->name('google.callback')
    ->middleware(['web']);


