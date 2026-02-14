<?php

use App\Models\User;

beforeEach(function () {
    $this->baseUrl = '/api/v1';
});

test('me endpoint is accessible with valid token', function () {
    $customer = User::factory()->customer()->create();

    $response = $this->actingAs($customer, 'sanctum')
        ->getJson("{$this->baseUrl}/me");

    $response->assertStatus(200)
        ->assertJsonPath('data.role', 'customer');
});

test('admin can access role-protected route when role middleware allows admin', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin, 'sanctum')
        ->getJson("{$this->baseUrl}/me");

    $response->assertStatus(200);
});

test('organizer can access me', function () {
    $organizer = User::factory()->organizer()->create();

    $response = $this->actingAs($organizer, 'sanctum')
        ->getJson("{$this->baseUrl}/me");

    $response->assertStatus(200)
        ->assertJsonPath('data.role', 'organizer');
});
