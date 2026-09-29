<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class AnalyticsEventController extends Controller
{
    /**
     * Client-side events that the server cannot observe (e.g. filter toggles).
     */
    public function __invoke(Request $request, Analytics $analytics): Response
    {
        $validated = $request->validate([
            'name' => ['required', Rule::in(['filter_toggle'])],
            'subject_type' => ['nullable', 'string', 'max:40'],
            'subject_id' => ['nullable', 'integer'],
        ]);

        $analytics->record($validated['name'], $validated['subject_type'] ?? null, $validated['subject_id'] ?? null);

        return response()->noContent();
    }
}
