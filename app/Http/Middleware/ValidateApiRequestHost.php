<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiRequestHost
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowed = config('app.api.allowed_hosts');

        $host = $request->getHost();
        $origin = $request->headers->get('origin');

        if (! in_array($host, $allowed, true)) {
            return response()->json(['message' => 'Invalid host.'], 403);
        }

        if ($origin) {
            $originHost = parse_url($origin, PHP_URL_HOST);
            if ($originHost && ! in_array($originHost, $allowed, true)) {
                return response()->json(['message' => 'Invalid origin.'], 403);
            }
        }

        return $next($request);
    }
}
