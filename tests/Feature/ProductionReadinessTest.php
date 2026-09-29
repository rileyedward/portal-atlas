<?php

use App\Enums\UserRole;
use App\Models\Map;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('make-admin promotes an existing user and verifies their email', function () {
    $user = User::factory()->unverified()->create(['email' => 'owner@example.com']);

    $this->artisan('app:make-admin', ['email' => 'owner@example.com'])->assertSuccessful();

    expect($user->refresh()->role)->toBe(UserRole::Admin)->and($user->email_verified_at)->not->toBeNull();
});

test('make-admin can grant the editor role and rejects unknown users', function () {
    User::factory()->create(['email' => 'editor@example.com']);

    $this->artisan('app:make-admin', ['email' => 'editor@example.com', '--role' => 'editor'])->assertSuccessful();
    $this->artisan('app:make-admin', ['email' => 'nobody@example.com'])->assertFailed();
    $this->artisan('app:make-admin', ['email' => 'editor@example.com', '--role' => 'player'])->assertFailed();

    expect(User::where('email', 'editor@example.com')->sole()->role)->toBe(UserRole::Editor);
});

test('responses carry security headers', function () {
    $this->get('/')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

test('production renders branded error pages but keeps json errors for the api', function () {
    app()->instance('env', 'production');
    app()->detectEnvironment(fn () => 'production');

    $this->get('/maps/does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('Error')->where('status', 404));

    $this->getJson('/api/markers/999999')->assertNotFound()->assertJsonStructure(['message']);
});

test('bundled map images are served as static assets, uploads from the media disk', function () {
    config(['filesystems.media' => 'public']);

    expect(Map::factory()->make(['image_path' => 'map-images/factory.webp'])->imageUrl())->toBe(asset('map-images/factory.webp'))
        ->and(Map::factory()->make(['image_path' => 'maps/upload.webp'])->imageUrl())->toEndWith('/storage/maps/upload.webp')
        ->and(Map::factory()->make(['image_path' => null])->imageUrl())->toBeNull();
});

test('every bundled map image referenced by the manifest exists in public/', function () {
    $manifest = json_decode((string) file_get_contents(database_path('data/activematterhelp/map-images.json')), true);

    foreach ($manifest as $entry) {
        expect(public_path($entry['path']))->toBeFile();
    }
});
