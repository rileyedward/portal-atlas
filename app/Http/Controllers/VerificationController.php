<?php

namespace App\Http\Controllers;

use App\Actions\Community\ConfirmAccuracy;
use App\Models\Marker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VerificationController extends Controller
{
    public function store(Request $request, Marker $marker, ConfirmAccuracy $confirm): JsonResponse
    {
        Gate::authorize('view', $marker);

        $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $confirm->handle($marker, $request->user(), $request->input('note'));

        return response()->json([
            'message' => 'Thanks for confirming!',
            'confidence' => $marker->refresh()->effectiveConfidence(),
            'confirmations' => $marker->confirmations_count,
        ]);
    }
}
