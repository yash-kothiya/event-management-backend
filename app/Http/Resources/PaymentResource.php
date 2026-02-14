<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payment = $this->resource;
        $status = $payment->status;
        $statusValue = $status instanceof \BackedEnum ? $status->value : $status;

        return [
            'id' => $payment->id,
            'booking_id' => $payment->booking_id,
            'amount' => (float) $payment->amount,
            'status' => $statusValue,
            'booking' => new BookingResource($this->whenLoaded('booking')),
            'created_at' => $payment->created_at?->toISOString(),
            'updated_at' => $payment->updated_at?->toISOString(),
        ];
    }
}
