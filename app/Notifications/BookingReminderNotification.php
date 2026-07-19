<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public string $window // '24h' or '1h'
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $service = $this->booking->professionalService?->name
                ?? $this->booking->service?->name
                ?? 'your appointment';

        $when = $this->window === '24h' ? 'tomorrow' : 'in 1 hour';
        $date = $this->booking->booking_date;
        $time = $this->booking->booking_time;

        return (new MailMessage)
            ->subject("Reminder: Your ULOOK appointment is {$when}")
            ->greeting("Hello {$notifiable->name},")
            ->line("This is a reminder that your appointment for **{$service}** is {$when}.")
            ->line("**Date:** {$date} at {$time}")
            ->line('We look forward to seeing you!')
            ->salutation('The ULOOK Team');
    }

    public function toArray($notifiable): array
    {
        $service = $this->booking->professionalService?->name
                ?? $this->booking->service?->name
                ?? 'your appointment';

        $when = $this->window === '24h' ? 'tomorrow' : 'in 1 hour';

        return [
            'type'       => 'booking_reminder',
            'booking_id' => $this->booking->id,
            'title'      => 'Appointment Reminder',
            'body'       => "Your {$service} appointment is {$when} at {$this->booking->booking_time}.",
        ];
    }
}
