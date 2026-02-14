<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'date' => $this->date?->format('Y-m-d H:i:s'),
            'location' => $this->location,
            'organizer' => $this->when($this->relationLoaded('organizer'), [
                'id' => $this->organizer?->id,
                'name' => $this->organizer?->name,
                'email' => $this->organizer?->email,
            ]),
            'tickets' => TicketResource::collection($this->whenLoaded('tickets')),
            'tickets_count' => $this->when(isset($this->tickets_count), $this->tickets_count),
            'created_by' => $this->when(isset($this->created_by), $this->created_by),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
