<?php

namespace Tommica\Mailpox\Support;

use DateTimeImmutable;

final class MessageHydrator
{
    /** @param array<string, mixed> $data */
    public function hydrate(array $data): CapturedMessage
    {
        return new CapturedMessage(
            (string) $data['id'],
            $data['message_id'] ?? null,
            $data['subject'] ?? null,
            $this->decode($data['from'] ?? []),
            $this->decode($data['to'] ?? []),
            $this->decode($data['cc'] ?? []),
            $this->decode($data['bcc'] ?? []),
            $data['html'] ?? null,
            $data['text'] ?? null,
            (string) ($data['raw'] ?? ''),
            $this->decode($data['headers'] ?? []),
            $this->decode($data['attachments'] ?? []),
            (bool) ($data['read'] ?? false),
            $data['created_at'] instanceof DateTimeImmutable ? $data['created_at'] : new DateTimeImmutable((string) $data['created_at']),
        );
    }

    /** @return array<mixed> */
    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return json_decode((string) $value, true, 512, JSON_THROW_ON_ERROR);
    }
}
