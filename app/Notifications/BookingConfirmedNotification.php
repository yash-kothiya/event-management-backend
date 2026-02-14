<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmedNotification extends Notification implements ShouldQueue
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
        $booking = $this->booking->load('ticket.event', 'payment');
        $event = $booking->ticket->event;
        $totalAmount = $booking->getTotalAmount();
        $status = $booking->status;
        $statusValue = $status instanceof \BackedEnum ? $status->value : $status;

        return (new MailMessage)
            ->subject('Booking Confirmed - ' . $event->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your booking has been confirmed successfully.')
            ->line('**Event Details:**')
            ->line('Event: ' . $event->title)
            ->line('Date: ' . $event->date->format('F j, Y g:i A'))
            ->line('Location: ' . $event->location)
            ->line('Ticket Type: ' . $booking->ticket->type)
            ->line('Quantity: ' . $booking->quantity)
            ->line('Total Amount: $' . number_format($totalAmount, 2))
            ->line('Please keep this email for your records.')
            ->action('View Booking', url('/api/v1/bookings/' . $booking->id))
            ->line('Thank you for booking with us!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $booking = $this->booking->load('ticket.event');
        $status = $booking->status;
        $statusValue = $status instanceof \BackedEnum ? $status->value : $status;

        return [
            'booking_id' => $booking->id,
            'event_title' => $booking->ticket->event->title,
            'event_date' => $booking->ticket->event->date->toDateTimeString(),
            'ticket_type' => $booking->ticket->type,
            'quantity' => $booking->quantity,
            'total_amount' => $booking->getTotalAmount(),
            'status' => $statusValue,
            'message' => 'Your booking for ' . $booking->ticket->event->title . ' has been confirmed.',
        ];
    }
}
