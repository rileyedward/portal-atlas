<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use stdClass;

/**
 * Caches the shared (non-personal) data behind public pages. Keys carry a
 * version prefix so one flush() invalidates everything at once; this works on
 * the database store (no tags) and after raw-insert imports that skip model
 * events. Call flush() after any write that changes public content.
 *
 * Bound as a scoped instance so the version is read once per request.
 */
class PublicCache
{
    private const VERSION_KEY = 'public:version';

    private ?string $version = null;

    /**
     * Values are stored as JSON (the cache refuses to unserialize objects), so
     * they come back exactly as the page would render them. A top-level map is
     * returned as an array so it can be spread into props; nested empty
     * objects stay objects.
     *
     * @param  Closure(): mixed  $callback
     */
    public function remember(string $key, Closure $callback): mixed
    {
        $json = Cache::remember(
            'public:'.$this->version().':'.$key,
            (int) config('cache.public_ttl', 86400),
            fn () => json_encode($callback(), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION),
        );

        $value = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        return $value instanceof stdClass ? (array) $value : $value;
    }

    public function flush(): void
    {
        $this->version = (string) Str::ulid();

        Cache::forever(self::VERSION_KEY, $this->version);
    }

    private function version(): string
    {
        return $this->version ??= (string) Cache::rememberForever(self::VERSION_KEY, fn () => (string) Str::ulid());
    }
}
