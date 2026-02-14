<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;

beforeEach(function () {
    $this->baseUrl = '/api/v1';
});

describe('Payments – Process (customer, own booking only)', function () {
    test('customer can process payment for own pending booking', function () {
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

        // Service simulates success/failure; we expect 200 (success) or 400 (failed)
        $this->assertContains($response->status(), [200, 400]);
        $response->assertJson(['success' => $response->status() === 200]);
        $this->assertDatabaseHas('payments', ['booking_id' => $booking->id]);
    });

    test('customer cannot process payment for another user booking', function () {
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

    test('process payment validates payment_method', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create();
        $booking = Booking::create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 1,
            'status' => BookingStatus::PENDING,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("{$this->baseUrl}/bookings/{$booking->id}/payment", [
                'payment_method' => 'invalid_method',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    });
});

describe('Payments – Show (authenticated, own only)', function () {
    test('customer can view own payment', function () {
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
            'amount' => 50,
            'status' => 'success',
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

    test('customer cannot view another user payment', function () {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create();
        $booking = Booking::create([
            'user_id' => $other->id,
            'ticket_id' => $ticket->id,
            'quantity' => 1,
            'status' => BookingStatus::CONFIRMED,
        ]);
        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => 50,
            'status' => 'success',
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson("{$this->baseUrl}/payments/{$payment->id}");

        $response->assertStatus(403)
            ->assertJson(['success' => false, 'message' => 'You do not have permission to view this payment']);
    });

    test('payment show returns 404 for invalid id', function () {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson("{$this->baseUrl}/payments/00000000-0000-0000-0000-000000000000");

        $response->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'Payment not found']);
    });
});
