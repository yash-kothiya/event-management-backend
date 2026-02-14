<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait CommonQueryScopes
{
    /**
     * Filter records by date range
     */
    public function scopeFilterByDate($query, $startDate = null, $endDate = null)
    {
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Search by title (case-insensitive on PostgreSQL, LIKE elsewhere e.g. SQLite)
     */
    public function scopeSearchByTitle($query, $search)
    {
        if ($search) {
            $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            return $query->where('title', $operator, '%' . $search . '%');
        }

        return $query;
    }

    /**
     * Filter by location (case-insensitive on PostgreSQL)
     */
    public function scopeFilterByLocation($query, $location)
    {
        if ($location) {
            $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            return $query->where('location', $operator, '%' . $location . '%');
        }

        return $query;
    }
}
