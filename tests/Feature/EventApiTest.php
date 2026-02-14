<?php

use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->baseUrl = '/api/v1';
});

describe('Events – Public (no auth)', function () {
    test('list events returns paginated results', function () {
        Event::factory(3)->create();

        $response = $this->getJson("{$this->baseUrl}/events");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Events retrieved successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => ['id', 'title', 'description', 'date', 'location', 'organizer', 'tickets_count', 'created_at'],
                    ],
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    });

    test('list events can filter by search', function () {
        Event::factory()->create(['title' => 'Concert Night']);
        Event::factory()->create(['title' => 'Tech Meetup']);

        $response = $this->getJson("{$this->baseUrl}/events?search=Concert");

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data.data')));
    });

    test('show event returns event with tickets and available_quantity', function () {
        $event = Event::factory()->has(\App\Models\Ticket::factory()->count(2))->create();
        $event->load('organizer');

        $response = $this->getJson("{$this->baseUrl}/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $event->id)
            ->assertJsonPath('data.title', $event->title)
            ->assertJsonStructure([
                'data' => [
                    'id', 'title', 'description', 'date', 'location',
                    'organizer' => ['id', 'name', 'email'],
                    'tickets' => [
                        '*' => ['id', 'type', 'price', 'quantity', 'available_quantity'],
                    ],
                ],
            ]);
    });

    test('show event returns 404 for invalid id', function () {
        $response = $this->getJson("{$this->baseUrl}/events/00000000-0000-0000-0000-000000000000");

        $response->assertStatus(404)
            ->assertJson(['success' => false, 'message' => 'Event not found']);
    });
});

describe('Events – Create (organizer/admin only)', function () {
    test('organizer can create event', function () {
        $organizer = User::factory()->organizer()->create();

        $response = $this->actingAs($organizer, 'sanctum')
            ->postJson("{$this->baseUrl}/events", [
                'title' => 'New Concert',
                'description' => 'A great concert',
                'date' => now()->addDays(30)->format('Y-m-d H:i:s'),
                'location' => 'Central Park',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Event created successfully',
                'data' => [
                    'title' => 'New Concert',
                    'location' => 'Central Park',
                ],
            ])
            ->assertJsonStructure(['data' => ['id', 'created_at']]);

        $this->assertDatabaseHas('events', ['title' => 'New Concert', 'created_by' => $organizer->id]);
    });

    test('admin can create event', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("{$this->baseUrl}/events", [
                'title' => 'Admin Event',
                'description' => 'Description',
                'date' => now()->addDays(7)->format('Y-m-d H:i:s'),
                'location' => 'City Hall',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Admin Event');
    });

    test('customer cannot create event', function () {
        $customer = User::factory()->customer()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("{$this->baseUrl}/events", [
                'title' => 'Customer Event',
                'description' => 'Desc',
                'date' => now()->addDays(1)->format('Y-m-d H:i:s'),
                'location' => 'Here',
            ]);

        $response->assertStatus(403);
    });

    test('create event validates required fields', function () {
        $organizer = User::factory()->organizer()->create();

        $response = $this->actingAs($organizer, 'sanctum')
            ->postJson("{$this->baseUrl}/events", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'date', 'location']);
    });

    test('create event rejects past date', function () {
        $organizer = User::factory()->organizer()->create();

        $response = $this->actingAs($organizer, 'sanctum')
            ->postJson("{$this->baseUrl}/events", [
                'title' => 'Past Event',
                'description' => 'Desc',
                'date' => now()->subDay()->format('Y-m-d H:i:s'),
                'location' => 'Here',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    });
});

describe('Events – Update (owner/admin only)', function () {
    test('organizer can update own event', function () {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id, 'title' => 'Original']);

        $response = $this->actingAs($organizer, 'sanctum')
            ->putJson("{$this->baseUrl}/events/{$event->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');
        $event->refresh();
        $this->assertSame('Updated Title', $event->title);
    });

    test('organizer cannot update another organizer event', function () {
        $owner = User::factory()->organizer()->create();
        $other = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($other, 'sanctum')
            ->putJson("{$this->baseUrl}/events/{$event->id}", ['title' => 'Hacked']);

        $response->assertStatus(403);
    });

    test('admin can update any event', function () {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->create(['title' => 'Original']);

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("{$this->baseUrl}/events/{$event->id}", ['title' => 'Admin Updated']);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Admin Updated');
    });
});

describe('Events – Delete (owner/admin only)', function () {
    test('organizer can delete own event with no confirmed bookings', function () {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);

        $response = $this->actingAs($organizer, 'sanctum')
            ->deleteJson("{$this->baseUrl}/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Event deleted successfully']);
        $this->assertDatabaseMissing('events', ['id' => $event->id]);
    });

    test('delete event returns 404 for invalid id', function () {
        $organizer = User::factory()->organizer()->create();

        $response = $this->actingAs($organizer, 'sanctum')
            ->deleteJson("{$this->baseUrl}/events/00000000-0000-0000-0000-000000000000");

        $response->assertStatus(404);
    });
});
