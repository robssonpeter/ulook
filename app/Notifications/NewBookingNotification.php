<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBookingNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $customerName = $this->booking->customer?->name ?? 'A customer';
        $service      = $this->booking->professionalService?->name
                     ?? $this->booking->service?->name
                     ?? 'Service';
        $date = $this->booking->booking_date;
        $time = $this->booking->booking_time;

        return (new MailMessage)
            ->subject('New Booking Request — ULOOK')
            ->greeting("Hello {$notifiable->name},")
            ->line("{$customerName} has requested a booking with you.")
            ->line("**Service:** {$service}")
            ->line("**Date:** {$date} at {$time}")
            ->line('Log in to your ULOOK Business app to accept or decline.')
            ->salutation('The ULOOK Team');
    }
}
