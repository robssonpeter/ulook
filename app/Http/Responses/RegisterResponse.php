<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

/**
 * New accounts start as customers (same as the apps). Anyone who signed up
 * as a professional goes on to set up their business profile, which is
 * what upgrades their role.
 */
class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json('', 201);
        }

        if ($request->input('account_type') === 'professional') {
            return redirect()->route('business.setup');
        }

        return redirect()->intended(route('professionals.index'));
    }
}
