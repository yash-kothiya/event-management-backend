<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ticket = $this->resource;

        return [
            'id' => $ticket->id,
            'event_id' => $ticket->event_id,
            'type' => $ticket->type,
            'price' => (float) $ticket->price,
            'quantity' => (int) $ticket->quantity,
            'available_quantity' => $this->when(
                $ticket->relationLoaded('bookings') || isset($ticket->available_quantity),
                $ticket->available_quantity ?? $ticket->getAvailableQuantity()
            ),
            'event' => new EventResource($this->whenLoaded('event')),
            'created_at' => $ticket->created_at?->toISOString(),
            'updated_at' => $ticket->updated_at?->toISOString(),
        ];
    }
}
