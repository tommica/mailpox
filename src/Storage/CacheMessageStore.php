<?php

namespace Tommica\Mailpox\Storage;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Tommica\Mailpox\Contracts\MessageStore;
use Tommica\Mailpox\Support\CapturedMessage;
use Tommica\Mailpox\Support\MessageHydrator;

final class CacheMessageStore implements MessageStore
{
    public function __construct(private Repository $cache, private MessageHydrator $hydrator, private string $prefix, private int $ttl, private int $limit) {}

    public function store(array $message): string
    {
        $this->cache->put($this->messageKey($message['id']), $message, $this->ttl);
        $ids = array_values(array_unique([$message['id'], ...$this->ids()]));
        foreach (array_slice($ids, $this->limit) as $id) {
            $this->cache->forget($this->messageKey($id));
        }
        $this->cache->put($this->indexKey(), array_slice($ids, 0, $this->limit), $this->ttl);

        return $message['id'];
    }

    /** @return LengthAwarePaginator<int, mixed> */
    public function paginate(?string $search = null, int $perPage = 50): LengthAwarePaginator
    {
        $messages = collect($this->ids())->map(fn (string $id): ?array => $this->cache->get($this->messageKey($id)))->filter();
        if ($search) {
            $needle = mb_strtolower($search);
            $messages = $messages->filter(fn (array $item): bool => str_contains(mb_strtolower(($item['subject'] ?? '').json_encode($item['from']).json_encode($item['to'])), $needle));
        }
        $messages = $messages->sortByDesc('created_at')->values();
        $page = Paginator::resolveCurrentPage();

        return new Paginator($messages->forPage($page, $perPage), $messages->count(), $perPage, $page, ['path' => Paginator::resolveCurrentPath()]);
    }

    public function find(string $id): ?CapturedMessage
    {
        $data = $this->cache->get($this->messageKey($id));

        return is_array($data) ? $this->hydrator->hydrate($data) : null;
    }

    public function attachment(string $messageId, string $attachmentId): ?array
    {
        $data = $this->cache->get($this->messageKey($messageId), []);
        foreach ($data['attachments'] ?? [] as $attachment) {
            if (hash_equals((string) $attachment['id'], $attachmentId)) {
                return $attachment;
            }
        }

        return null;
    }

    public function markRead(string $id): void
    {
        $data = $this->cache->get($this->messageKey($id));
        if (is_array($data)) {
            $data['read'] = true;
            $this->cache->put($this->messageKey($id), $data, $this->ttl);
        }
    }

    public function delete(string $id): bool
    {
        $deleted = $this->cache->forget($this->messageKey($id));
        $this->cache->put($this->indexKey(), array_values(array_diff($this->ids(), [$id])), $this->ttl);

        return $deleted;
    }

    public function deleteAll(): int
    {
        $ids = $this->ids();
        foreach ($ids as $id) {
            $this->cache->forget($this->messageKey($id));
        }
        $this->cache->forget($this->indexKey());

        return count($ids);
    }

    public function prune(int $days): int
    {
        $count = 0;
        foreach ($this->ids() as $id) {
            $message = $this->cache->get($this->messageKey($id));
            if (is_array($message) && $message['created_at'] < now()->subDays($days)) {
                $this->delete($id);
                $count++;
            }
        }

        return $count;
    }

    /** @return array<int, string> */
    private function ids(): array
    {
        return $this->cache->get($this->indexKey(), []);
    }

    private function indexKey(): string
    {
        return $this->prefix.':index';
    }

    private function messageKey(string $id): string
    {
        return $this->prefix.':message:'.$id;
    }
}
