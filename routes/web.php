<?php

use App\Http\Controllers\Web\PublicSiteController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/professionals', [PublicSiteController::class, 'professionals'])->name('professionals.index');
Route::get('/professionals/{professional}', [PublicSiteController::class, 'professional'])
    ->whereNumber('professional')
    ->name('professionals.show');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
