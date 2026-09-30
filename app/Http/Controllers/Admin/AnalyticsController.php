<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public const RANGES = [7, 30, 90];

    public function __invoke(Request $request): Response
    {
        $range = in_array($request->integer('range'), self::RANGES, true) ? $request->integer('range') : 30;
        $today = CarbonImmutable::today();
        $since = $today->subDays($range - 1);
        $previousSince = $since->subDays($range);

        $window = fn (CarbonImmutable $from, ?CarbonImmutable $to = null): Builder => PageView::query()
            ->where('created_at', '>=', $from)
            ->when($to, fn (Builder $q) => $q->where('created_at', '<', $to));

        $visitors = $window($since)->distinct()->count('visitor_hash');
        $pageviews = $window($since)->count();
        $previousVisitors = $window($previousSince, $since)->distinct()->count('visitor_hash');
        $previousPageviews = $window($previousSince, $since)->count();

        return Inertia::render('admin/analytics/Index', [
            'range' => $range,
            'ranges' => self::RANGES,
            'summary' => [
                'visitors' => $visitors,
                'pageviews' => $pageviews,
                'views_per_visitor' => $visitors > 0 ? round($pageviews / $visitors, 1) : 0,
                'visitors_today' => $window($today)->distinct()->count('visitor_hash'),
                'change' => [
                    'visitors' => $this->change($visitors, $previousVisitors),
                    'pageviews' => $this->change($pageviews, $previousPageviews),
                ],
            ],
            'daily' => $this->daily($window($since), $since, $range),
            'top_pages' => $window($since)
                ->select('path', DB::raw('count(*) as views'), DB::raw('count(distinct visitor_hash) as visitors'))
                ->groupBy('path')->orderByDesc('views')->limit(10)->get()
                ->map(fn (PageView $row) => [
                    'path' => $row->path,
                    'views' => (int) $row->getAttribute('views'),
                    'visitors' => (int) $row->getAttribute('visitors'),
                ]),
            'referrers' => $window($since)
                ->select('referrer_host', DB::raw('count(*) as total'))
                ->groupBy('referrer_host')->orderByDesc('total')->limit(10)->get()
                ->map(fn (PageView $row) => [
                    'host' => $row->referrer_host ?? 'Direct / unknown',
                    'total' => (int) $row->getAttribute('total'),
                ]),
            'devices' => $window($since)
                ->select('device', DB::raw('count(*) as total'))
                ->groupBy('device')->orderByDesc('total')->get()
                ->map(fn (PageView $row) => [
                    'device' => $row->device,
                    'total' => (int) $row->getAttribute('total'),
                ]),
            'retentionDays' => PageView::RETENTION_DAYS,
        ]);
    }

    /**
     * One entry per day in the range, zero-filled.
     *
     * @param  Builder<PageView>  $query
     * @return list<array{date: string, visitors: int, pageviews: int}>
     */
    private function daily(Builder $query, CarbonImmutable $since, int $range): array
    {
        $rows = $query
            ->select(DB::raw('date(created_at) as day'), DB::raw('count(*) as pageviews'), DB::raw('count(distinct visitor_hash) as visitors'))
            ->groupBy('day')->get()
            ->keyBy(fn (PageView $row) => substr((string) $row->getAttribute('day'), 0, 10));

        $days = [];
        for ($i = 0; $i < $range; $i++) {
            $date = $since->addDays($i)->toDateString();
            $row = $rows->get($date);
            $days[] = [
                'date' => $date,
                'visitors' => (int) $row?->getAttribute('visitors'),
                'pageviews' => (int) $row?->getAttribute('pageviews'),
            ];
        }

        return $days;
    }

    private function change(int $current, int $previous): ?int
    {
        return $previous > 0 ? (int) round(($current - $previous) / $previous * 100) : null;
    }
}
