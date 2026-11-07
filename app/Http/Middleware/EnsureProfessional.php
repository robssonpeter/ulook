<?php

namespace App\Http\Middleware;

use App\Models\Professional;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Business pages need a professional profile; send everyone else to set one up. */
class EnsureProfessional
{
    public function handle(Request $request, Closure $next): Response
    {
        $professional = Professional::where('user_id', $request->user()->id)->first();

        if (! $professional) {
            return redirect()->route('business.setup');
        }

        $request->attributes->set('professional', $professional);

        return $next($request);
    }
}
