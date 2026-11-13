<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LinkGroupController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('groups', LinkGroupController::class)->except(['index', 'show']);
});

require __DIR__.'/auth.php';
