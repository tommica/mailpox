<?php

namespace Tommica\Mailpox\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tommica\Mailpox\Contracts\MessageStore;
use Tommica\Mailpox\Storage\CacheMessageStore;
use Tommica\Mailpox\Storage\FilesystemMessageStore;
use Tommica\Mailpox\Tests\TestCase;

final class StorageDriversTest extends TestCase
{
    public function test_filesystem_driver_round_trips_and_deletes_messages(): void
    {
        config(['mailpox.driver' => 'filesystem', 'mailpox.filesystem.disk' => 'local', 'mailpox.filesystem.directory' => 'mailpox-tests']);
        $this->app->forgetInstance(MessageStore::class);

        Mail::mailer('mailpox')->raw('Filesystem body', fn ($message) => $message->from('a@example.com')->to('b@example.com')->subject('Filesystem'));
        $store = $this->app->make(MessageStore::class);
        $message = $store->paginate()->items()[0];

        $this->assertInstanceOf(FilesystemMessageStore::class, $store);
        $this->assertSame('Filesystem', $store->find($message['id'])->subject);
        $this->assertTrue($store->delete($message['id']));
    }

    public function test_cache_driver_is_bounded_and_round_trips_messages(): void
    {
        config(['mailpox.driver' => 'cache', 'mailpox.cache.store' => 'array', 'mailpox.cache.limit' => 1]);
        $this->app->forgetInstance(MessageStore::class);

        foreach (['First', 'Second'] as $subject) {
            Mail::mailer('mailpox')->raw('Cache body', fn ($message) => $message->from('a@example.com')->to('b@example.com')->subject($subject));
        }
        $store = $this->app->make(MessageStore::class);

        $this->assertInstanceOf(CacheMessageStore::class, $store);
        $this->assertSame(1, $store->paginate()->total());
        $this->assertSame('Second', $store->paginate()->items()[0]['subject']);
    }
}
