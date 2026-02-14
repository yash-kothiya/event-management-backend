<?php

use App\Models\Event;
use App\Models\User;

beforeEach(function () {
    $this->baseUrl = '/api/v1';
});

describe('Event – Guest access', function () {
    test('guest can view events list', function () {
        Event::factory()->count(5)->create();

        $response = $this->getJson("{$this->baseUrl}/events");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => ['id', 'title', 'description', 'date', 'location'],
                    ],
                ],
            ]);
    });

    test('guest can view single event', function () {
        $event = Event::factory()->create();

        $response = $this->getJson("{$this->baseUrl}/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $event->id,
                    'title' => $event->title,
                ],
            ]);
    });
});

describe('Event – Organizer create', function () {
    test('organizer can create event', function () {
        $organizer = User::factory()->organizer()->create();
        $eventData = [
            'title' => 'New Event',
            'description' => 'Event description',
            'date' => now()->addDays(30)->format('Y-m-d H:i:s'),
            'location' => 'New York',
        ];

        $response = $this->actingAs($organizer, 'sanctum')
            ->postJson("{$this->baseUrl}/events", $eventData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Event created successfully',
            ]);

        $this->assertDatabaseHas('events', [
            'title' => 'New Event',
            'created_by' => $organizer->id,
        ]);
    });

    test('customer cannot create event', function () {
        $customer = User::factory()->customer()->create();
        $eventData = [
            'title' => 'New Event',
            'description' => 'Event description',
            'date' => now()->addDays(30)->format('Y-m-d H:i:s'),
            'location' => 'New York',
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("{$this->baseUrl}/events", $eventData);

        $response->assertStatus(403);
    });
});

describe('Event – Organizer update', function () {
    test('organizer can update own event', function () {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);

        $response = $this->actingAs($organizer, 'sanctum')
            ->putJson("{$this->baseUrl}/events/{$event->id}", ['title' => 'Updated Event Title']);

        $response->assertStatus(200);
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Event Title',
        ]);
    });

    test('organizer cannot update others event', function () {
        $organizer1 = User::factory()->organizer()->create();
        $organizer2 = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer1->id]);

        $response = $this->actingAs($organizer2, 'sanctum')
            ->putJson("{$this->baseUrl}/events/{$event->id}", ['title' => 'Updated Title']);

        $response->assertStatus(403);
    });
});

describe('Event – Search and filter', function () {
    test('events can be searched', function () {
        Event::factory()->create(['title' => 'Rock Concert']);
        Event::factory()->create(['title' => 'Jazz Festival']);

        $response = $this->getJson("{$this->baseUrl}/events?search=Rock");

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $titles = array_column($data, 'title');
        $this->assertNotEmpty($titles);
        $this->assertTrue(
            count(array_filter($titles, fn ($t) => str_contains($t, 'Rock'))) >= 1
        );
    });

    test('events can be filtered by location', function () {
        Event::factory()->create(['location' => 'New York']);
        Event::factory()->create(['location' => 'Los Angeles']);

        $response = $this->getJson("{$this->baseUrl}/events?location=New York");

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $locations = array_column($data, 'location');
        $this->assertNotEmpty($locations);
        $this->assertTrue(
            count(array_filter($locations, fn ($l) => str_contains($l, 'New York'))) >= 1
        );
    });
});
