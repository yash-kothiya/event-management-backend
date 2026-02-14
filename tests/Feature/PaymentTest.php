<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\BookingConfirmedNotification;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->baseUrl = '/api/v1';
});

describe('Payment – Process', function () {
    test('customer can process payment (accept 200 or 400 from simulator)', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['price' => 50, 'quantity' => 10]);
        $booking = Booking::create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 2,
            'status' => BookingStatus::PENDING,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("{$this->baseUrl}/bookings/{$booking->id}/payment", [
                'payment_method' => 'credit_card',
            ]);

        $this->assertContains($response->status(), [200, 400]);
        $response->assertJson(['success' => $response->status() === 200]);
        $this->assertDatabaseHas('payments', ['booking_id' => $booking->id]);
    });

    test('notification sent on payment success', function () {
        Notification::fake();

        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['price' => 50, 'quantity' => 10]);
        $booking = Booking::create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 2,
            'status' => BookingStatus::PENDING,
        ]);

        $this->mock(PaymentService::class, function ($mock) use ($booking) {
            $mock->shouldReceive('processPayment')
                ->once()
                ->andReturnUsing(function () use ($booking) {
                    return Payment::create([
                        'booking_id' => $booking->id,
                        'amount' => 100,
                        'status' => PaymentStatus::SUCCESS,
                    ]);
                });
        });

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("{$this->baseUrl}/bookings/{$booking->id}/payment", [
                'payment_method' => 'credit_card',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Payment processed successfully. Confirmation email sent.',
            ]);

        Notification::assertSentTo($customer, BookingConfirmedNotification::class);
    });

    test('customer cannot pay for another user booking', function () {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create();
        $booking = Booking::create([
            'user_id' => $other->id,
            'ticket_id' => $ticket->id,
            'quantity' => 1,
            'status' => BookingStatus::PENDING,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("{$this->baseUrl}/bookings/{$booking->id}/payment", [
                'payment_method' => 'credit_card',
            ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false, 'message' => 'You do not have permission to pay for this booking']);
    });

    test('cannot process payment for non-pending booking', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create();
        $booking = Booking::create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 1,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("{$this->baseUrl}/bookings/{$booking->id}/payment", [
                'payment_method' => 'credit_card',
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'Booking is not pending payment']);
    });
});

describe('Payment – View', function () {
    test('customer can view own payment details', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create();
        $booking = Booking::create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 1,
            'status' => BookingStatus::CONFIRMED,
        ]);
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => 50.00,
            'status' => PaymentStatus::SUCCESS,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson("{$this->baseUrl}/payments/{$payment->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $payment->id,
                    'booking_id' => $booking->id,
                    'amount' => 50.0,
                    'status' => 'success',
                ],
            ]);
    });
});
