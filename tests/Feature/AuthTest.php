<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->baseUrl = '/api/v1';
});

describe('Registration', function () {
    test('user can register as customer', function () {
        $response = $this->postJson("{$this->baseUrl}/register", [
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'name' => 'Test Customer',
                        'email' => 'customer@example.com',
                        'role' => 'customer',
                    ],
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email', 'phone', 'role'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'customer@example.com']);
    });

    test('user can register as organizer', function () {
        $response = $this->postJson("{$this->baseUrl}/register", [
            'name' => 'Test Organizer',
            'email' => 'organizer@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'organizer',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.role', 'organizer');
    });

    test('registration rejects admin role', function () {
        $response = $this->postJson("{$this->baseUrl}/register", [
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('role');
    });

    test('registration validates required fields', function () {
        $response = $this->postJson("{$this->baseUrl}/register", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
    });

    test('registration requires unique email', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson("{$this->baseUrl}/register", [
            'name' => 'Another User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    });

    test('registration requires password confirmation', function () {
        $response = $this->postJson("{$this->baseUrl}/register", [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'wrong',
            'role' => 'customer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    });

    test('registration requires password minimum 8 characters', function () {
        $response = $this->postJson("{$this->baseUrl}/register", [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'role' => 'customer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');
    });

    test('registration validates email format', function () {
        $response = $this->postJson("{$this->baseUrl}/register", [
            'name' => 'Test User',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'customer',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    });

    test('registration accepts optional phone and returns it in response', function () {
        $response = $this->postJson("{$this->baseUrl}/register", [
            'name' => 'Test User',
            'email' => 'withphone@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+1234567890',
            'role' => 'customer',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.user.phone', '+1234567890');
    });

    test('registration rejects invalid role values', function () {
        $response = $this->postJson("{$this->baseUrl}/register", [
            'name' => 'Test User',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'superadmin',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('role');
    });
});

describe('Login', function () {
    test('user can login with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson("{$this->baseUrl}/login", [
            'email' => 'login@example.com',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => 'login@example.com',
                    ],
                ],
            ])
            ->assertJsonStructure(['data' => ['token']]);
    });

    test('login returns 401 for invalid credentials', function () {
        User::factory()->create(['email' => 'user@example.com']);

        $response = $this->postJson("{$this->baseUrl}/login", [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    });

    test('login validates required fields', function () {
        $response = $this->postJson("{$this->baseUrl}/login", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    });

    test('login returns 401 for non-existent email', function () {
        $response = $this->postJson("{$this->baseUrl}/login", [
            'email' => 'nobody@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    });

    test('login validates email format', function () {
        $response = $this->postJson("{$this->baseUrl}/login", [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    });
});

describe('Protected routes', function () {
    test('me returns authenticated user', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("{$this->baseUrl}/me");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ],
            ]);
    });

    test('me returns 401 when unauthenticated', function () {
        $response = $this->getJson("{$this->baseUrl}/me");

        $response->assertStatus(401);
    });

    test('logout revokes token', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("{$this->baseUrl}/logout");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        $this->assertCount(0, $user->tokens);
    });

    test('logout returns 401 when unauthenticated', function () {
        $response = $this->postJson("{$this->baseUrl}/logout");

        $response->assertStatus(401);
    });

    test('me returns 401 with invalid or expired token', function () {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson("{$this->baseUrl}/me");

        $response->assertStatus(401);
    });
});
