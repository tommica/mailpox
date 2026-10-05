# Mailpox

Mailpox is a local mailbox for Laravel 9–13. It captures messages through a Symfony Mailer transport and presents them in a self-contained Blade interface with no CDN dependencies.

## Installation

```bash
composer require --dev tommica/mailpox
php artisan vendor:publish --tag=mailpox-config
php artisan migrate
open http://localhost/mailpox
```

Mailpox registers its mailer automatically through Laravel package discovery. Select it in local development:

```dotenv
MAIL_MAILER=mailpox
MAILPOX_ENABLED=true
MAILPOX_DRIVER=database
```

The inbox polls for new mail every five seconds by default; set `MAILPOX_POLLING_INTERVAL=0` to disable it. Visit `/mailpox`. Routes are restricted to the `local` and `testing` environments.

## Storage

The default `database` driver stores complete MIME bodies and attachment bytes in package tables. `filesystem` and TTL-backed `cache` drivers are also available.

## Cleanup

Delete individual messages or all messages in the UI. Optional automatic retention is available:

```dotenv
MAILPOX_RETENTION_DAYS=7
```

Schedule `mailpox:prune`, or run `php artisan mailpox:prune --days=7` manually.
