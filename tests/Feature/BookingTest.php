<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\User;

beforeEach(function () {
    $this->baseUrl = '/api/v1';
});

describe('Booking – Customer create', function () {
    test('customer can create booking', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['quantity' => 100]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("{$this->baseUrl}/tickets/{$ticket->id}/bookings", [
                'quantity' => 2,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Booking created successfully',
            ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 2,
        ]);

        $ticket->refresh();
        $this->assertSame(98, $ticket->getAvailableQuantity());
    });

    test('booking fails with insufficient tickets', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['quantity' => 5]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("{$this->baseUrl}/tickets/{$ticket->id}/bookings", [
                'quantity' => 10,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Insufficient tickets available',
            ]);
    });
});

describe('Booking – Customer list', function () {
    test('customer can view their bookings', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create();
        Booking::create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 1,
            'status' => BookingStatus::PENDING,
        ]);
        Booking::create([
            'user_id' => $customer->id,
            'ticket_id' => Ticket::factory()->create()->id,
            'quantity' => 1,
            'status' => BookingStatus::PENDING,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson("{$this->baseUrl}/bookings");

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    });
});

describe('Booking – Cancel', function () {
    test('customer can cancel booking', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['quantity' => 90]);
        $booking = Booking::create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 5,
            'status' => BookingStatus::PENDING,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->putJson("{$this->baseUrl}/bookings/{$booking->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Booking cancelled successfully',
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
        ]);
        $booking->refresh();
        $this->assertTrue($booking->isCancelled());
        $this->assertSame(90, $ticket->fresh()->getAvailableQuantity());
    });

    test('customer cannot cancel others booking', function () {
        $customer1 = User::factory()->customer()->create();
        $customer2 = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create();
        $booking = Booking::create([
            'user_id' => $customer1->id,
            'ticket_id' => $ticket->id,
            'quantity' => 1,
            'status' => BookingStatus::PENDING,
        ]);

        $response = $this->actingAs($customer2, 'sanctum')
            ->putJson("{$this->baseUrl}/bookings/{$booking->id}/cancel");

        $response->assertStatus(403);
    });
});

describe('Booking – Double booking prevention', function () {
    test('double booking is prevented', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['quantity' => 100]);

        Booking::create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 2,
            'status' => BookingStatus::PENDING,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("{$this->baseUrl}/tickets/{$ticket->id}/bookings", [
                'quantity' => 2,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'You already have an active booking for this ticket.',
            ]);
    });
});
