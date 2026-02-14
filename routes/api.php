<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

// Public auth routes (rate limited: 5/min per IP)
Route::prefix('v1')->middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Public event listing
Route::prefix('v1')->group(function () {
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{id}', [EventController::class, 'show']);
});

// Protected routes
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Event management (Organizer/Admin)
    Route::middleware('role:organizer,admin')->group(function () {
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{id}', [EventController::class, 'update'])
            ->middleware('event.owner');
        Route::delete('/events/{id}', [EventController::class, 'destroy'])
            ->middleware('event.owner');

        // Ticket management
        Route::post('/events/{event_id}/tickets', [TicketController::class, 'store'])
            ->middleware('event.owner');
        Route::put('/tickets/{id}', [TicketController::class, 'update'])
            ->middleware('ticket.owner');
        Route::delete('/tickets/{id}', [TicketController::class, 'destroy'])
            ->middleware('ticket.owner');
    });

    // Booking management (Customer)
    Route::middleware('role:customer')->group(function () {
        Route::post('/tickets/{id}/bookings', [BookingController::class, 'store'])
            ->middleware(['prevent.double.booking', 'throttle:booking']);
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancel']);

        // Payment
        Route::post('/bookings/{id}/payment', [PaymentController::class, 'store']);
    });

    // Payment details (any authenticated user)
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
});
