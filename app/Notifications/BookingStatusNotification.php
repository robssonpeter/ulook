<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingStatusNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking, public string $oldStatus) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $newStatus = ucfirst($this->booking->status);
        $service   = $this->booking->professionalService?->name
                  ?? $this->booking->service?->name
                  ?? 'Service';
        $date = $this->booking->booking_date;
        $time = $this->booking->booking_time;

        $lines = [
            'confirmed'  => 'Your booking has been confirmed! We look forward to seeing you.',
            'completed'  => 'Your appointment has been marked as completed. We hope you had a great experience!',
            'cancelled'  => 'Unfortunately, your booking has been cancelled.',
        ];

        $message = $lines[$this->booking->status] ?? "Your booking status has been updated to: {$newStatus}.";

        return (new MailMessage)
            ->subject("Booking {$newStatus} — ULOOK")
            ->greeting("Hello {$notifiable->name},")
            ->line($message)
            ->line("**Service:** {$service}")
            ->line("**Date:** {$date} at {$time}")
            ->salutation('The ULOOK Team');
    }
}
