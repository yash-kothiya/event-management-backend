<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;

class PaymentService
{
    /**
     * Process payment for a booking.
     */
    public function processPayment(Booking $booking, string $paymentMethod = 'credit_card'): Payment
    {
        $amount = $booking->getTotalAmount();
        $isSuccess = $this->simulatePaymentGateway();

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => $amount,
            'status' => $isSuccess ? PaymentStatus::SUCCESS : PaymentStatus::FAILED,
        ]);

        if ($isSuccess) {
            $booking->update(['status' => BookingStatus::CONFIRMED]);
        }

        return $payment;
    }

    /**
     * Simulate payment gateway response. 90% success rate.
     */
    public function simulatePaymentGateway(): bool
    {
        return rand(1, 100) <= 90;
    }

    /**
     * Process refund for a payment.
     */
    public function processRefund(Payment $payment): bool
    {
        if (! $payment->isSuccess()) {
            return false;
        }

        $isSuccess = rand(1, 100) <= 95;

        if ($isSuccess) {
            $payment->update(['status' => PaymentStatus::REFUNDED]);

            return true;
        }

        return false;
    }

    /**
     * Validate payment amount (max transaction $100,000).
     */
    public function validatePaymentAmount(float $amount): bool
    {
        return $amount > 0 && $amount <= 100000;
    }

    /**
     * Get payment status description.
     */
    public function getStatusDescription(string $status): string
    {
        return match ($status) {
            'success' => 'Payment completed successfully',
            'failed' => 'Payment processing failed',
            'refunded' => 'Payment has been refunded',
            default => 'Unknown payment status',
        };
    }
}
