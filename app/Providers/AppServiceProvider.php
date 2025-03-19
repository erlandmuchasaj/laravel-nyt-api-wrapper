<?php

namespace App\Providers;

use App\Services\NYT\BestSellerCache;
use App\Services\NYT\Contracts\CacheInterface;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->isLocal()) {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Debugbar', \Barryvdh\Debugbar\Facades\Debugbar::class);
        }

        if ($this->app->isLocal() && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }

        $this->app->bind(CacheInterface::class, BestSellerCache::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Register a macro for Carbon timezone for users
        Carbon::macro('inAppTimezone', function () {
            /** @var \Illuminate\Support\Carbon $this */
            // @phpstan-ignore-next-line
            return $this->tz(config()->string('app.timezone_display'));
        });

        Carbon::macro('inUserTimezone', function () {
            /** @var \Illuminate\Support\Carbon $this */
            // @phpstan-ignore-next-line
            return $this->tz(auth()->user()->timezone ?? config()->string('app.timezone_display'));
        });

        // when we go live we might want to force SSL
        // on the requests and responses
        if ($this->app->isProduction() && config()->boolean('app.ssl')) {
            URL::useOrigin(Str::of(config()->string('app.url'))->ltrim('/'));
            URL::forceHttps($this->app->isProduction());
        }

        // prevent user to send accidental emails in production
        // and if they are disabled.
        if (! $this->app->isProduction()) {
            Mail::alwaysTo(config()->string('app.fallback_email'), config()->string('app.name'));
        }

        // Handle SQL Error schema migrate
        if (PHP_OS === 'WINNT' && ! $this->app->isProduction()) {
            // MySQL 8.0 limits index keys to 1000 characters
            Schema::defaultStringLength(125); // For MYSQL 8.0
        }

        // Prevents 'migrate:fresh', 'migrate:refresh', 'migrate:reset',
        // 'migrate:rollback', and 'db:wipe' Laravel >= 11
        DB::prohibitDestructiveCommands(
            $this->app->isProduction(),
        );

        // Log all SQL queries
        if (! $this->app->isProduction() && config()->boolean('app.db_log')) {
            DB::listen(function (QueryExecuted $query) {

                if ($query->time > 5000) {
                    Log::warning('An individual database query exceeded 5 second.', [
                        'sql' => $query->sql,
                    ]);
                }

                /**
                 * @var array<int, string> $bindings
                 */
                $bindings = $query->bindings;
                logger(Str::replaceArray('?', $bindings, $query->sql));
            });
        }

        // Remove 'data' from json api responses
        JsonResource::withoutWrapping();

        // Find N+1 problems instantly by disabling lazy loading
        Model::shouldBeStrict(! $this->app->isProduction());

        // this is helpful when you are working with dates,
        // and you are changing them for example:
        // $date = now();
        // $future = $date->addHours(2);
        // -> if you dont have the below activated both dates will be the same.
        Date::use(CarbonImmutable::class);

        // But in production, log the violation instead of throwing an exception.
        if ($this->app->isProduction()) {
            Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
                $class = $model::class;
                info("Attempted to lazy load [{$relation}] on model [{$class}].");
            });
        }

        // Log a warning if we spend more than a total of 5000ms querying.
        DB::whenQueryingForLongerThan(5000, function (Connection $connection) {
            Log::warning("Attempted to lazy load [{$connection->getName()}].");
        });

        if (! $this->app->isProduction()) {
            if ($this->app->runningInConsole()) {
                // Log slow commands.
                $this->app->make(ConsoleKernel::class)->whenCommandLifecycleIsLongerThan(
                    5000,
                    function ($startedAt, $input, $status) {
                        Log::warning('A command took longer than 5 seconds.', [
                            'startedAt' => $startedAt,
                            'input' => $input,
                            'status' => $status,
                        ]);
                    },
                );
            } else {
                // Log slow requests.
                $this->app->make(HttpKernel::class)->whenRequestLifecycleIsLongerThan(
                    5000,
                    function ($startedAt, $request) {
                        Log::warning('A request took longer than 5 seconds.', [
                            'startedAt' => $startedAt,
                            'request' => $request,
                        ]);
                    },
                );
            }
        }
    }
}
