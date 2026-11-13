<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\LinkGroupController;
use App\Http\Controllers\LinkOrderController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('groups', LinkGroupController::class)->except(['index', 'show']);

    Route::patch('/groups/{group}/links/order', LinkOrderController::class)->name('groups.links.order');

    // Scoped so a {link} that does not belong to {group} resolves to a 404.
    Route::resource('groups.links', LinkController::class)
        ->only(['index', 'store', 'update', 'destroy'])
        ->scoped(['link' => 'id'])
        ->where(['link' => '[0-9]+']);
});

require __DIR__.'/auth.php';
