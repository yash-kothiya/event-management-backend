<?php

namespace App\Http\Middleware;

use App\Models\Event;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEventOwnership
{
    /**
     * @param  \Closure(Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $eventId = $request->route('id') ?? $request->route('event') ?? $request->route('event_id');

        if (! $eventId) {
            return response()->json([
                'success' => false,
                'message' => 'Event identifier is required',
            ], 400);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($user->isOrganizer()) {
            $event = Event::find($eventId);

            if (! $event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found',
                ], 404);
            }

            if ($event->created_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this event',
                ], 403);
            }
        }

        return $next($request);
    }
}
