<?php

use App\Enums\ReportStatus;
use App\Models\Marker;
use App\Models\Report;
use App\Models\User;

test('anonymous players can report a marker and confidence drops', function () {
    $marker = Marker::factory()->create();
    $before = $marker->confidence;

    $this->postJson(route('api.reports.store'), [
        'subject_type' => 'marker',
        'subject_id' => $marker->id,
        'type' => 'incorrect_location',
        'message' => 'It is <b>north</b> of here',
    ])->assertCreated();

    $report = Report::sole();
    expect($report->status)->toBe(ReportStatus::Open)
        ->and($report->message)->toBe('It is north of here')
        ->and($report->reporter_fingerprint)->not->toBeNull()
        ->and($report->reporter_fingerprint)->not->toContain('127.0.0.1')
        ->and($marker->refresh()->open_reports_count)->toBe(1)
        ->and($marker->confidence)->toBeLessThanOrEqual($before);
});

test('duplicate open reports from the same reporter are rejected', function () {
    $marker = Marker::factory()->create();
    $payload = ['subject_type' => 'marker', 'subject_id' => $marker->id, 'type' => 'duplicate'];

    $this->postJson(route('api.reports.store'), $payload)->assertCreated();
    $this->postJson(route('api.reports.store'), $payload)->assertUnprocessable();
});

test('honeypot field blocks bots', function () {
    $marker = Marker::factory()->create();

    $this->postJson(route('api.reports.store'), [
        'subject_type' => 'marker', 'subject_id' => $marker->id, 'type' => 'other', 'website' => 'spam',
    ])->assertUnprocessable();
});

test('reports cannot target unpublished content', function () {
    $marker = Marker::factory()->draft()->create();

    $this->postJson(route('api.reports.store'), [
        'subject_type' => 'marker', 'subject_id' => $marker->id, 'type' => 'other',
    ])->assertNotFound();
});

test('signed in players can confirm a marker', function () {
    $marker = Marker::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('api.markers.confirm', $marker))->assertOk()->assertJsonPath('confirmations', 1);
    // Confirming twice for the same version does not double count.
    $this->actingAs($user)->postJson(route('api.markers.confirm', $marker))->assertOk()->assertJsonPath('confirmations', 1);
});

test('confirming requires an account', function () {
    $marker = Marker::factory()->create();

    $this->postJson(route('api.markers.confirm', $marker))->assertUnauthorized();
});
