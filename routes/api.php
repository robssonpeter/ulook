<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ProfilePhotoController;
use App\Http\Controllers\Api\ProfessionalController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/professionals', [ProfessionalController::class, 'index']);
Route::get('/professionals/{id}', [ProfessionalController::class, 'show']);
Route::get('/professionals/{id}/reviews', [ReviewController::class, 'indexForProfessional']);

Route::get('/services', [ServiceController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return new UserResource($request->user());
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/user/profile-photo', [ProfilePhotoController::class, 'update']);

    Route::post('/professionals', [ProfessionalController::class, 'store']);
    Route::put('/professional/profile', [ProfessionalController::class, 'updateProfile']);
    Route::get('/professional/profile', [ProfessionalController::class, 'myProfile']);
    Route::get('/professionals/{id}/services', [ProfessionalController::class, 'getServices']);
    Route::post('/professional/services', [ProfessionalController::class, 'addService']);
    Route::patch('/professional/services/{id}', [ProfessionalController::class, 'updateService']);
    Route::patch('/professional/services/{id}/toggle', [ProfessionalController::class, 'toggleService']);
    Route::delete('/professional/services/{id}', [ProfessionalController::class, 'deleteService']);
    Route::get('/professional/bookings', [BookingController::class, 'index']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);

    Route::post('/reviews', [ReviewController::class, 'store']);
});
