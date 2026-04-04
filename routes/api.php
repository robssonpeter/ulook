<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ProfessionalController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/professionals', [ProfessionalController::class, 'index']);
Route::get('/professionals/{id}', [ProfessionalController::class, 'show']);

Route::get('/services', [ServiceController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return new \App\Http\Resources\UserResource($request->user());
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/professionals', [ProfessionalController::class, 'store']);
    Route::patch('/professionals/{id}', [ProfessionalController::class, 'update']);
    Route::get('/professional/bookings', [BookingController::class, 'index']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);

    Route::post('/reviews', [ReviewController::class, 'store']);
});
