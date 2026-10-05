<?php

namespace Tommica\Mailpox\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Tommica\Mailpox\Contracts\MessageStore;
use Tommica\Mailpox\Tests\TestCase;

final class MailpoxTest extends TestCase
{
    public function test_it_registers_the_mailer_without_host_configuration(): void
    {
        $this->assertSame(['transport' => 'mailpox'], config('mail.mailers.mailpox'));

        Mail::mailer('mailpox')->raw('Automatic configuration', fn ($message) => $message->from('a@example.com')->to('b@example.com')->subject('Automatic'));

        $this->assertSame(1, $this->app->make(MessageStore::class)->paginate()->total());
    }

    public function test_it_captures_and_displays_an_email_with_an_attachment(): void
    {
        Mail::mailer('mailpox')->send(
            ['html' => fn (): HtmlString => new HtmlString('<h1>Hello</h1>'), 'raw' => 'Plain body'],
            [],
            function ($message): void {
                $message->from('sender@example.com')
                    ->to('recipient@example.com')
                    ->subject('Captured message')
                    ->attachData('file contents', 'report.txt', ['mime' => 'text/plain']);
            },
        );

        $message = $this->app->make(MessageStore::class)->paginate()->items()[0];

        $this->get('/mailpox')->assertOk()->assertSee('Captured message');
        $this->get('/mailpox/'.$message->id)->assertOk()->assertSee('report.txt')->assertSee('Plain body');
        $this->get('/mailpox/'.$message->id.'/html')->assertOk()->assertSee('<h1>Hello</h1>', false);
    }

    public function test_polling_reports_mailbox_state(): void
    {
        Mail::mailer('mailpox')->raw('Body', fn ($message) => $message->from('a@example.com')->to('b@example.com')->subject('Polling'));
        $message = $this->app->make(MessageStore::class)->paginate()->items()[0];

        $this->getJson('/mailpox/poll')->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'Polling') && str_contains($html, $message->id));
    }

    public function test_it_deletes_one_or_all_messages(): void
    {
        foreach (['One', 'Two'] as $subject) {
            Mail::mailer('mailpox')->raw('Body', function ($message) use ($subject): void {
                $message->from('a@example.com')->to('b@example.com')->subject($subject);
            });
        }

        $store = $this->app->make(MessageStore::class);
        $id = $store->paginate()->items()[0]->id;
        $this->delete('/mailpox/'.$id)->assertRedirect('/mailpox');
        $this->assertSame(1, $store->paginate()->total());
        $this->delete('/mailpox')->assertRedirect('/mailpox');
        $this->assertSame(0, $store->paginate()->total());
    }

    public function test_mailbox_is_hidden_outside_local_environments(): void
    {
        $this->app['env'] = 'production';

        $this->get('/mailpox')->assertNotFound();
    }
}
