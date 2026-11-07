<?php

namespace App\Support;

use App\Models\User;

/** Where a signed-in user lands on the website, by role. */
class HomeRoute
{
    public static function for(User $user): string
    {
        return match (true) {
            $user->role === 'admin' => route('dashboard'),
            $user->role === 'professional' => route('business.dashboard'),
            default => route('account.bookings'),
        };
    }
}
