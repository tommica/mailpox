<?php

namespace Tommica\Mailpox\Storage;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Tommica\Mailpox\Contracts\MessageStore;
use Tommica\Mailpox\Support\CapturedMessage;
use Tommica\Mailpox\Support\MessageHydrator;

final class DatabaseMessageStore implements MessageStore
{
    public function __construct(private ConnectionInterface $connection, private MessageHydrator $hydrator) {}

    public function store(array $message): string
    {
        $attachments = $message['attachments'];
        unset($message['attachments']);

        $this->connection->transaction(function () use ($message, $attachments): void {
            $this->messages()->insert([
                ...$this->record($message),
                'created_at' => $message['created_at']->format('Y-m-d H:i:s.u'),
                'updated_at' => $message['created_at']->format('Y-m-d H:i:s.u'),
            ]);

            foreach ($attachments as $attachment) {
                $this->attachments()->insert([
                    'id' => $attachment['id'],
                    'message_id' => $message['id'],
                    'filename' => $attachment['filename'],
                    'content_type' => $attachment['content_type'],
                    'content' => $attachment['content'],
                    'size' => $attachment['size'],
                    'disposition' => $attachment['disposition'],
                    'content_id' => $attachment['content_id'],
                    'created_at' => $message['created_at']->format('Y-m-d H:i:s.u'),
                ]);
            }
        });

        return $message['id'];
    }

    /** @return LengthAwarePaginator<int, mixed> */
    public function paginate(?string $search = null, int $perPage = 50): LengthAwarePaginator
    {
        $query = $this->messages()->select(['id', 'subject', 'from', 'to', 'read', 'created_at']);
        if ($search !== null && $search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('subject', 'like', '%'.$search.'%')
                    ->orWhere('from', 'like', '%'.$search.'%')
                    ->orWhere('to', 'like', '%'.$search.'%');
            });
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function find(string $id): ?CapturedMessage
    {
        $record = $this->messages()->where('id', $id)->first();
        if ($record === null) {
            return null;
        }

        $data = (array) $record;
        $data['attachments'] = $this->attachments()->where('message_id', $id)->get()->map(fn ($item): array => [
            'id' => $item->id,
            'filename' => $item->filename,
            'content_type' => $item->content_type,
            'size' => (int) $item->size,
            'disposition' => $item->disposition,
            'content_id' => $item->content_id,
        ])->all();

        return $this->hydrator->hydrate($data);
    }

    public function attachment(string $messageId, string $attachmentId): ?array
    {
        $record = $this->attachments()->where('message_id', $messageId)->where('id', $attachmentId)->first();

        return $record === null ? null : (array) $record;
    }

    public function markRead(string $id): void
    {
        $this->messages()->where('id', $id)->update(['read' => true, 'updated_at' => now()]);
    }

    public function delete(string $id): bool
    {
        return $this->messages()->where('id', $id)->delete() > 0;
    }

    public function deleteAll(): int
    {
        return $this->messages()->delete();
    }

    public function prune(int $days): int
    {
        return $this->messages()->where('created_at', '<', now()->subDays($days))->delete();
    }

    private function messages(): Builder
    {
        return $this->connection->table((string) config('mailpox.database.table'));
    }

    private function attachments(): Builder
    {
        return $this->connection->table((string) config('mailpox.database.attachments_table'));
    }

    /** @param array<string, mixed> $message
     * @return array<string, mixed>
     */
    private function record(array $message): array
    {
        foreach (['from', 'to', 'cc', 'bcc', 'headers'] as $key) {
            $message[$key] = json_encode($message[$key], JSON_THROW_ON_ERROR);
        }
        $message['read'] = false;

        return $message;
    }
}
