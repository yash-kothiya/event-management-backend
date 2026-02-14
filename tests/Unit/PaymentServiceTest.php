<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Ticket;
use App\Services\PaymentService;

beforeEach(function () {
    $this->service = new PaymentService;
});

describe('PaymentService – processPayment', function () {
    test('creates payment record for booking', function () {
        $ticket = Ticket::factory()->create(['price' => 25, 'quantity' => 10]);
        $booking = Booking::create([
            'user_id' => \App\Models\User::factory()->customer()->create()->id,
            'ticket_id' => $ticket->id,
            'quantity' => 2,
            'status' => BookingStatus::PENDING,
        ]);

        $payment = $this->service->processPayment($booking, 'credit_card');

        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'amount' => 50.00,
        ]);
        $this->assertContains($payment->status->value ?? $payment->status, ['success', 'failed']);
    });
});

describe('PaymentService – simulatePaymentGateway', function () {
    test('returns boolean', function () {
        $result = $this->service->simulatePaymentGateway();
        $this->assertIsBool($result);
    });
});

describe('PaymentService – validatePaymentAmount', function () {
    test('valid amount returns true', function () {
        $this->assertTrue($this->service->validatePaymentAmount(100));
        $this->assertTrue($this->service->validatePaymentAmount(0.01));
        $this->assertTrue($this->service->validatePaymentAmount(100000));
    });

    test('invalid amount returns false', function () {
        $this->assertFalse($this->service->validatePaymentAmount(0));
        $this->assertFalse($this->service->validatePaymentAmount(-10));
        $this->assertFalse($this->service->validatePaymentAmount(100001));
    });
});

describe('PaymentService – processRefund', function () {
    test('refund succeeds for success payment', function () {
        $booking = Booking::create([
            'user_id' => \App\Models\User::factory()->customer()->create()->id,
            'ticket_id' => Ticket::factory()->create()->id,
            'quantity' => 1,
            'status' => BookingStatus::CONFIRMED,
        ]);
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => 50,
            'status' => PaymentStatus::SUCCESS,
        ]);

        $result = $this->service->processRefund($payment);

        $this->assertIsBool($result);
        if ($result) {
            $payment->refresh();
            $this->assertTrue($payment->isRefunded());
        }
    });

    test('refund returns false for failed payment', function () {
        $booking = Booking::create([
            'user_id' => \App\Models\User::factory()->customer()->create()->id,
            'ticket_id' => Ticket::factory()->create()->id,
            'quantity' => 1,
            'status' => BookingStatus::PENDING,
        ]);
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => 50,
            'status' => PaymentStatus::FAILED,
        ]);

        $result = $this->service->processRefund($payment);

        $this->assertFalse($result);
    });
});

describe('PaymentService – getStatusDescription', function () {
    test('returns description for success, failed, refunded', function () {
        $this->assertSame('Payment completed successfully', $this->service->getStatusDescription('success'));
        $this->assertSame('Payment processing failed', $this->service->getStatusDescription('failed'));
        $this->assertSame('Payment has been refunded', $this->service->getStatusDescription('refunded'));
        $this->assertSame('Unknown payment status', $this->service->getStatusDescription('unknown'));
    });
});
