<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\LinkGroupAppearanceController;
use App\Http\Controllers\LinkGroupController;
use App\Http\Controllers\LinkOrderController;
use App\Http\Controllers\PublicLinkGroupController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::redirect('/', '/dashboard');

// Permanent public URL (may be written to NFC cards). Stateless: no session row or
// cookies are created for anonymous visitors.
Route::get('/p/{slug}', PublicLinkGroupController::class)
    ->where('slug', '[a-z0-9-]+')
    ->withoutMiddleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        StartSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
    ])
    ->name('public.groups.show');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('groups', LinkGroupController::class)->except(['index', 'show']);

    Route::get('/groups/{group}/appearance', [LinkGroupAppearanceController::class, 'edit'])->name('groups.appearance.edit');
    Route::put('/groups/{group}/appearance', [LinkGroupAppearanceController::class, 'update'])->name('groups.appearance.update');

    Route::patch('/groups/{group}/links/order', LinkOrderController::class)->name('groups.links.order');

    // Scoped so a {link} that does not belong to {group} resolves to a 404.
    Route::resource('groups.links', LinkController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->scoped(['link' => 'id'])
        ->where(['link' => '[0-9]+']);
});

require __DIR__.'/auth.php';
