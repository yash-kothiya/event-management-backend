<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Ticket;
use App\Models\User;

beforeEach(function () {
    $this->baseUrl = '/api/v1';
});

describe('Tickets – Create (organizer/admin, event owner)', function () {
    test('organizer can create ticket for own event', function () {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);

        $response = $this->actingAs($organizer, 'sanctum')
            ->postJson("{$this->baseUrl}/events/{$event->id}/tickets", [
                'type' => 'VIP',
                'price' => 99.50,
                'quantity' => 100,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Ticket created successfully',
                'data' => [
                    'event_id' => $event->id,
                    'type' => 'VIP',
                    'price' => 99.50,
                    'quantity' => 100,
                ],
            ])
            ->assertJsonStructure(['data' => ['id', 'created_at']]);

        $this->assertDatabaseHas('tickets', ['event_id' => $event->id, 'type' => 'VIP']);
    });

    test('organizer cannot create ticket for another organizer event', function () {
        $owner = User::factory()->organizer()->create();
        $other = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($other, 'sanctum')
            ->postJson("{$this->baseUrl}/events/{$event->id}/tickets", [
                'type' => 'Standard',
                'price' => 50,
                'quantity' => 50,
            ]);

        $response->assertStatus(403);
    });

    test('admin can create ticket for any event', function () {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("{$this->baseUrl}/events/{$event->id}/tickets", [
                'type' => 'Economy',
                'price' => 25,
                'quantity' => 200,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'Economy');
    });

    test('create ticket validates required fields', function () {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);

        $response = $this->actingAs($organizer, 'sanctum')
            ->postJson("{$this->baseUrl}/events/{$event->id}/tickets", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'price', 'quantity']);
    });
});

describe('Tickets – Update (ticket owner)', function () {
    test('organizer can update own event ticket', function () {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id, 'type' => 'VIP', 'quantity' => 100]);

        $response = $this->actingAs($organizer, 'sanctum')
            ->putJson("{$this->baseUrl}/tickets/{$ticket->id}", [
                'type' => 'Premium VIP',
                'quantity' => 120,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.type', 'Premium VIP')
            ->assertJsonPath('data.quantity', 120);
    });

    test('organizer cannot update ticket of another organizer event', function () {
        $owner = User::factory()->organizer()->create();
        $other = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $owner->id]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id]);

        $response = $this->actingAs($other, 'sanctum')
            ->putJson("{$this->baseUrl}/tickets/{$ticket->id}", ['type' => 'Hacked']);

        $response->assertStatus(403);
    });
});

describe('Tickets – Delete (ticket owner)', function () {
    test('organizer can delete ticket with no confirmed bookings', function () {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id]);

        $response = $this->actingAs($organizer, 'sanctum')
            ->deleteJson("{$this->baseUrl}/tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Ticket deleted successfully']);
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    });

    test('cannot delete ticket with confirmed bookings', function () {
        $organizer = User::factory()->organizer()->create();
        $customer = User::factory()->customer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);
        $ticket = Ticket::factory()->create(['event_id' => $event->id]);
        Booking::create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 1,
            'status' => BookingStatus::CONFIRMED,
        ]);

        $response = $this->actingAs($organizer, 'sanctum')
            ->deleteJson("{$this->baseUrl}/tickets/{$ticket->id}");

        $response->assertStatus(400)
            ->assertJson(['success' => false, 'message' => 'Cannot delete ticket with confirmed bookings']);
    });
});
