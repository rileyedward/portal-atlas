<?php

namespace App\Http\Controllers;

use App\Actions\Community\SubmitReport;
use App\Enums\ReportType;
use App\Http\Requests\StoreReportRequest;
use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use App\Models\Objective;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function store(StoreReportRequest $request, SubmitReport $submit): JsonResponse
    {
        $subject = match ($request->input('subject_type')) {
            'marker' => Marker::query()->published()->findOrFail($request->integer('subject_id')),
            'item' => Item::query()->published()->findOrFail($request->integer('subject_id')),
            'objective' => Objective::query()->published()->findOrFail($request->integer('subject_id')),
            'map' => Map::query()->published()->findOrFail($request->integer('subject_id')),
            default => null,
        };

        if ($subject instanceof Marker) {
            Gate::authorize('view', $subject);
        }

        $submit->handle(
            $subject,
            ReportType::from($request->string('type')->toString()),
            $request->input('message'),
            $request->user(),
            (string) $request->ip(),
            [
                'email' => $request->input('email'),
                'page_url' => $request->pagePath(),
                'context' => $request->input('context'),
                'suggested_x' => $request->filled('suggested_x') ? round((float) $request->input('suggested_x'), 4) : null,
                'suggested_y' => $request->filled('suggested_y') ? round((float) $request->input('suggested_y'), 4) : null,
            ],
        );

        return response()->json(['message' => 'Thanks! Your feedback was sent to the moderators.'], 201);
    }
}
