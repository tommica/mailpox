<?php

namespace Tommica\Mailpox\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Tommica\Mailpox\Support\CapturedMessage;

interface MessageStore
{
    /** @param array<string, mixed> $message */
    public function store(array $message): string;

    /** @return LengthAwarePaginator<int, mixed> */
    public function paginate(?string $search = null, int $perPage = 50): LengthAwarePaginator;

    public function find(string $id): ?CapturedMessage;

    public function markRead(string $id): void;

    /** @return array{id: string, filename: string, content_type: string, content: string, size: int, disposition: string, content_id: ?string}|null */
    public function attachment(string $messageId, string $attachmentId): ?array;

    public function delete(string $id): bool;

    public function deleteAll(): int;

    public function prune(int $days): int;
}
