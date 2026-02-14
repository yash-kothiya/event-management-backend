<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $booking = $this->booking->load('ticket.event');
        $event = $booking->ticket->event;

        return (new MailMessage)
            ->subject('Booking Cancelled - ' . $event->title)
            ->line('Your booking has been cancelled.')
            ->line('Event: ' . $event->title)
            ->line('Ticket Type: ' . $booking->ticket->type)
            ->line('Quantity: ' . $booking->quantity)
            ->line('If you were charged, a refund will be processed within 5-7 business days.')
            ->line('Thank you for your understanding.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $booking = $this->booking->load('ticket.event');

        return [
            'booking_id' => $this->booking->id,
            'event_title' => $booking->ticket->event->title,
            'status' => 'cancelled',
            'message' => 'Your booking has been cancelled.',
        ];
    }
}
