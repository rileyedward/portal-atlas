<?php

namespace App\Http\Middleware;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'can' => [
                    'manageContent' => (bool) $request->user()?->canManageContent(),
                    'administer' => (bool) $request->user()?->isAdmin(),
                ],
            ],
            // Shared so any page can open the feedback form without extra props.
            'feedbackTypes' => ReportType::feedbackOptions(),
            'openFeedbackCount' => fn () => $request->user()?->canManageContent()
                ? Report::query()->where('status', ReportStatus::Open->value)->count()
                : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
