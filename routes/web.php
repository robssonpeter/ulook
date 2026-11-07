<?php

use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\BusinessController;
use App\Http\Controllers\Web\PublicSiteController;
use App\Http\Middleware\EnsureProfessional;
use App\Support\HomeRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [PublicSiteController::class, 'home'])->name('home');
Route::get('/professionals', [PublicSiteController::class, 'professionals'])->name('professionals.index');
Route::get('/professionals/{professional}', [PublicSiteController::class, 'professional'])
    ->whereNumber('professional')
    ->name('professionals.show');

Route::middleware(['auth:sanctum', config('jetstream.auth_session')])->group(function () {
    // Admins keep the Jetstream dashboard; everyone else goes to their own area.
    Route::get('/dashboard', function (Request $request) {
        return $request->user()->role === 'admin'
            ? Inertia::render('Dashboard')
            : redirect(HomeRoute::for($request->user()));
    })->name('dashboard');

    // Customers
    Route::get('/book/{professional}', [AccountController::class, 'startBooking'])->whereNumber('professional')->name('book.start');
    Route::post('/bookings', [AccountController::class, 'storeBooking'])->name('bookings.store');
    Route::get('/account/bookings', [AccountController::class, 'bookings'])->name('account.bookings');
    Route::patch('/account/bookings/{booking}/cancel', [AccountController::class, 'cancel'])->name('account.bookings.cancel');

    // Professionals
    Route::get('/business/setup', [BusinessController::class, 'setup'])->name('business.setup');
    Route::post('/business/setup', [BusinessController::class, 'storeSetup'])->name('business.setup.store');

    Route::middleware(EnsureProfessional::class)->prefix('business')->name('business.')->group(function () {
        Route::get('/', [BusinessController::class, 'dashboard'])->name('dashboard');
        Route::patch('/bookings/{booking}', [BusinessController::class, 'updateBooking'])->name('bookings.update');
        Route::get('/profile', [BusinessController::class, 'profile'])->name('profile');
        Route::put('/profile', [BusinessController::class, 'updateProfile'])->name('profile.update');
        Route::post('/services', [BusinessController::class, 'addService'])->name('services.store');
        Route::patch('/services/{service}', [BusinessController::class, 'updateService'])->name('services.update');
        Route::put('/hours', [BusinessController::class, 'saveHours'])->name('hours.update');
    });
});
