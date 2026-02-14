<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    public const EVENTS_LIST_TTL = 600;         // 10 minutes

    public const EVENT_DETAIL_TTL = 1800;      // 30 minutes

    public const TICKET_AVAILABILITY_TTL = 300; // 5 minutes

    /**
     * Get cached events list.
     */
    public function getEventsList(string $key, callable $callback): mixed
    {
        return Cache::remember($key, self::EVENTS_LIST_TTL, $callback);
    }

    /**
     * Get cached event detail (eventId is UUID).
     */
    public function getEventDetail(string $eventId, callable $callback): mixed
    {
        $key = "event_{$eventId}";

        return Cache::remember($key, self::EVENT_DETAIL_TTL, $callback);
    }

    /**
     * Clear event caches (single event and events list).
     */
    public function clearEventCaches(?string $eventId = null): void
    {
        if ($eventId) {
            Cache::forget("event_{$eventId}");
        }

        $this->clearEventListCaches();
    }

    /**
     * Clear all event list caches. With Redis you could use Cache::tags(['events'])->flush().
     */
    private function clearEventListCaches(): void
    {
        // Without tags we flush. In production with Redis, use cache tags for events list.
        Cache::flush();
    }
}
