<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Event;
use App\Models\Ticket;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    use ApiResponseTrait;

    /**
     * Create ticket for event (organizer/admin, event owner).
     */
    public function store(StoreTicketRequest $request, string $event_id): JsonResponse
    {
        $event = Event::find($event_id);

        if (! $event) {
            return $this->notFoundResponse('Event not found');
        }

        $ticket = Ticket::create([
            'event_id' => $event_id,
            ...$request->validated(),
        ]);

        return $this->createdResponse(new TicketResource($ticket), 'Ticket created successfully');
    }

    /**
     * Update ticket (organizer/admin, ticket owner).
     */
    public function update(UpdateTicketRequest $request, string $id): JsonResponse
    {
        $ticket = Ticket::find($id);

        if (! $ticket) {
            return $this->notFoundResponse('Ticket not found');
        }

        $ticket->update($request->validated());

        return $this->successResponse(new TicketResource($ticket->fresh()), 'Ticket updated successfully');
    }

    /**
     * Delete ticket (organizer/admin, ticket owner). Prevent if confirmed bookings exist.
     */
    public function destroy(string $id): JsonResponse
    {
        $ticket = Ticket::find($id);

        if (! $ticket) {
            return $this->notFoundResponse('Ticket not found');
        }

        $hasConfirmed = $ticket->bookings()->where('status', BookingStatus::CONFIRMED)->exists();

        if ($hasConfirmed) {
            return $this->errorResponse('Cannot delete ticket with confirmed bookings', 400);
        }

        $ticket->delete();

        return $this->successResponse(null, 'Ticket deleted successfully');
    }
}
