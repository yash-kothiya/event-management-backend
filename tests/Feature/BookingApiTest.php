<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\User;

beforeEach(function () {
    $this->baseUrl = '/api/v1';
});

describe('Bookings – Create (customer only)', function () {
    test('customer can create booking when tickets available', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['quantity' => 50]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("{$this->baseUrl}/tickets/{$ticket->id}/bookings", [
                'quantity' => 2,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Booking created successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id', 'user_id', 'ticket_id', 'quantity', 'status', 'total_amount',
                    'ticket' => ['id', 'type', 'price', 'event' => ['title', 'date']],
                    'created_at',
                ],
            ])
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.quantity', 2);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 2,
            'status' => BookingStatus::PENDING->value,
        ]);
    });

    test('customer cannot create booking when insufficient tickets', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['quantity' => 5]);
        // Book 4 already so only 1 left
        Booking::create([
            'user_id' => User::factory()->customer()->create()->id,
            'ticket_id' => $ticket->id,
            'quantity' => 4,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("{$this->baseUrl}/tickets/{$ticket->id}/bookings", [
                'quantity' => 3,
            ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'Insufficient tickets available']);
    });

    test('organizer cannot create booking', function () {
        $organizer = User::factory()->organizer()->create();
        $ticket = Ticket::factory()->create(['quantity' => 50]);

        $response = $this->actingAs($organizer, 'sanctum')
            ->postJson("{$this->baseUrl}/tickets/{$ticket->id}/bookings", ['quantity' => 1]);

        $response->assertStatus(403);
    });

    test('create booking validates quantity', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['quantity' => 50]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("{$this->baseUrl}/tickets/{$ticket->id}/bookings", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    });

    test('prevent double booking: customer cannot create second pending booking for same ticket', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create(['quantity' => 50]);
        Booking::create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 1,
            'status' => BookingStatus::PENDING,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("{$this->baseUrl}/tickets/{$ticket->id}/bookings", ['quantity' => 1]);

        $response->assertStatus(400)
            ->assertJsonFragment(['message' => 'You already have an active booking for this ticket.']);
    });
});

describe('Bookings – List (customer only)', function () {
    test('customer sees only own bookings', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create();
        Booking::create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 1,
            'status' => BookingStatus::PENDING,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson("{$this->baseUrl}/bookings");

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');
    });

    test('organizer cannot list bookings index', function () {
        $organizer = User::factory()->organizer()->create();

        $response = $this->actingAs($organizer, 'sanctum')
            ->getJson("{$this->baseUrl}/bookings");

        $response->assertStatus(403);
    });
});

describe('Bookings – Cancel (customer, own only)', function () {
    test('customer can cancel own pending booking', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create();
        $booking = Booking::create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 1,
            'status' => BookingStatus::PENDING,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->putJson("{$this->baseUrl}/bookings/{$booking->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'data' => ['status' => 'cancelled'],
            ]);
        $booking->refresh();
        $this->assertTrue($booking->isCancelled());
    });

    test('customer cannot cancel another user booking', function () {
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
            ->putJson("{$this->baseUrl}/bookings/{$booking->id}/cancel");

        $response->assertStatus(403)
            ->assertJson(['success' => false, 'message' => 'You do not have permission to cancel this booking']);
    });

    test('cannot cancel already cancelled booking', function () {
        $customer = User::factory()->customer()->create();
        $ticket = Ticket::factory()->create();
        $booking = Booking::create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 1,
            'status' => BookingStatus::CANCELLED,
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->putJson("{$this->baseUrl}/bookings/{$booking->id}/cancel");

        $response->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'Booking is already cancelled']);
    });
});
