<?php

use App\Enums\ReportStatus;
use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use App\Models\Report;
use App\Models\Source;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests can send general feedback with an optional reply email', function () {
    $this->postJson(route('api.reports.store'), [
        'type' => 'bug',
        'message' => 'The search box <b>freezes</b> on mobile.',
        'email' => 'player@example.com',
        'page_url' => 'https://evil.example.com/maps/factory?marker=3',
        'context' => 'Search',
    ])->assertCreated();

    $report = Report::sole();
    expect($report->reportable_type)->toBeNull()
        ->and($report->message)->toBe('The search box freezes on mobile.')
        ->and($report->email)->toBe('player@example.com')
        // Only the path is kept, never another host.
        ->and($report->page_url)->toBe('/maps/factory?marker=3')
        ->and($report->context)->toBe('Search')
        ->and($report->toArray())->not->toHaveKey('email');
});

test('general feedback needs a message', function () {
    $this->postJson(route('api.reports.store'), ['type' => 'suggestion'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');
});

test('the same general feedback cannot be sent twice while open', function () {
    $payload = ['type' => 'suggestion', 'message' => 'Please add a dark mode toggle.'];

    $this->postJson(route('api.reports.store'), $payload)->assertCreated();
    $this->postJson(route('api.reports.store'), $payload)->assertUnprocessable();
});

test('signed in players are not asked for an email', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('api.reports.store'), [
        'type' => 'bug', 'message' => 'Something broke here.', 'email' => 'other@example.com',
    ])->assertCreated();

    expect(Report::sole()->user_id)->toBe($user->id)->and(Report::sole()->email)->toBeNull();
});

test('feedback can be about a map', function () {
    $map = Map::factory()->create();

    $this->postJson(route('api.reports.store'), [
        'subject_type' => 'map', 'subject_id' => $map->id, 'type' => 'outdated', 'context' => 'Field notes',
    ])->assertCreated();

    expect(Report::sole()->reportable->is($map))->toBeTrue()->and(Report::sole()->map_id)->toBe($map->id);
});

test('players can suggest a marker position and admins can apply it', function () {
    $marker = Marker::factory()->create(['x' => null, 'y' => null]);

    $this->postJson(route('api.reports.store'), [
        'subject_type' => 'marker', 'subject_id' => $marker->id, 'type' => 'incorrect_location',
        'suggested_x' => 42.123456, 'suggested_y' => 17.5,
    ])->assertCreated();

    $report = Report::sole();
    expect($report->suggested_x)->toBe(42.1235)->and($report->map_id)->toBe($marker->map_id);

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get(route('admin.reports.index', ['kind' => 'position']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports/Index')
            ->where('reports.data.0.suggestion.can_apply', true)
            ->where('reports.data.0.suggestion.current', null)
            ->where('counts.with_position', 1));

    $this->actingAs($admin)->post(route('admin.reports.apply-suggestion', $report))->assertRedirect();

    expect($marker->refresh()->x)->toBe(42.1235)
        ->and($marker->y)->toBe(17.5)
        ->and($marker->updated_by)->toBe($admin->id)
        ->and($report->refresh()->status)->toBe(ReportStatus::Accepted)
        ->and($marker->open_reports_count)->toBe(0);
});

test('a suggested position cannot be applied to feedback without a marker', function () {
    $report = Report::factory()->create(['reportable_type' => null, 'reportable_id' => null, 'suggested_x' => 10, 'suggested_y' => 10]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.reports.apply-suggestion', $report))
        ->assertStatus(422);
});

test('players cannot use the feedback inbox', function () {
    $report = Report::factory()->create();

    $this->actingAs(User::factory()->create())->get(route('admin.reports.index'))->assertForbidden();
    $this->actingAs(User::factory()->create())->post(route('admin.reports.apply-suggestion', $report))->assertForbidden();
});

test('the inbox filters general feedback and managers see the open count', function () {
    Report::factory()->create();
    Report::factory()->create(['reportable_type' => null, 'reportable_id' => null, 'message' => 'Love the site']);
    $editor = User::factory()->editor()->create();

    // Guests never see the moderation count.
    $this->get('/')->assertInertia(fn (Assert $page) => $page->where('openFeedbackCount', null)->has('feedbackTypes'));

    $this->actingAs($editor)->get(route('admin.reports.index', ['kind' => 'general']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('reports.data', 1)
            ->where('reports.data.0.subject', null)
            ->where('openFeedbackCount', 2));
});

test('players never receive data sources, editors do', function () {
    $source = Source::factory()->create(['name' => 'Secret Source', 'url' => 'https://secret.example.com']);
    $map = Map::factory()->create([
        'image_attribution' => 'Imagery: Secret Source',
        'metadata' => ['region' => 'North', 'facts' => [['text' => 'A fact', 'source_url' => 'https://secret.example.com/fact']], 'calibration' => ['source' => 'Secret Source']],
    ]);
    $marker = Marker::factory()->for($map)->create([
        'source_id' => $source->id,
        'source_note' => 'quoted from Secret Source',
        'metadata' => ['conditions' => 'Needs a key', 'game_coords' => [1, 2, 3], 'site_type' => 't2_box'],
    ]);
    $item = Item::factory()->create(['source_id' => $source->id, 'metadata' => ['tags' => ['Official listing'], 'chronotraces' => ['idea' => 2]]]);

    $public = $this->getJson(route('api.markers.show', $marker))->assertOk()->json('data');
    expect($public)->not->toHaveKeys(['source', 'source_note'])
        ->and($public['metadata'])->toBe(['conditions' => 'Needs a key']);

    $this->get(route('maps.show', $map))->assertInertia(fn (Assert $page) => $page
        ->where('map.image_attribution', null)
        ->where('map.source_url', null)
        ->missing('map.metadata.calibration')
        ->missing('map.metadata.facts.0.source_url')
        ->where('map.metadata.facts.0.text', 'A fact'));

    $this->get(route('items.show', $item))->assertInertia(fn (Assert $page) => $page
        ->missing('item.source')
        ->missing('item.metadata.tags')
        ->where('item.metadata.chronotraces.idea', 2));

    $this->get(route('items.show', $item))->assertDontSee('Secret Source');
    $this->get(route('maps.show', $map))->assertDontSee('Secret Source');

    $editor = User::factory()->editor()->create();
    $this->actingAs($editor)->getJson(route('api.markers.show', $marker))
        ->assertJsonPath('data.source.name', 'Secret Source')
        ->assertJsonPath('data.metadata.site_type', 't2_box');
});
