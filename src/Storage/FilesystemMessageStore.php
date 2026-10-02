<?php

namespace Tommica\Mailpox\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Tommica\Mailpox\Contracts\MessageStore;
use Tommica\Mailpox\Support\CapturedMessage;
use Tommica\Mailpox\Support\MessageHydrator;

final class FilesystemMessageStore implements MessageStore
{
    public function __construct(private Filesystem $filesystem, private MessageHydrator $hydrator, private string $directory) {}

    public function store(array $message): string
    {
        $path = $this->path($message['id']);
        $this->filesystem->put($path, serialize($message));

        return $message['id'];
    }

    /** @return LengthAwarePaginator<int, mixed> */
    public function paginate(?string $search = null, int $perPage = 50): LengthAwarePaginator
    {
        $messages = collect($this->filesystem->files($this->directory))
            ->filter(fn (string $path): bool => str_ends_with($path, '.mailpox'))
            ->map(fn (string $path): ?array => $this->read($path))
            ->filter()
            ->when($search, fn (Collection $items): Collection => $items->filter(fn (array $item): bool => str_contains(mb_strtolower(($item['subject'] ?? '').json_encode($item['from']).json_encode($item['to'])), mb_strtolower((string) $search))))
            ->sortByDesc('created_at')
            ->values();
        $page = Paginator::resolveCurrentPage();

        return new Paginator($messages->forPage($page, $perPage), $messages->count(), $perPage, $page, ['path' => Paginator::resolveCurrentPath()]);
    }

    public function find(string $id): ?CapturedMessage
    {
        $data = $this->read($this->path($id));

        return $data === null ? null : $this->hydrator->hydrate($data);
    }

    public function attachment(string $messageId, string $attachmentId): ?array
    {
        $data = $this->read($this->path($messageId));
        foreach ($data['attachments'] ?? [] as $attachment) {
            if (hash_equals((string) $attachment['id'], $attachmentId)) {
                return $attachment;
            }
        }

        return null;
    }

    public function markRead(string $id): void
    {
        $data = $this->read($this->path($id));
        if ($data !== null) {
            $data['read'] = true;
            $this->filesystem->put($this->path($id), serialize($data));
        }
    }

    public function delete(string $id): bool
    {
        return $this->filesystem->delete($this->path($id));
    }

    public function deleteAll(): int
    {
        $files = $this->filesystem->files($this->directory);
        $this->filesystem->delete($files);

        return count($files);
    }

    public function prune(int $days): int
    {
        $count = 0;
        foreach ($this->filesystem->files($this->directory) as $path) {
            $data = $this->read($path);
            if ($data && $data['created_at'] < now()->subDays($days)) {
                $this->filesystem->delete($path);
                $count++;
            }
        }

        return $count;
    }

    private function path(string $id): string
    {
        return trim($this->directory, '/').'/'.basename($id).'.mailpox';
    }

    /** @return array<string, mixed>|null */
    private function read(string $path): ?array
    {
        if (! $this->filesystem->exists($path)) {
            return null;
        }
        $value = unserialize($this->filesystem->get($path), ['allowed_classes' => [\DateTimeImmutable::class]]);

        return is_array($value) ? $value : null;
    }
}
