<?php

namespace App\Http\Middleware;

use App\Models\Ticket;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTicketOwnership
{
    /**
     * @param  \Closure(Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $ticketId = $request->route('id');

        if (! $ticketId) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket identifier is required',
            ], 400);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $ticket = Ticket::with('event')->find($ticketId);

        if (! $ticket) {
            return response()->json([
                'success' => false,
                'message' => 'Ticket not found',
            ], 404);
        }

        if ($ticket->event->created_by !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this ticket',
            ], 403);
        }

        return $next($request);
    }
}
