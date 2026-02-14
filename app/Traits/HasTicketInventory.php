<?php

namespace App\Traits;

use App\Enums\BookingStatus;

trait HasTicketInventory
{
    /**
     * Get total booked quantity (pending + confirmed) for this ticket.
     */
    public function getBookedQuantityAttribute(): int
    {
        return (int) $this->bookings()
            ->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED])
            ->sum('quantity');
    }

    /**
     * Available quantity = quantity - booked.
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, (int) $this->quantity - $this->booked_quantity);
    }

    /**
     * Check if requested quantity is available.
     */
    public function isAvailable(int $requestedQuantity): bool
    {
        return $requestedQuantity >= 1 && $this->available_quantity >= $requestedQuantity;
    }

    /**
     * Get available quantity (method for backward compatibility).
     */
    public function getAvailableQuantity(): int
    {
        return $this->available_quantity;
    }

    /**
     * Get booked quantity (method for backward compatibility).
     */
    public function getBookedQuantity(): int
    {
        return $this->booked_quantity;
    }

    /**
     * No-op: capacity is fixed; availability is computed from bookings.
     */
    public function decreaseAvailability(int $quantity): void
    {
        // Availability is computed from bookings; no column to decrement
    }

    /**
     * No-op: capacity is fixed; availability is computed from bookings.
     */
    public function increaseAvailability(int $quantity): void
    {
        // No column to update
    }
}
