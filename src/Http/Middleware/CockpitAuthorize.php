<?php

namespace Mathieu\Cockpit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class CockpitAuthorize
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            return $next($request);
        }

        if (! $request->user()) {
            return redirect()->guest(route('login'));
        }

        if (Gate::allows('viewCockpit', $request->user())) {
            return $next($request);
        }

        abort(403, 'Unauthorized access to Cockpit.');
    }
}
