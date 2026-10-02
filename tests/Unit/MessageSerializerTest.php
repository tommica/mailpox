<?php

namespace Tommica\Mailpox\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;
use Tommica\Mailpox\Support\MessageSerializer;

final class MessageSerializerTest extends TestCase
{
    public function test_it_preserves_mime_content_and_attachment_bytes(): void
    {
        $email = (new Email)->from('a@example.com')->to('b@example.com')->subject('Subject')->html('<b>HTML</b>')->text('Text')->attach('binary', 'test.bin');
        $envelope = Envelope::create($email);
        $data = (new MessageSerializer)->serialize(new SentMessage($email, $envelope), $email, $envelope);

        $this->assertSame('Subject', $data['subject']);
        $this->assertSame('binary', $data['attachments'][0]['content']);
        $this->assertStringContainsString('Subject: Subject', $data['raw']);
    }
}
