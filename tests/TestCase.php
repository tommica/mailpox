<?php

namespace Tommica\Mailpox\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Tommica\Mailpox\MailpoxServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [MailpoxServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $app['config']->set('mailpox.database.connection', 'testing');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--database' => 'testing', '--force' => true])->run();
    }
}
