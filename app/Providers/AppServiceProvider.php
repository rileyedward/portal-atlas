<?php

namespace App\Providers;

use App\Models\Item;
use App\Models\Map;
use App\Models\Marker;
use App\Models\Objective;
use App\Models\User;
use App\Support\PublicCache;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(PublicCache::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureRateLimiting();

        Relation::morphMap([
            'map' => Map::class,
            'marker' => Marker::class,
            'item' => Item::class,
            'objective' => Objective::class,
        ]);
    }

    protected function configureAuthorization(): void
    {
        Gate::define('manage-content', fn (User $user): bool => $user->canManageContent());
        Gate::define('administer', fn (User $user): bool => $user->isAdmin());
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('reports', fn (Request $request) => [
            Limit::perMinute(3)->by('reports:'.($request->user()->id ?? $request->ip())),
            Limit::perDay(30)->by('reports-daily:'.($request->user()->id ?? $request->ip())),
        ]);

        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(120)->by('search:'.$request->ip()));

        RateLimiter::for('player-writes', fn (Request $request) => Limit::perMinute(60)->by('player:'.($request->user()->id ?? $request->ip())));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
