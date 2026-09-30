<?php

namespace App\Http\Middleware;

use App\Services\Analytics;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Counts public page views after the response is sent. Skips staff, bots, prefetches,
 * partial reloads and non-page routes so the numbers reflect real visitors.
 */
class RecordPageView
{
    private const IGNORED_PATHS = ['admin', 'admin/*', 'api/*', 'settings', 'settings/*', 'sitemap.xml', 'robots.txt', 'up'];

    private const BOTS = '/bot|crawl|spider|slurp|curl|wget|python|headless|lighthouse|preview/i';

    public function __construct(private readonly Analytics $analytics) {}

    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($this->shouldRecord($request, $response)) {
            $this->analytics->recordPageView($request);
        }
    }

    private function shouldRecord(Request $request, Response $response): bool
    {
        $userAgent = (string) $request->userAgent();

        return $request->isMethod('GET')
            && $response->getStatusCode() === 200
            && ($response->headers->has('X-Inertia') || str_contains((string) $response->headers->get('Content-Type'), 'text/html'))
            && $request->header('Purpose') !== 'prefetch'
            && ! $request->hasHeader('X-Inertia-Partial-Component')
            && ! $request->is(...self::IGNORED_PATHS)
            && $userAgent !== ''
            && ! preg_match(self::BOTS, $userAgent)
            && ! $request->user()?->canManageContent();
    }
}
