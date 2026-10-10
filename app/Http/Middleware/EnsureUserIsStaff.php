<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Back-office endpoints (the MCP server) are for staff tokens only, not customer accounts. */
class EnsureUserIsStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isStaff(), 403, 'This endpoint is for staff accounts.');

        return $next($request);
    }
}
