<?php

namespace Tests;

use App\Models\User;
use Laravel\Sanctum\Sanctum;

trait TestHelpers
{
    /**
     * Create and authenticate admin user.
     */
    protected function authenticatedAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        return $admin;
    }

    /**
     * Create and authenticate organizer user.
     */
    protected function authenticatedOrganizer(): User
    {
        $organizer = User::factory()->organizer()->create();
        Sanctum::actingAs($organizer);

        return $organizer;
    }

    /**
     * Create and authenticate customer user.
     */
    protected function authenticatedCustomer(): User
    {
        $customer = User::factory()->customer()->create();
        Sanctum::actingAs($customer);

        return $customer;
    }
}
