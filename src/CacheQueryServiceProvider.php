<?php

namespace Laragear\CacheQuery;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;

/**
 * @internal
 */
class CacheQueryServiceProvider extends ServiceProvider
{
    public const CONFIG = __DIR__.'/../config/cache-query.php';
    public const STUBS = __DIR__.'/../.stubs/stubs.php';

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(static::CONFIG, 'cache-query');
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if (! Builder::hasMacro('cache')) {
            Builder::macro('cache', $this->macro());
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([static::CONFIG => $this->app->configPath('cache-query.php')], 'config');
            $this->publishes([static::STUBS => $this->app->basePath('.stubs/cache-query.php')], 'phpstorm');

            $this->commands([
                Console\Commands\CacheQuery\Forget::class,
            ]);
        }
    }

    /**
     * Creates a macro for the query builders.
     *
     * @return \Closure
     */
    protected function macro(): Closure
    {
        return function (
            int|DateTimeInterface|DateInterval $ttl = 60,
            string $key = '',
            string $store = null,
            int $wait = 0,
        ): Builder {
            /** @var \Illuminate\Database\Query\Builder $this */
            if ($this->connection instanceof CacheAwareConnectionProxy) {
                $this->connection = $this->connection->connection;
            }

            $this->connection = CacheAwareConnectionProxy::crateNewInstance($this->connection, $ttl, $key, $wait, $store);

            return $this;
        };
    }
}
