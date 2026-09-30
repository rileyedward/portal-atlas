<?php

use App\Models\PageView;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function pageView(string $visitor, string $path, int $daysAgo = 0, ?string $referrer = null): void
{
    PageView::create([
        'visitor_hash' => str_pad($visitor, 64, '0'),
        'path' => $path,
        'referrer_host' => $referrer,
        'device' => 'desktop',
    ])->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
}

test('admins see visitor and page view totals', function () {
    pageView('a', '/');
    pageView('a', '/items');
    pageView('b', '/', referrer: 'reddit.com');
    pageView('c', '/', daysAgo: 10);
    pageView('d', '/', daysAgo: 45);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.analytics.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/analytics/Index')
            ->where('range', 30)
            ->where('summary.visitors', 3)
            ->where('summary.pageviews', 4)
            ->where('summary.visitors_today', 2)
            ->where('summary.change.visitors', 200)
            ->has('daily', 30)
            ->where('daily.29.visitors', 2)
            ->where('daily.29.pageviews', 3)
            ->where('top_pages.0.path', '/')
            ->where('top_pages.0.views', 3)
            ->where('referrers.0.host', 'Direct / unknown')
            ->where('referrers.1.host', 'reddit.com'));
});

test('the range filter narrows the window', function () {
    pageView('a', '/');
    pageView('b', '/', daysAgo: 10);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.analytics.index', ['range' => 7]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('range', 7)
            ->has('daily', 7)
            ->where('summary.visitors', 1));
});

test('players cannot view analytics', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.analytics.index'))->assertForbidden();
});
