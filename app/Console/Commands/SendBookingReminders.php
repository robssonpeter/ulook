<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendBookingReminders extends Command
{
    protected $signature   = 'app:send-booking-reminders';
    protected $description = 'Send 24h and 1h reminders for upcoming bookings';

    public function handle(): void
    {
        $now = Carbon::now();

        // 24-hour reminder: booking is between 23h and 25h from now
        $window24Start = $now->copy()->addHours(23);
        $window24End   = $now->copy()->addHours(25);

        // 1-hour reminder: booking is between 30min and 90min from now
        $window1Start = $now->copy()->addMinutes(30);
        $window1End   = $now->copy()->addMinutes(90);

        foreach (['24h', '1h'] as $window) {
            $start = $window === '24h' ? $window24Start : $window1Start;
            $end   = $window === '24h' ? $window24End   : $window1End;
            $flag  = $window === '24h' ? 'reminder_24h_sent' : 'reminder_1h_sent';

            Booking::where('status', 'confirmed')
                ->where($flag, false)
                ->whereRaw(
                    "CONCAT(booking_date, ' ', booking_time) BETWEEN ? AND ?",
                    [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]
                )
                ->with(['professionalService', 'service'])
                ->chunkById(50, function ($bookings) use ($flag, $window) {
                    foreach ($bookings as $booking) {
                        $customer = User::find($booking->customer_id);
                        if ($customer) {
                            try {
                                $customer->notify(new BookingReminderNotification($booking, $window));
                            } catch (\Exception $e) {
                                // Non-fatal
                            }
                        }
                        $booking->update([$flag => true]);
                    }
                });
        }

        $this->info('Booking reminders sent.');
    }
}
