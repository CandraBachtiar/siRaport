<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuruHasWaliKelasWorkspace
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guru = $request->user()?->guru;

        if ($guru === null || ! $guru->kelasWali()->exists()) {
            abort(403);
        }

        return $next($request);
    }
}
