<?php

use App\Models\Event;
use App\Models\User;

describe('User – role checks', function () {
    test('isAdmin returns true for admin', function () {
        $user = User::factory()->admin()->create();
        $this->assertTrue($user->isAdmin());
        $this->assertFalse($user->isOrganizer());
        $this->assertFalse($user->isCustomer());
    });

    test('isOrganizer returns true for organizer', function () {
        $user = User::factory()->organizer()->create();
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->isOrganizer());
        $this->assertFalse($user->isCustomer());
    });

    test('isCustomer returns true for customer', function () {
        $user = User::factory()->customer()->create();
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isOrganizer());
        $this->assertTrue($user->isCustomer());
    });
});

describe('User – ownsEvent', function () {
    test('returns true when user created the event', function () {
        $organizer = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer->id]);

        $this->assertTrue($organizer->ownsEvent($event));
    });

    test('returns false when another user created the event', function () {
        $organizer1 = User::factory()->organizer()->create();
        $organizer2 = User::factory()->organizer()->create();
        $event = Event::factory()->create(['created_by' => $organizer1->id]);

        $this->assertFalse($organizer2->ownsEvent($event));
    });
});
