<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\CacheService;
use App\Services\LogService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        protected LogService $logService,
        protected CacheService $cacheService
    ) {}

    /**
     * List events (public). Pagination, search, filter. Cached 10 minutes.
     */
    public function index(Request $request): JsonResponse
    {
        $cacheKey = 'events_' . md5(json_encode($request->query()));

        $events = $this->cacheService->getEventsList($cacheKey, function () use ($request) {
            $query = Event::query()
                ->with('organizer:id,name')
                ->withCount('tickets')
                ->searchByTitle($request->query('search'))
                ->filterByDate($request->query('start_date'), $request->query('end_date'))
                ->filterByLocation($request->query('location'))
                ->orderBy('date');

            return $query->paginate(15);
        });

        $data = [
            'current_page' => $events->currentPage(),
            'data' => EventResource::collection($events->items())->resolve(),
            'per_page' => $events->perPage(),
            'total' => $events->total(),
            'last_page' => $events->lastPage(),
        ];

        return $this->successResponse($data, 'Events retrieved successfully');
    }

    /**
     * Get event details (public). Cached 30 minutes.
     */
    public function show(string $id): JsonResponse
    {
        $event = $this->cacheService->getEventDetail($id, function () use ($id) {
            return Event::with(['organizer:id,name,email', 'tickets'])->find($id);
        });

        if (! $event) {
            return $this->notFoundResponse('Event not found');
        }

        return $this->successResponse(new EventResource($event), 'Event details retrieved successfully');
    }

    /**
     * Create event (organizer/admin).
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = Event::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $this->logService->logEventCreated($event);
        $this->cacheService->clearEventCaches();

        return $this->createdResponse(new EventResource($event), 'Event created successfully');
    }

    /**
     * Update event (owner/admin).
     */
    public function update(UpdateEventRequest $request, string $id): JsonResponse
    {
        $event = Event::find($id);

        if (! $event) {
            return $this->notFoundResponse('Event not found');
        }

        $event->update($request->validated());
        $this->cacheService->clearEventCaches($id);

        return $this->successResponse(new EventResource($event->fresh()), 'Event updated successfully');
    }

    /**
     * Delete event (owner/admin). Prevent if confirmed bookings exist.
     */
    public function destroy(string $id): JsonResponse
    {
        $event = Event::find($id);

        if (! $event) {
            return $this->notFoundResponse('Event not found');
        }

        if ($event->hasConfirmedBookings()) {
            return $this->errorResponse('Cannot delete event with confirmed bookings', 400);
        }

        $event->delete();
        $this->cacheService->clearEventCaches($id);

        return $this->successResponse(null, 'Event deleted successfully');
    }
}
