<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Community\ResolveReport;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use App\Models\Objective;
use App\Models\Report;
use App\Services\ConfidenceCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The feedback inbox: everything players send, whether about a specific
 * marker/item/objective/map or about the site in general.
 */
class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status', ReportStatus::Open->value)->toString();
        $kind = $request->string('kind', 'all')->toString();
        $type = $request->string('type')->toString();
        $search = $request->string('search')->trim()->toString();

        $reports = Report::query()
            ->with(['reportable', 'map:id,slug,name', 'user:id,name,email', 'resolver:id,name'])
            ->when($status !== 'all', fn (Builder $q) => $q->where('status', $status))
            ->when($kind === 'data', fn (Builder $q) => $q->whereNotNull('reportable_type'))
            ->when($kind === 'general', fn (Builder $q) => $q->whereNull('reportable_type'))
            ->when($kind === 'position', fn (Builder $q) => $q->whereNotNull('suggested_x'))
            ->when($type !== '', fn (Builder $q) => $q->where('type', $type))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->whereLike('message', '%'.addcslashes($search, '%_\\').'%')
                ->orWhereLike('context', '%'.addcslashes($search, '%_\\').'%')))
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Report $report) => $this->present($report));

        return Inertia::render('admin/reports/Index', [
            'reports' => $reports,
            'filters' => ['status' => $status, 'kind' => $kind, 'type' => $type, 'search' => $search],
            'statuses' => ReportStatus::options(),
            'types' => ReportType::options(),
            'counts' => [
                'open' => Report::where('status', ReportStatus::Open->value)->count(),
                'with_position' => Report::where('status', ReportStatus::Open->value)->whereNotNull('suggested_x')->count(),
            ],
        ]);
    }

    public function update(Request $request, Report $report, ResolveReport $resolve): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(ReportStatus::class)],
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $resolve->handle($report, ReportStatus::from($validated['status']), $request->user(), $validated['resolution_note'] ?? null);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Feedback updated.']);

        return back();
    }

    /**
     * Move the reported marker to the position the player suggested and
     * resolve the feedback in one step.
     */
    public function applySuggestion(Request $request, Report $report, ResolveReport $resolve, ConfidenceCalculator $confidence): RedirectResponse
    {
        $marker = $report->reportable;
        abort_unless($marker instanceof Marker && $report->hasSuggestedPosition(), 422, 'This feedback has no marker position to apply.');

        $marker->update([
            'x' => $report->suggested_x,
            'y' => $report->suggested_y,
            'updated_by' => $request->user()?->id,
        ]);
        $confidence->refresh($marker);

        $resolve->handle($report, ReportStatus::Accepted, $request->user(), $request->string('resolution_note')->toString() ?: 'Applied the suggested position.');

        Inertia::flash('toast', ['type' => 'success', 'message' => "Moved “{$marker->name}” and resolved the feedback."]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Report $report): array
    {
        return [
            'id' => $report->id,
            'type' => $report->type->value,
            'type_label' => $report->type->label(),
            'status' => $report->status->value,
            'message' => $report->message,
            'context' => $report->context,
            'page_url' => $report->page_url,
            'reporter' => [
                'name' => $report->user->name ?? null,
                // Email is only ever shown to moderators, for replying.
                'email' => $report->user->email ?? $report->email,
                'registered' => $report->user !== null,
            ],
            'resolver' => $report->resolver?->name,
            'resolution_note' => $report->resolution_note,
            'resolved_at' => $report->resolved_at?->toIso8601String(),
            'created_at' => $report->created_at->toIso8601String(),
            'subject' => $this->subject($report),
            'suggestion' => $report->hasSuggestedPosition() ? [
                'x' => $report->suggested_x,
                'y' => $report->suggested_y,
                'map' => $report->map ? ['slug' => $report->map->slug, 'name' => $report->map->name] : null,
                'can_apply' => $report->reportable instanceof Marker,
                'current' => $report->reportable instanceof Marker && $report->reportable->isPlaced()
                    ? ['x' => $report->reportable->x, 'y' => $report->reportable->y]
                    : null,
            ] : null,
        ];
    }

    /**
     * @return array{kind: string, name: string|null, detail: string|null, edit_url: string|null, public_url: string|null}|null
     */
    private function subject(Report $report): ?array
    {
        if ($report->reportable_type === null) {
            return null;
        }

        $subject = $report->reportable;

        return match (true) {
            $subject instanceof Marker => [
                'kind' => 'Marker',
                'name' => $subject->name,
                'detail' => $subject->map->name.($subject->variant ? " · {$subject->variant}" : ''),
                'edit_url' => route('admin.maps.editor', ['map' => $subject->map->slug, 'marker' => $subject->id]),
                'public_url' => route('maps.show', ['map' => $subject->map->slug, 'marker' => $subject->id]),
            ],
            $subject instanceof Item => [
                'kind' => 'Item',
                'name' => $subject->name,
                'detail' => null,
                'edit_url' => route('admin.items.edit', $subject),
                'public_url' => route('items.show', $subject),
            ],
            $subject instanceof Objective => [
                'kind' => 'Objective',
                'name' => $subject->name,
                'detail' => null,
                'edit_url' => route('admin.objectives.edit', $subject),
                'public_url' => route('objectives.show', $subject),
            ],
            $subject instanceof Map => [
                'kind' => 'Map',
                'name' => $subject->name,
                'detail' => null,
                'edit_url' => route('admin.maps.edit', $subject),
                'public_url' => route('maps.show', $subject),
            ],
            default => ['kind' => 'Deleted', 'name' => null, 'detail' => null, 'edit_url' => null, 'public_url' => null],
        };
    }
}
