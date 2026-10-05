<?php

namespace Tommica\Mailpox\Support;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

final class MessageSerializer
{
    /** @return array<string, mixed> */
    public function serialize(SentMessage $sent, Email $email, Envelope $envelope): array
    {
        $id = bin2hex(random_bytes(16));
        $attachments = [];

        foreach ($email->getAttachments() as $index => $attachment) {
            $attachments[] = $this->attachment($attachment, $id.'-'.($index + 1));
        }

        $headers = [];
        foreach ($email->getHeaders()->all() as $header) {
            $headers[$header->getName()] = $header->getBodyAsString();
        }

        return [
            'id' => $id,
            'message_id' => $sent->getMessageId(),
            'subject' => $email->getSubject(),
            'from' => $this->addresses($email->getFrom()),
            'to' => $this->addresses($email->getTo()),
            'cc' => $this->addresses($email->getCc()),
            'bcc' => $this->addresses($email->getBcc()),
            'html' => $email->getHtmlBody(),
            'text' => $email->getTextBody(),
            'raw' => $sent->toString(),
            'headers' => $headers,
            'attachments' => $attachments,
            'read' => false,
            'created_at' => new \DateTimeImmutable,
        ];
    }

    /** @param Address[] $addresses */
    /** @param array<int, Address> $addresses
     * @return array<int, array{name: string, address: string}>
     */
    private function addresses(array $addresses): array
    {
        return array_map(fn (Address $address): array => [
            'name' => $address->getName(),
            'address' => $address->getAddress(),
        ], $addresses);
    }

    /** @return array<string, mixed> */
    private function attachment(DataPart $attachment, string $id): array
    {
        return [
            'id' => $id,
            'filename' => $attachment->getFilename() ?: 'attachment',
            'content_type' => $attachment->getContentType(),
            'content' => $attachment->getBody(),
            'size' => strlen($attachment->getBody()),
            'disposition' => $attachment->getDisposition(),
            'content_id' => $attachment->getContentId(),
        ];
    }
}
