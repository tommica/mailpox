<?php

namespace Tommica\Mailpox\Support;

use DateTimeImmutable;

final class CapturedMessage
{
    /**
     * @param  array<int, array{name: string, address: string}>  $from
     * @param  array<int, array{name: string, address: string}>  $to
     * @param  array<int, array{name: string, address: string}>  $cc
     * @param  array<int, array{name: string, address: string}>  $bcc
     * @param  array<string, string>  $headers
     * @param  array<int, array{id: string, filename: string, content_type: string, size: int, disposition: string, content_id: ?string}>  $attachments
     */
    public function __construct(
        public string $id,
        public ?string $messageId,
        public ?string $subject,
        public array $from,
        public array $to,
        public array $cc,
        public array $bcc,
        public ?string $html,
        public ?string $text,
        public string $raw,
        public array $headers,
        public array $attachments,
        public bool $read,
        public DateTimeImmutable $createdAt,
    ) {}
}
