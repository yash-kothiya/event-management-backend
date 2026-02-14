<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('id');
        if (! $event) {
            return false;
        }
        $eventModel = \App\Models\Event::find($event);

        return $eventModel && ($this->user()->ownsEvent($eventModel) || $this->user()->isAdmin());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:5000',
            'date' => 'sometimes|date|after:now',
            'location' => 'sometimes|string|max:255',
        ];
    }
}
