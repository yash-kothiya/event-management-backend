<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->paragraphs(2, true),
            'date' => fake()->dateTimeBetween('+1 week', '+6 months'),
            'location' => fake()->city() . ', ' . fake()->country(),
            'created_by' => User::factory(),
        ];
    }

    public function forOrganizer(User $organizer): static
    {
        return $this->state(fn (array $attributes) => [
            'created_by' => $organizer->id,
        ]);
    }
}
