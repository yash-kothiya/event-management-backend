<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $booking = $this->resource;
        $status = $booking->status;
        $statusValue = $status instanceof \BackedEnum ? $status->value : $status;

        return [
            'id' => $booking->id,
            'user_id' => $booking->user_id,
            'ticket_id' => $booking->ticket_id,
            'quantity' => $booking->quantity,
            'status' => $statusValue,
            'total_amount' => (float) $booking->getTotalAmount(),
            'user' => $this->when($booking->relationLoaded('user'), [
                'id' => $booking->user?->id,
                'name' => $booking->user?->name,
                'email' => $booking->user?->email,
            ]),
            'ticket' => new TicketResource($this->whenLoaded('ticket')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'created_at' => $booking->created_at?->toISOString(),
            'updated_at' => $booking->updated_at?->toISOString(),
        ];
    }
}
