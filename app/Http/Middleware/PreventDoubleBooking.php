<?php

namespace App\Http\Middleware;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PreventDoubleBooking
{
    /**
     * Prevent double booking: cache-based cooldown (5 min) and block if user already has active booking for this ticket.
     *
     * @param  \Closure(Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $ticketId = $request->route('id');

        if (! $ticketId || ! $user) {
            return $next($request);
        }

        $cacheKey = "booking_attempt_{$user->id}_{$ticketId}";

        if (Cache::has($cacheKey)) {
            return response()->json([
                'success' => false,
                'message' => 'You have recently attempted to book this ticket. Please wait before trying again.',
            ], 429);
        }

        $existingBooking = Booking::where('user_id', $user->id)
            ->where('ticket_id', $ticketId)
            ->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED])
            ->exists();

        if ($existingBooking) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active booking for this ticket.',
            ], 400);
        }

        Cache::put($cacheKey, true, now()->addMinutes(5));

        return $next($request);
    }
}
