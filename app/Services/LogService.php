<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class LogService
{
    /**
     * Log booking creation.
     */
    public function logBookingCreated($booking, $user): void
    {
        Log::info('Booking created', [
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ticket_id' => $booking->ticket_id,
            'quantity' => $booking->quantity,
            'total_amount' => $booking->getTotalAmount(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log payment processing.
     */
    public function logPaymentProcessed($payment, $booking): void
    {
        $status = $payment->status;
        $statusValue = $status instanceof \BackedEnum ? $status->value : $status;

        Log::info('Payment processed', [
            'payment_id' => $payment->id,
            'booking_id' => $booking->id,
            'amount' => $payment->amount,
            'status' => $statusValue,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log booking cancellation.
     */
    public function logBookingCancelled($booking, $user): void
    {
        Log::info('Booking cancelled', [
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ticket_id' => $booking->ticket_id,
            'refund_amount' => $booking->getTotalAmount(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log event creation.
     */
    public function logEventCreated($event): void
    {
        Log::info('Event created', [
            'event_id' => $event->id,
            'title' => $event->title,
            'created_by' => $event->created_by,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log failed operations.
     */
    public function logError(string $operation, \Throwable $exception, array $context = []): void
    {
        Log::error("Error in {$operation}", [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log authentication events.
     */
    public function logAuthentication(string $event, $user = null): void
    {
        Log::info("Authentication: {$event}", [
            'user_id' => $user?->id,
            'email' => $user?->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }
}
