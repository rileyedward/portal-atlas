<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\GameVersion;
use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use App\Models\Objective;
use App\Models\Report;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $since = now()->subDays(30);
        $current = GameVersion::current();

        $top = fn (string $name, string $column) => AnalyticsEvent::query()
            ->where('name', $name)->where('created_at', '>=', $since)->whereNotNull($column)
            ->select($column, DB::raw('count(*) as total'))
            ->groupBy($column)->orderByDesc('total')->limit(8)->get();

        return Inertia::render('admin/Dashboard', [
            'counts' => [
                'maps' => Map::count(),
                'markers' => Marker::count(),
                'items' => Item::count(),
                'objectives' => Objective::count(),
                'open_reports' => Report::where('status', ReportStatus::Open->value)->count(),
                'low_confidence' => Marker::where('confidence', '<', 30)->whereNull('confidence_override')->count(),
                'unverified_current' => $current ? Marker::where(fn ($q) => $q->whereNull('verified_version_id')->orWhere('verified_version_id', '!=', $current->id))->count() : null,
            ],
            'currentVersion' => $current?->label(),
            'recentReports' => Report::with('reportable')->where('status', ReportStatus::Open->value)->latest()->limit(6)->get()
                ->map(fn (Report $r) => [
                    'id' => $r->id,
                    'type' => $r->type->label(),
                    'subject' => $r->reportable?->getAttribute('name'),
                    'message' => $r->message,
                    'created_at' => $r->created_at->toIso8601String(),
                ]),
            'analytics' => [
                'top_searches' => $top('search', 'term'),
                'failed_searches' => AnalyticsEvent::query()->where('name', 'search')->where('result_count', 0)
                    ->where('created_at', '>=', $since)->select('term', DB::raw('count(*) as total'))
                    ->groupBy('term')->orderByDesc('total')->limit(8)->get(),
                'top_maps' => $this->named($top('map_view', 'subject_id'), Map::class),
                'top_markers' => $this->named($top('marker_open', 'subject_id'), Marker::class),
                'top_items' => $this->named($top('item_view', 'subject_id'), Item::class),
            ],
        ]);
    }

    /**
     * @param  Collection<int, AnalyticsEvent>  $rows
     * @param  class-string<Map|Marker|Item>  $model
     * @return list<array{name: string, total: int}>
     */
    private function named($rows, string $model): array
    {
        $names = $model::query()->whereIn('id', $rows->pluck('subject_id'))->pluck('name', 'id');

        return array_values($rows->map(fn (AnalyticsEvent $row) => [
            'name' => (string) ($names[$row->subject_id] ?? "#{$row->subject_id}"),
            'total' => (int) $row->getAttribute('total'),
        ])->all());
    }
}
