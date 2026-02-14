<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ticket = Ticket::factory()->create(['quantity' => 200]);
        $quantity = fake()->numberBetween(1, min(10, $ticket->quantity));

        return [
            'user_id' => User::factory()->customer(),
            'ticket_id' => $ticket->id,
            'quantity' => $quantity,
            'status' => BookingStatus::PENDING,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => BookingStatus::PENDING]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => BookingStatus::CONFIRMED]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => ['status' => BookingStatus::CANCELLED]);
    }
}
