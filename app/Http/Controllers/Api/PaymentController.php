<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\BookingConfirmedNotification;
use App\Services\LogService;
use App\Services\PaymentService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected PaymentService $paymentService,
        protected LogService $logService
    ) {}

    /**
     * Process payment for booking (customer, own booking only).
     */
    public function store(ProcessPaymentRequest $request, string $id): JsonResponse
    {
        $booking = Booking::with('ticket.event')->find($id);

        if (! $booking) {
            return $this->notFoundResponse('Booking not found');
        }

        if ($booking->user_id !== $request->user()->id) {
            return $this->errorResponse('You do not have permission to pay for this booking', 403);
        }

        if (! $booking->isPending()) {
            return $this->errorResponse('Booking is not pending payment', 400);
        }

        if ($booking->payment()->exists()) {
            return $this->errorResponse('Payment already processed for this booking', 400);
        }

        $payment = $this->paymentService->processPayment($booking, $request->validated('payment_method'));
        $this->logService->logPaymentProcessed($payment, $booking);
        $payment->load('booking');

        if ($payment->isSuccess()) {
            $booking->user->notify(new BookingConfirmedNotification($booking->fresh(['ticket.event'])));

            return $this->successResponse(new PaymentResource($payment), 'Payment processed successfully. Confirmation email sent.');
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment processing failed',
            'data' => new PaymentResource($payment),
        ], 400);
    }

    /**
     * Get payment details (authenticated user; must own the booking).
     */
    public function show(string $id): JsonResponse
    {
        $payment = Payment::with('booking.ticket.event')->find($id);

        if (! $payment) {
            return $this->notFoundResponse('Payment not found');
        }

        if ($payment->booking->user_id !== request()->user()->id) {
            return $this->errorResponse('You do not have permission to view this payment', 403);
        }

        return $this->successResponse(new PaymentResource($payment));
    }
}
