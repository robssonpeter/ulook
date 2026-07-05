<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfilePhotoController;
use App\Http\Controllers\Api\ProfessionalController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ServiceRequestController;
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
    Route::post('/user/fcm-token', [FcmTokenController::class, 'store']);
    Route::post('/user/profile-photo', [ProfilePhotoController::class, 'update']);
    Route::post('/professional/profile-photo', [ProfilePhotoController::class, 'update']);

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

    // Expenses
    Route::get('/professional/expenses', [ExpenseController::class, 'index']);
    Route::get('/professional/expenses/summary', [ExpenseController::class, 'summary']);
    Route::post('/professional/expenses', [ExpenseController::class, 'store']);
    Route::put('/professional/expenses/{id}', [ExpenseController::class, 'update']);
    Route::delete('/professional/expenses/{id}', [ExpenseController::class, 'destroy']);

    // Open service requests (broadcast matching)
    Route::post('/service-requests', [ServiceRequestController::class, 'store']);
    Route::get('/service-requests/my', [ServiceRequestController::class, 'myRequests']);
    Route::patch('/service-requests/{id}/cancel', [ServiceRequestController::class, 'cancel']);
    Route::post('/service-requests/{requestId}/responses/{responseId}/accept', [ServiceRequestController::class, 'acceptResponse']);
    Route::get('/service-requests/nearby', [ServiceRequestController::class, 'nearby']);
    Route::post('/service-requests/{id}/respond', [ServiceRequestController::class, 'respond']);

    // Inventory
    Route::get('/professional/inventory', [InventoryController::class, 'index']);
    Route::get('/professional/inventory/low-stock', [InventoryController::class, 'lowStock']);
    Route::post('/professional/inventory', [InventoryController::class, 'store']);
    Route::put('/professional/inventory/{id}', [InventoryController::class, 'update']);
    Route::delete('/professional/inventory/{id}', [InventoryController::class, 'destroy']);
    Route::patch('/professional/inventory/{id}/adjust', [InventoryController::class, 'adjust']);

    // In-app notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // Messages / chat
    Route::get('/messages/conversations', [MessageController::class, 'conversations']);
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount']);
    Route::get('/messages/{otherUserId}', [MessageController::class, 'conversation']);
    Route::post('/messages', [MessageController::class, 'send']);
});
