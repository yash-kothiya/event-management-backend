<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization done by ticket.owner middleware
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ticketId = $this->route('id');
        $ticket = $ticketId ? Ticket::find($ticketId) : null;
        $minQuantity = $ticket ? $ticket->getBookedQuantity() : 0;

        return [
            'type' => 'sometimes|string|max:50',
            'price' => 'sometimes|numeric|min:0|max:999999.99',
            'quantity' => "sometimes|integer|min:{$minQuantity}|max:100000",
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantity.min' => 'Quantity cannot be less than already booked quantity',
        ];
    }
}
