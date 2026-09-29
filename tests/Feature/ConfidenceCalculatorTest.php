<?php

use App\Enums\ReportStatus;
use App\Models\GameVersion;
use App\Models\Marker;
use App\Models\Report;
use App\Models\Source;
use App\Models\User;
use App\Services\ConfidenceCalculator;

test('unsourced, never verified data is unverified', function () {
    $marker = Marker::factory()->create();

    $breakdown = app(ConfidenceCalculator::class)->breakdown($marker);

    expect($breakdown->score)->toBe(ConfidenceCalculator::UNSOURCED_BASE - ConfidenceCalculator::NEVER_VERIFIED_PENALTY)
        ->and($breakdown->label())->toBe('Unverified');
});

test('official source verified on the current version by an admin is high confidence', function () {
    $version = GameVersion::factory()->current()->create();
    $marker = Marker::factory()->create([
        'source_id' => Source::factory()->create(['reliability' => 90])->id,
        'verified_version_id' => $version->id,
        'last_verified_at' => now(),
    ]);
    $marker->verifications()->create(['user_id' => User::factory()->admin()->create()->id, 'game_version_id' => $version->id, 'is_admin' => true]);

    $breakdown = app(ConfidenceCalculator::class)->breakdown($marker);

    expect($breakdown->score)->toBe(100)->and($breakdown->label())->toBe('High');
});

test('open reports and outdated versions reduce confidence', function () {
    $old = GameVersion::factory()->create();
    GameVersion::factory()->current()->create();
    $marker = Marker::factory()->create([
        'source_id' => Source::factory()->create(['reliability' => 60])->id,
        'verified_version_id' => $old->id,
    ]);
    Report::factory()->count(2)->create(['reportable_id' => $marker->id, 'status' => ReportStatus::Open]);

    $breakdown = app(ConfidenceCalculator::class)->breakdown($marker);

    expect($breakdown->factors)->toMatchArray([
        'source' => 60,
        'open_reports' => -2 * ConfidenceCalculator::PER_OPEN_REPORT,
        'outdated_version' => -ConfidenceCalculator::OUTDATED_VERSION_PENALTY,
    ])->and($breakdown->score)->toBe(60 - 24 - 15);
});

test('admin override wins but the computed score is kept internally', function () {
    $marker = Marker::factory()->create(['confidence_override' => 90]);

    $breakdown = app(ConfidenceCalculator::class)->refresh($marker);

    expect($breakdown->score)->toBe(90)
        ->and($breakdown->overridden)->toBeTrue()
        ->and($marker->refresh()->confidence)->toBe(ConfidenceCalculator::UNSOURCED_BASE - ConfidenceCalculator::NEVER_VERIFIED_PENALTY)
        ->and($marker->effectiveConfidence())->toBe(90);
});

test('marking a new build current makes previously verified data stale', function () {
    $old = GameVersion::factory()->current()->create();
    $marker = Marker::factory()->create(['verified_version_id' => $old->id, 'source_id' => Source::factory()->create(['reliability' => 80])->id]);
    app(ConfidenceCalculator::class)->refresh($marker);
    expect($marker->refresh()->confidence)->toBe(80);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.versions.store'), ['version' => '9.9.9', 'is_current' => true])
        ->assertRedirect();

    expect($marker->refresh()->confidence)->toBe(80 - ConfidenceCalculator::OUTDATED_VERSION_PENALTY);
});
