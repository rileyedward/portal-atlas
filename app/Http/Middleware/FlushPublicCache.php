<?php

namespace App\Http\Middleware;

use App\Support\PublicCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Invalidates cached public page data after a successful content write.
 */
class FlushPublicCache
{
    public function __construct(private PublicCache $cache) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethodSafe() && $response->getStatusCode() < 400) {
            $this->cache->flush();
        }

        return $response;
    }
}
