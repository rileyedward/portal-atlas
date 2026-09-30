<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * Anonymous aggregate analytics. Never stores user ids, raw IPs or user agents. Page
 * views keep only a coarse device type and a visitor hash that rotates every day.
 */
class Analytics
{
    public const EVENTS = ['map_view', 'search', 'marker_open', 'filter_toggle', 'item_view'];

    public function record(string $name, ?string $subjectType = null, ?int $subjectId = null, ?string $term = null, ?int $resultCount = null): void
    {
        if (! in_array($name, self::EVENTS, true) || ! $this->enabled()) {
            return;
        }

        try {
            AnalyticsEvent::create([
                'name' => $name,
                'subject_type' => $subjectType ? Str::limit($subjectType, 40, '') : null,
                'subject_id' => $subjectId,
                'term' => $term !== null ? Str::limit(Str::lower(trim($term)), 120, '') : null,
                'result_count' => $resultCount,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function recordPageView(Request $request): void
    {
        if (! $this->enabled()) {
            return;
        }

        $userAgent = (string) $request->userAgent();

        try {
            PageView::create([
                'visitor_hash' => hash_hmac('sha256', $request->ip().'|'.$userAgent.'|'.today()->toDateString(), (string) config('app.key')),
                'path' => Str::limit('/'.ltrim($request->path(), '/'), 255, ''),
                'referrer_host' => $this->referrerHost($request),
                'device' => match (true) {
                    (bool) preg_match('/ipad|tablet/i', $userAgent) => 'tablet',
                    (bool) preg_match('/mobi|android/i', $userAgent) => 'mobile',
                    default => 'desktop',
                },
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private function referrerHost(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = Str::of($host)->lower()->chopStart('www.')->limit(120, '')->toString();

        return $host === Str::chopStart(Str::lower($request->getHost()), 'www.') ? null : $host;
    }

    private function enabled(): bool
    {
        return (bool) config('services.analytics.enabled', true);
    }
}
