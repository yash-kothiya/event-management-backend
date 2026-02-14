<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $booking = Booking::factory()->create();
        $amount = $booking->getTotalAmount();

        return [
            'booking_id' => $booking->id,
            'amount' => $amount,
            'status' => PaymentStatus::SUCCESS,
        ];
    }

    public function forBooking(Booking $booking): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_id' => $booking->id,
            'amount' => $booking->getTotalAmount(),
        ]);
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PaymentStatus::SUCCESS]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PaymentStatus::FAILED]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => ['status' => PaymentStatus::REFUNDED]);
    }
}
