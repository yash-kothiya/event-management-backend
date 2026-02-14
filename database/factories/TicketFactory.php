<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    protected static array $types = ['VIP', 'Standard', 'Economy'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(self::$types);
        $price = match ($type) {
            'VIP' => fake()->randomFloat(2, 100, 200),
            'Standard' => fake()->randomFloat(2, 50, 100),
            default => fake()->randomFloat(2, 20, 50),
        };

        return [
            'event_id' => Event::factory(),
            'type' => $type,
            'price' => $price,
            'quantity' => fake()->numberBetween(50, 200),
        ];
    }

    public function forEvent(Event $event): static
    {
        return $this->state(fn (array $attributes) => [
            'event_id' => $event->id,
        ]);
    }

    public function vip(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'VIP',
            'price' => fake()->randomFloat(2, 100, 200),
        ]);
    }

    public function standard(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Standard',
            'price' => fake()->randomFloat(2, 50, 100),
        ]);
    }

    public function economy(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'Economy',
            'price' => fake()->randomFloat(2, 20, 50),
        ]);
    }
}
