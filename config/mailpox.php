<?php

return [
    'enabled' => env('MAILPOX_ENABLED', true),
    'path' => env('MAILPOX_PATH', '/mailpox'),
    'middleware' => ['web', 'mailpox.local'],
    'driver' => env('MAILPOX_DRIVER', 'database'),
    'per_page' => 50,
    'retention_days' => env('MAILPOX_RETENTION_DAYS'),
    'polling_interval' => env('MAILPOX_POLLING_INTERVAL', 5),

    'database' => [
        'connection' => env('MAILPOX_DB_CONNECTION'),
        'table' => 'mailpox_messages',
        'attachments_table' => 'mailpox_attachments',
    ],

    'filesystem' => [
        'disk' => env('MAILPOX_FILESYSTEM_DISK', 'local'),
        'directory' => env('MAILPOX_FILESYSTEM_DIRECTORY', 'mailpox'),
    ],

    'cache' => [
        'store' => env('MAILPOX_CACHE_STORE'),
        'prefix' => 'mailpox',
        'ttl' => env('MAILPOX_CACHE_TTL', 86400),
        'limit' => env('MAILPOX_CACHE_LIMIT', 500),
    ],
];
