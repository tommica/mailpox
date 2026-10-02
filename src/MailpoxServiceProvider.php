<?php

namespace Tommica\Mailpox;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Mail\MailManager;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Tommica\Mailpox\Console\PruneMailpoxCommand;
use Tommica\Mailpox\Contracts\MessageStore;
use Tommica\Mailpox\Http\Middleware\EnsureMailpoxIsLocal;
use Tommica\Mailpox\Storage\CacheMessageStore;
use Tommica\Mailpox\Storage\DatabaseMessageStore;
use Tommica\Mailpox\Storage\FilesystemMessageStore;
use Tommica\Mailpox\Support\MessageHydrator;
use Tommica\Mailpox\Support\MessageSerializer;
use Tommica\Mailpox\Transport\MailpoxTransport;

final class MailpoxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mailpox.php', 'mailpox');

        if ($this->app['config']->get('mail.mailers.mailpox') === null) {
            $this->app['config']->set('mail.mailers.mailpox', ['transport' => 'mailpox']);
        }

        $this->app->singleton(MessageHydrator::class);
        $this->app->singleton(MessageSerializer::class);
        $this->app->singleton(MessageStore::class, fn (Application $app): MessageStore => $this->createStore($app));
    }

    public function boot(MailManager $mail, Router $router): void
    {
        $mail->extend('mailpox', fn (): MailpoxTransport => new MailpoxTransport(
            $this->app->make(MessageStore::class),
            $this->app->make(MessageSerializer::class),
        ));

        $router->aliasMiddleware('mailpox.local', EnsureMailpoxIsLocal::class);
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mailpox');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if (config('mailpox.enabled')) {
            $router->group([
                'prefix' => trim((string) config('mailpox.path', '/mailpox'), '/'),
                'middleware' => config('mailpox.middleware', ['web', 'mailpox.local']),
            ], fn () => $this->loadRoutesFrom(__DIR__.'/../routes/web.php'));
        }

        if ($this->app->runningInConsole()) {
            $this->commands([PruneMailpoxCommand::class]);
            $this->publishes([__DIR__.'/../config/mailpox.php' => config_path('mailpox.php')], 'mailpox-config');
            $this->publishes([__DIR__.'/../database/migrations' => database_path('migrations')], 'mailpox-migrations');
            $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/mailpox')], 'mailpox-views');
        }
    }

    private function createStore(Application $app): MessageStore
    {
        $driver = config('mailpox.driver', 'database');

        return match ($driver) {
            'database' => new DatabaseMessageStore(
                $app->make(DatabaseManager::class)->connection(config('mailpox.database.connection')),
                $app->make(MessageHydrator::class),
            ),
            'filesystem' => new FilesystemMessageStore(
                $app->make(FilesystemManager::class)->disk(config('mailpox.filesystem.disk')),
                $app->make(MessageHydrator::class),
                (string) config('mailpox.filesystem.directory'),
            ),
            'cache' => new CacheMessageStore(
                $app->make(CacheManager::class)->store(config('mailpox.cache.store')),
                $app->make(MessageHydrator::class),
                (string) config('mailpox.cache.prefix'),
                (int) config('mailpox.cache.ttl'),
                (int) config('mailpox.cache.limit'),
            ),
            default => throw new InvalidArgumentException("Unsupported Mailpox driver [{$driver}]."),
        };
    }
}
