<?php

use App\Models\Map;
use App\Models\PageView;
use App\Models\User;

const BROWSER = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 Chrome/128.0 Safari/537.36';

beforeEach(function () {
    $this->withHeader('User-Agent', BROWSER);
});

test('guest page views are recorded without storing the ip', function () {
    $map = Map::factory()->create();

    $this->get('/')->assertOk();
    $this->get(route('maps.show', $map))->assertOk();

    expect(PageView::pluck('path')->all())->toBe(['/', parse_url(route('maps.show', $map), PHP_URL_PATH)]);

    $view = PageView::first();
    expect($view->visitor_hash)->toHaveLength(64)->not->toContain('127.0.0.1')
        ->and($view->device)->toBe('desktop')
        ->and($view->referrer_host)->toBeNull();
});

test('the visitor hash is stable within a day and rotates daily', function () {
    $this->get('/');
    $this->get('/');
    $this->travel(1)->day();
    $this->get('/');

    $hashes = PageView::orderBy('id')->pluck('visitor_hash');
    expect($hashes[0])->toBe($hashes[1])->and($hashes[2])->not->toBe($hashes[0]);
});

test('external referrers are stored by host and same-site ones are dropped', function () {
    $this->withHeader('Referer', 'https://www.Reddit.com/r/games?x=1')->get('/');
    $this->withHeader('Referer', url('/items'))->get('/');

    expect(PageView::orderBy('id')->pluck('referrer_host')->all())->toBe(['reddit.com', null]);
});

test('mobile devices are classified', function () {
    $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) Mobile/15E148')->get('/');

    expect(PageView::first()->device)->toBe('mobile');
});

test('bots are not recorded', function (string $agent) {
    $this->withHeader('User-Agent', $agent)->get('/');

    expect(PageView::count())->toBe(0);
})->with(['Googlebot/2.1 (+http://www.google.com/bot.html)', 'curl/8.4.0', '']);

test('staff visits are not recorded', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create())->get('/')->assertOk();

    expect(PageView::count())->toBe(0);
})->with(['editor', 'admin']);

test('signed in players are recorded', function () {
    $this->actingAs(User::factory()->create())->get('/')->assertOk();

    expect(PageView::count())->toBe(1);
});

test('non page requests are not recorded', function () {
    $this->get('/sitemap.xml');
    $this->get('/robots.txt');
    $this->getJson('/api/search?q=test');
    $this->get('/definitely-not-a-page')->assertNotFound();
    $this->post('/api/reports');
    $this->withHeader('Purpose', 'prefetch')->get('/');

    expect(PageView::count())->toBe(0);
});

test('page views older than the retention window are pruned', function () {
    PageView::create(['visitor_hash' => str_repeat('a', 64), 'path' => '/', 'device' => 'desktop'])
        ->forceFill(['created_at' => now()->subDays(PageView::RETENTION_DAYS + 1)])->save();
    PageView::create(['visitor_hash' => str_repeat('b', 64), 'path' => '/', 'device' => 'desktop']);

    $this->artisan('model:prune', ['--model' => [PageView::class]])->assertSuccessful();

    expect(PageView::pluck('visitor_hash')->all())->toBe([str_repeat('b', 64)]);
});
