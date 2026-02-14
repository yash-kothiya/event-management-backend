<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isOrganizer() || $this->user()->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|max:50',
            'price' => 'required|numeric|min:0|max:999999.99',
            'quantity' => 'required|integer|min:1|max:100000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'price.min' => 'Ticket price cannot be negative',
            'quantity.min' => 'Quantity must be at least 1',
            'type.required' => 'Ticket type is required',
        ];
    }
}
