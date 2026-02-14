<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::table('payments')->truncate();
        DB::table('bookings')->truncate();
        DB::table('tickets')->truncate();
        DB::table('events')->truncate();
        DB::table('users')->truncate();

        Schema::enableForeignKeyConstraints();

        // 2 admin, 3 organizer, 10 customer
        $admins = User::factory(2)->admin()->create();
        $organizers = User::factory(3)->organizer()->create();
        $customers = User::factory(10)->customer()->create();

        // 5 events (assigned to organizers)
        $events = collect();
        $eventCounts = [2, 2, 1]; // 5 total across 3 organizers
        foreach ($organizers as $index => $organizer) {
            $count = $eventCounts[$index] ?? 1;
            $events = $events->merge(
                Event::factory($count)->create(['created_by' => $organizer->id])
            );
        }

        // 15 tickets (3 per event)
        $tickets = collect();
        $types = [['VIP', 100, 200], ['Standard', 50, 100], ['Economy', 20, 50]];
        foreach ($events as $event) {
            foreach ($types as $i => [$type, $minPrice, $maxPrice]) {
                $tickets->push(Ticket::create([
                    'event_id' => $event->id,
                    'type' => $type,
                    'price' => round(fake()->randomFloat(2, $minPrice, $maxPrice), 2),
                    'quantity' => fake()->numberBetween(50, 200),
                ]));
            }
        }

        // 20 bookings (customers only, quantity within availability)
        $ticketIds = $tickets->pluck('id')->shuffle()->values();
        $customerIds = $customers->pluck('id')->shuffle()->values();

        for ($i = 0; $i < 20; $i++) {
            $ticket = Ticket::find($ticketIds->get($i % $ticketIds->count()));
            $ticket->refresh();
            $quantity = min(fake()->numberBetween(1, 3), max(1, (int) $ticket->quantity));

            $status = fake()->randomElement([
                BookingStatus::PENDING,
                BookingStatus::CONFIRMED,
                BookingStatus::CONFIRMED,
                BookingStatus::CANCELLED,
            ]);

            $booking = Booking::create([
                'user_id' => $customerIds->get($i % $customerIds->count()),
                'ticket_id' => $ticket->id,
                'quantity' => $quantity,
                'status' => $status,
            ]);

            if ($status === BookingStatus::CONFIRMED) {
                Payment::create([
                    'booking_id' => $booking->id,
                    'amount' => $booking->getTotalAmount(),
                    'status' => fake()->randomElement(['success', 'success', 'failed', 'refunded']),
                ]);
            }
        }
    }
}
