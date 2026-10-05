<?php

namespace Tommica\Mailpox\Console;

use Illuminate\Console\Command;
use Tommica\Mailpox\Contracts\MessageStore;

final class PruneMailpoxCommand extends Command
{
    protected $signature = 'mailpox:prune {--days= : Override configured retention period}';

    protected $description = 'Delete captured emails older than the retention period';

    public function handle(MessageStore $store): int
    {
        $days = $this->option('days') ?? config('mailpox.retention_days');
        if ($days === null || (int) $days < 1) {
            $this->components->warn('Mailpox retention is disabled. Set MAILPOX_RETENTION_DAYS or pass --days.');

            return self::SUCCESS;
        }

        $this->components->info($store->prune((int) $days).' messages pruned.');

        return self::SUCCESS;
    }
}
