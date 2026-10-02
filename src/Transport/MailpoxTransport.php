<?php

namespace Tommica\Mailpox\Transport;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Tommica\Mailpox\Contracts\MessageStore;
use Tommica\Mailpox\Support\MessageSerializer;

final class MailpoxTransport implements TransportInterface
{
    public function __construct(private MessageStore $store, private MessageSerializer $serializer) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): SentMessage
    {
        $envelope ??= Envelope::create($message);
        $sent = new SentMessage($message, $envelope);

        if ($message instanceof Email) {
            $this->store->store($this->serializer->serialize($sent, $message, $envelope));
        }

        return $sent;
    }

    public function __toString(): string
    {
        return 'mailpox';
    }
}
