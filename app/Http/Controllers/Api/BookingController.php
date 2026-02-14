<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Ticket;
use App\Notifications\BookingCancelledNotification;
use App\Services\LogService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected LogService $logService
    ) {}

    /**
     * Create booking (customer only).
     */
    public function store(StoreBookingRequest $request, string $id): JsonResponse
    {
        $ticket = Ticket::with('event')->find($id);

        if (! $ticket) {
            return $this->notFoundResponse('Ticket not found');
        }

        $quantity = $request->validated('quantity');

        if (! $ticket->isAvailable($quantity)) {
            return $this->errorResponse('Insufficient tickets available', 400);
        }

        try {
            $booking = DB::transaction(function () use ($request, $ticket, $quantity) {
                return Booking::create([
                    'user_id' => $request->user()->id,
                    'ticket_id' => $ticket->id,
                    'quantity' => $quantity,
                    'status' => BookingStatus::PENDING,
                ]);
            });
        } catch (\Throwable $e) {
            $this->logService->logError('BookingCreation', $e, [
                'ticket_id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return $this->errorResponse('Unable to create booking', 400);
        }

        $this->logService->logBookingCreated($booking, $request->user());
        $booking->load(['ticket.event']);

        return $this->createdResponse(new BookingResource($booking), 'Booking created successfully');
    }

    /**
     * List current user's bookings (customer).
     */
    public function index(): JsonResponse
    {
        $user = request()->user();

        $bookings = Booking::forUser($user->id)
            ->with(['ticket.event', 'payment'])
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse(BookingResource::collection($bookings)->resolve());
    }

    /**
     * Cancel booking (customer, own booking only).
     */
    public function cancel(string $id): JsonResponse
    {
        $booking = Booking::with(['payment'])->find($id);

        if (! $booking) {
            return $this->notFoundResponse('Booking not found');
        }

        if ($booking->user_id !== request()->user()->id) {
            return $this->errorResponse('You do not have permission to cancel this booking', 403);
        }

        if ($booking->isCancelled()) {
            return $this->errorResponse('Booking is already cancelled', 400);
        }

        $booking->update(['status' => BookingStatus::CANCELLED]);

        if ($booking->payment && $booking->payment->isSuccess()) {
            $booking->payment->update(['status' => PaymentStatus::REFUNDED]);
        }

        $this->logService->logBookingCancelled($booking, request()->user());
        $booking->user->notify(new BookingCancelledNotification($booking->fresh(['ticket.event'])));

        return $this->successResponse([
            'id' => $booking->id,
            'status' => 'cancelled',
            'updated_at' => $booking->updated_at?->toISOString(),
        ], 'Booking cancelled successfully');
    }
}
